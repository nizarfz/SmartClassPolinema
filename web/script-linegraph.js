
function convertToGMT7(timestamp) {
  const truncated = timestamp.replace(/(\.\d{3})\d+/, '$1');
  return new Date(truncated + 'Z'); // parsing sebagai UTC, tampilkan otomatis di lokal (GMT+7)
}

function getTimeRange(datasets) {
  let minTime = null;
  let maxTime = null;

  datasets.forEach(ds => {
    ds.data.forEach(point => {
      const time = point.x.getTime();
      if (minTime === null || time < minTime) minTime = time;
      if (maxTime === null || time > maxTime) maxTime = time;
    });
  });

  return { min: minTime ? new Date(minTime) : null, max: maxTime ? new Date(maxTime) : null };
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
  };

  const processedData = dataArray.map(item => ({
    x: convertToGMT7(item.created),
    y: item[valueKey],
  }));



  return {
    label,
    data: processedData,
    borderColor,
    fill: false,
    yAxisID,
    tension: 0.4,
    pointRadius: 0,
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
    // Hilangkan unit agar Chart.js otomatis menyesuaikan
    // unit: 'second',
    displayFormats: {
      second: 'HH:mm:ss',
      minute: 'HH:mm',
      hour: 'HH:mm',
    },
    tooltipFormat: 'PPpp'
  },
  ticks: {
    maxRotation: 45,
    minRotation: 30,
    autoSkip: false, // coba nonaktifkan autoSkip untuk lihat semua titik
    maxTicksLimit: 20 // atau sesuaikan sesuai kebutuhan
  },
  title: {
    display: true,
    text: 'Waktu'
  },
  min: 100,
  max: null
},
        y: {
          suggestedMin: 210,
          suggestedMax: 240,
          type: 'linear',
          position: 'left',
          title: { display: true, text: 'Voltage (V)' }
        },
        y1: {
          suggestedMax: 5,
          type: 'linear',
          position: 'right',
          grid: { drawOnChartArea: false },
          title: { display: true, text: 'Power(W)' }
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
          suggestedMax: 30,
          suggestedMin: 20,
          type: 'linear',
          grid: { drawOnChartArea: false },
          title: { display: true, text: 'Temp' }
        },
        y1: {
          suggestedMax: 70,
          suggestedMin: 40,
          type: 'linear',
          position: 'right',
          grid: { drawOnChartArea: false },
          title: { display: true, text: 'Hum' }
        },
        y2: {
          suggestedMax: 10,
          type: 'linear',
          position: 'right',
          grid: { drawOnChartArea: false },
          title: { display: true, text: 'People' }
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

function updateCharts(datameter, datasmartclass) {
  if (!datameter && !datasmartclass) return;

  // Dataset untuk chart pertama
  const newDatasets = [
    createDataset(datameter.smartmeter1, 'vi', 'V Kelas 1', 'blue', 'y'),
    createDataset(datameter.smartmeter2, 'vi', 'V Kelas 2', 'red', 'y'),
    createDataset(datameter.smartmeter1, 'pi', 'P Kelas 1', 'purple', 'y1'),
    createDataset(datameter.smartmeter2, 'pi', 'P Kelas 2', 'green', 'y1')
  ];

  // Dataset untuk chart kedua
  const newEnvDatasets = [
    createDataset(datasmartclass.smartclass1, 'temperature', 'Temperature', 'red','y'),
    createDataset(datasmartclass.smartclass1, 'humidity', 'Humidity', 'blue','y1'),
    createDataset(datasmartclass.smartclass1, 'people_count', 'People Count', 'purple','y2')
  ];

  // Update dataset chartVoltagePower
  newDatasets.forEach((newDs, index) => {
    if (chartVoltagePower.data.datasets[index]) {
      chartVoltagePower.data.datasets[index].data = newDs.data;
    } else {
      chartVoltagePower.data.datasets[index] = newDs;
    }
  });
  chartVoltagePower.data.datasets.splice(newDatasets.length);

  // Update dataset chartEnvironment
  newEnvDatasets.forEach((newDs, index) => {
    if (chartEnvironment.data.datasets[index]) {
      chartEnvironment.data.datasets[index].data = newDs.data;
    } else {
      chartEnvironment.data.datasets[index] = newDs;
    }
  });
  chartEnvironment.data.datasets.splice(newEnvDatasets.length);

  // Hitung rentang waktu masing-masing chart
  const voltageTimeRange = getTimeRange(newDatasets);
  const environmentTimeRange = getTimeRange(newEnvDatasets);

  // Set rentang waktu chartVoltagePower
  if (voltageTimeRange.min && voltageTimeRange.max) {
    chartVoltagePower.options.scales.x.min = voltageTimeRange.min;
    chartVoltagePower.options.scales.x.max = voltageTimeRange.max;
  } else {
    chartVoltagePower.options.scales.x.min = null;
    chartVoltagePower.options.scales.x.max = null;
  }

  // Set rentang waktu chartEnvironment
  if (environmentTimeRange.min && environmentTimeRange.max) {
    chartEnvironment.options.scales.x.min = environmentTimeRange.min;
    chartEnvironment.options.scales.x.max = environmentTimeRange.max;
  } else {
    chartEnvironment.options.scales.x.min = null;
    chartEnvironment.options.scales.x.max = null;
  }

  // Update kedua chart
  chartVoltagePower.update();
  chartEnvironment.update();
}

datameter ="";
delay1=0;
a = 0;
const intervalSelect = document.getElementById('interval');
let selectedInterval = intervalSelect.value;
if(selectedInterval == "realtime") delay1 = 0; 
else {
  delay1 = 60;
  a = 60;
}
intervalSelect.addEventListener('change', () => {
  selectedInterval = intervalSelect.value;
  console.log('Interval dipilih:', selectedInterval);
  if(selectedInterval == "realtime") delay1 = 0; else delay1 = 60;
  a = 60;
});

dataclass ="";
delay2=0;
b = 0;
const intervalSelect2 = document.getElementById('interval2');
let selectedInterval2 = intervalSelect2.value;
if(selectedInterval2 == "realtime") delay2 = 0; 
else{
  delay2 = 60;
  b = 60
}
intervalSelect2.addEventListener('change', () => {
  selectedInterval2 = intervalSelect2.value;
  console.log('Interval2 dipilih:', selectedInterval);
  if(selectedInterval2 == "realtime") delay2 = 0; else delay2 = 60;
  b = 60;
});

// Fungsi mengambil data dari API
async function fetchMeterData() {
  try {
    response ="";
    if(selectedInterval == "realtime"){
      response = await fetch('https://api.smartclass.elektrolosskediri.my.id/graph/realtime/smartmeter');
    }else{
      response = await fetch('https://api.smartclass.elektrolosskediri.my.id/graph/realtime/smartmeter/'+ selectedInterval);
    }
    if (!response.ok) throw new Error('Gagal mengambil data API');
    const data = await response.json();
    //console.log('Data dari API:', data);
    return data;
  } catch (error) {
    console.error('Error fetch data:', error);
    return null;
  }
}

async function fetchClassData() {
  try {
    response ="";
    if(selectedInterval2 == "realtime"){
      response = await fetch('https://api.smartclass.elektrolosskediri.my.id/graph/realtime/smartclass');
    }else{
      response = await fetch('https://api.smartclass.elektrolosskediri.my.id/graph/realtime/smartclass/'+ selectedInterval2);
    }
    if (!response.ok) throw new Error('Gagal mengambil data API');
    const data = await response.json();
    //console.log('Data dari API:', data);
    return data;
  } catch (error) {
    console.error('Error fetch data:', error);
    return null;
  }
}

// Fungsi utama untuk fetch dan update grafik secara berkala
async function refreshData() {
  if(a >= delay1){
    a = 0;
    datameter = await fetchMeterData();
  }else{
    a++;
  }
  if(b >= delay2){
    b = 0;
    dataclass = await fetchClassData();
  }else{
    b++;
  }
  updateCharts(datameter,dataclass);
}

// Inisialisasi grafik dan mulai refresh data setiap 2 detik
document.addEventListener('DOMContentLoaded', () => {
  initCharts();
  refreshData(); // panggil pertama kali
  setInterval(refreshData, 2000); // refresh setiap 2 detik
});
