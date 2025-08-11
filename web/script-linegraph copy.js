// Fungsi membersihkan format waktu mikrodetik menjadi milidetik
function fixDateString(dateStr) {
  if (!dateStr) return null;
  // Contoh: "2025-05-10T03:47:39.107693" -> "2025-05-10T03:47:39.107Z"
  return dateStr.replace(/(\.\d{3})\d+/, '$1') + 'Z';
}

// Fungsi validasi tanggal
function isValidDate(d) {
  return d instanceof Date && !isNaN(d);
}

// Fungsi mengambil data dari API
async function fetchSensorData() {
  try {
    const response = await fetch('https://api.smartclass.elektrolosskediri.my.id/graph/realtime');
    if (!response.ok) throw new Error('Gagal mengambil data API');
    const data = await response.json();
    //console.log('Data dari API:', data);
    return data;
  } catch (error) {
    console.error('Error fetch data:', error);
    document.getElementById('status').textContent = 'Gagal mengambil data sensor.';
    document.getElementById('status').style.color = 'red';
    return null;
  }
}

// Fungsi membuat dataset Chart.js dari data sensor, membatasi 200 data terakhir
function createDataset(dataArray, valueKey, label, borderColor, yAxisID = 'y') {
  if (!dataArray) return {
    label,
    data: [],
    borderColor,
    fill: false,
    yAxisID,
    tension: 0.4,
    pointRadius: 0,
    //cubicInterpolationMode: 'monotone'
  };

  // Filter dan perbaiki tanggal
  const filtered = dataArray
    .filter(item => item[valueKey] !== null && item[valueKey] !== undefined && fixDateString(item.created))
    .map(item => {
      const fixedDate = fixDateString(item.created);
      const dateObj = new Date(fixedDate);
      return isValidDate(dateObj) ? { x: dateObj, y: item[valueKey] } : null;
    })
    .filter(item => item !== null)
    .sort((a, b) => a.x - b.x);

  // Ambil 200 data terakhir
  const last200 = filtered.slice(-200);

  return {
    label,
    data: last200,
    //cubicInterpolationMode: 'monotone',
    borderColor,
    fill: false,
    yAxisID,
    tension: 0.4,
    pointRadius: 0
  };
}

// Variabel global untuk chart agar bisa di-update
let chartVoltagePower = null;
let chartEnvironment = null;

// Fungsi inisialisasi grafik kosong
function initCharts() {
  const ctx1 = document.getElementById('chartVoltagePower').getContext('2d');
  chartVoltagePower = new Chart(ctx1, {
    type: 'line',
    data: { datasets: [] },
    options: {
      responsive: true,
      parsing: false,
      scales: {
        x: {
          type: 'time',
          time: {
            unit: 'second',
            displayFormats: {
              second: 'HH:mm:ss'
            },
            tooltipFormat: 'PPpp'
          },
          ticks: {
            maxRotation: 45,
            minRotation: 30,
            autoSkip: true,
            maxTicksLimit: 12
          },
          title: {
            display: true,
            text: 'Waktu'
          },
          min: null,
          max: null
        },
        y: {
          suggestedMin: 210,
          suggestedMax: 240,
          type: 'linear',
          position: 'left',
          title: { display: true, text: 'Voltage (vi)' }
        },
        y1: {
          type: 'linear',
          position: 'right',
          grid: { drawOnChartArea: false },
          title: { display: true, text: 'Power Input (pi)' }
        }
      },
      interaction: { mode: 'nearest', intersect: false },
      plugins: {
        legend: { position: 'top' },
        tooltip: { mode: 'index', intersect: false }
      }
    }
  });

  const ctx2 = document.getElementById('chartEnvironment').getContext('2d');
  chartEnvironment = new Chart(ctx2, {
    type: 'line',
    data: { datasets: [] },
    options: {
      responsive: true,
      parsing: false,
      scales: {
        x: {
          type: 'time',
          time: {
            tooltipFormat: 'HH:mm:ss',
            displayFormats: { second: 'HH:mm:ss', minute: 'HH:mm' }
          },
          title: { display: true, text: 'Waktu' },
          min: null,
          max: null
        },
        y: {
          type: 'linear',
          title: { display: true, text: 'Data Lingkungan' }
        }
      },
      interaction: { mode: 'nearest', intersect: false },
      plugins: {
        legend: { position: 'top' },
        tooltip: { mode: 'index', intersect: false }
      }
    }
  });
}

function updateCharts(data) {
  if (!data) return;

  const newDatasets = [
    createDataset(data.smartmeter1, 'vi', 'Voltage 1', 'blue', 'y'),
    createDataset(data.smartmeter2, 'vi', 'Voltage 2', 'red', 'y'),
    createDataset(data.smartmeter1, 'pi', 'Power Input 1', 'purple', 'y1'),
    createDataset(data.smartmeter2, 'pi', 'Power Input 2', 'green', 'y1')
  ];

  // Update data pada dataset yang sudah ada
  newDatasets.forEach((newDs, index) => {
    if (chartVoltagePower.data.datasets[index]) {
      chartVoltagePower.data.datasets[index].data = newDs.data;
    } else {
      chartVoltagePower.data.datasets[index] = newDs;
    }
  });

  // Jika ada dataset lebih banyak dari sebelumnya, hapus sisanya
  chartVoltagePower.data.datasets.splice(newDatasets.length);

  // Lakukan hal sama untuk chartEnvironment
  const newEnvDatasets = [
    createDataset(data.smartclass1, 'temperature', 'Temperature', 'orange'),
    createDataset(data.smartclass1, 'humidity', 'Humidity', 'green'),
    createDataset(data.smartclass1, 'count', 'People Count', 'purple')
  ];

  newEnvDatasets.forEach((newDs, index) => {
    if (chartEnvironment.data.datasets[index]) {
      chartEnvironment.data.datasets[index].data = newDs.data;
    } else {
      chartEnvironment.data.datasets[index] = newDs;
    }
  });

  chartEnvironment.data.datasets.splice(newEnvDatasets.length);

  // Update sumbu waktu seperti biasa
  const allTimes = newDatasets
    .flatMap(ds => ds.data.map(point => point.x))
    .filter(t => t instanceof Date);

  if (allTimes.length > 0) {
    const minTime = new Date(Math.min(...allTimes));
    const maxTime = new Date(Math.max(...allTimes));

    chartVoltagePower.options.scales.x.min = minTime;
    chartVoltagePower.options.scales.x.max = maxTime;
    chartEnvironment.options.scales.x.min = minTime;
    chartEnvironment.options.scales.x.max = maxTime;
  }

  // Update grafik dengan animasi halus
  chartVoltagePower.update();
  chartEnvironment.update();
}


// Fungsi utama untuk fetch dan update grafik secara berkala
async function refreshData() {
  const data = await fetchSensorData();
  updateCharts(data);
}

// Inisialisasi grafik dan mulai refresh data setiap 2 detik
document.addEventListener('DOMContentLoaded', () => {
  initCharts();
  refreshData(); // panggil pertama kali
  setInterval(refreshData, 2000); // refresh setiap 2 detik
});
