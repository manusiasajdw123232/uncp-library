<?php
class LoanService {
  public function __construct(private PDO $pdo){}
  public function request(int $userId,int $bookId): void {
    $this->pdo->beginTransaction();
    try{
      $st=$this->pdo->prepare('SELECT id,stock_available FROM books WHERE id=? FOR UPDATE');$st->execute([$bookId]);$book=$st->fetch();
      if(!$book || (int)$book['stock_available']<1) throw new RuntimeException('Buku tidak tersedia.');
      $this->pdo->prepare("INSERT INTO loans(user_id,status) VALUES(?, 'menunggu')")->execute([$userId]);$loanId=(int)$this->pdo->lastInsertId();
      $this->pdo->prepare('INSERT INTO loan_items(loan_id,book_id,qty) VALUES(?,?,1)')->execute([$loanId,$bookId]);
      $this->pdo->commit();
    }catch(Throwable $e){$this->pdo->rollBack();throw $e;}
  }
  public function approve(int $loanId,int $staffId): void {
    $this->pdo->beginTransaction();
    try{$st=$this->pdo->prepare("SELECT l.*,li.book_id,li.qty,b.stock_available FROM loans l JOIN loan_items li ON li.loan_id=l.id JOIN books b ON b.id=li.book_id WHERE l.id=? FOR UPDATE");$st->execute([$loanId]);$r=$st->fetch();if(!$r||$r['status']!=='menunggu')throw new RuntimeException('Permintaan tidak valid.');if((int)$r['stock_available']<1)throw new RuntimeException('Stok buku habis.');$days=(int)($this->pdo->query("SELECT setting_value FROM settings WHERE setting_key='return_days'")->fetchColumn()?:7);$this->pdo->prepare("UPDATE loans SET status='dipinjam',loan_date=CURDATE(),due_date=DATE_ADD(CURDATE(), INTERVAL ? DAY),approved_by=? WHERE id=?")->execute([$days,$staffId,$loanId]);$this->pdo->prepare('UPDATE books SET stock_available=stock_available-? WHERE id=?')->execute([$r['qty'],$r['book_id']]);$this->pdo->commit();}catch(Throwable $e){$this->pdo->rollBack();throw $e;}
  }
  public function returnLoan(int $loanId,int $staffId,string $condition='baik'): void {
    $this->pdo->beginTransaction();
    try{$st=$this->pdo->prepare("SELECT l.*,li.book_id,li.qty FROM loans l JOIN loan_items li ON li.loan_id=l.id WHERE l.id=? FOR UPDATE");$st->execute([$loanId]);$r=$st->fetch();if(!$r||!in_array($r['status'],['dipinjam','terlambat'],true))throw new RuntimeException('Peminjaman tidak dapat dikembalikan.');$fine=0;if(!empty($r['due_date']) && strtotime($r['due_date'])<strtotime(date('Y-m-d'))){$days=max(0,(int)((time()-strtotime($r['due_date']))/86400));$per=(float)($this->pdo->query("SELECT setting_value FROM settings WHERE setting_key='fine_per_day'")->fetchColumn()?:1000);$fine=$days*$per;}$this->pdo->prepare("INSERT INTO returns(loan_id,processed_by,condition_status,fine_amount) VALUES(?,?,?,?)")->execute([$loanId,$staffId,$condition,$fine]);$this->pdo->prepare("UPDATE loans SET status='dikembalikan',returned_at=NOW() WHERE id=?")->execute([$loanId]);if($condition==='baik'){$this->pdo->prepare("UPDATE books SET stock_available=LEAST(stock_available+?,stock_total),status=CASE WHEN stock_available>0 THEN 'tersedia' ELSE 'habis' END WHERE id=?")->execute([$r['qty'],$r['book_id']]);}elseif(in_array($condition,['rusak','hilang'],true)){$this->pdo->prepare("UPDATE books SET stock_total=GREATEST(stock_total-?,0),stock_available=LEAST(stock_available,stock_total),status=CASE WHEN stock_available>0 THEN 'tersedia' WHEN stock_total=0 THEN ? ELSE 'habis' END WHERE id=?")->execute([$r['qty'],$condition==='hilang'?'hilang':'rusak',$r['book_id']]);}if($fine>0)$this->pdo->prepare("INSERT INTO fines(loan_id,user_id,amount,reason) VALUES(?,?,?,'Keterlambatan pengembalian')")->execute([$loanId,$r['user_id'],$fine]);$this->pdo->commit();}catch(Throwable $e){$this->pdo->rollBack();throw $e;}
  }
}
