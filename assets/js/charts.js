function makeChart(id, type, labels, data, label) {
  if (typeof Chart === 'undefined') return;

  const canvas = document.getElementById(id);
  if (!canvas) return;

  return new Chart(canvas, {
    type,
    data: {
      labels,
      datasets: [
        {
          label,
          data,
          borderWidth: 2,
          tension: 0.35,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: type !== 'bar',
        },
      },
    },
  });
}
