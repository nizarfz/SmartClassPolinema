async function fetchdata() {
  try {
    const response = await fetch("https://smartclass.elektrolosskediri.my.id/absensi_mahasiswa/api.php?today");
    const data = await response.json();

    // Clear existing tables before creating new ones
    clearContainer('mahasiswa-table-container');
    clearContainer('dosen-table-container');

    createNameTable('mahasiswa-table-container', 'Mahasiswa', data.mahasiswa.data);
    createNameTable('dosen-table-container', 'Dosen', data.dosen.data);
  }
  catch (error) {
    console.error("Gagal mengambil data:", error);
  }
}

function clearContainer(containerId) {
  const container = document.getElementById(containerId);
  container.innerHTML = ''; // Clear previous content
}

function createNameTable(containerId, captionText, data) {
  const container = document.getElementById(containerId);
  const table = document.createElement('table');
  table.classList.add('attendance-table');

  const caption = document.createElement('caption');
  caption.textContent = captionText;
  caption.classList.add('attendance-caption');
  table.appendChild(caption);

  const thead = document.createElement('thead');
  thead.classList.add('attendance-header');
  const headerRow = document.createElement('tr');

  // Buat kolom judul
  const headers = ['Nama', 'Mata Kuliah', 'Suhu', 'Status'];
  headers.forEach(text => {
    const th = document.createElement('th');
    th.textContent = text;
    headerRow.appendChild(th);
  });

  thead.appendChild(headerRow);
  table.appendChild(thead);

  const tbody = document.createElement('tbody');
  tbody.classList.add('attendance-body');

  // Buat isi tabel
  data.forEach(item => {
    const row = document.createElement('tr');

    const namaCell = document.createElement('td');
    namaCell.textContent = item.nama;
    row.appendChild(namaCell);

    const kelasCell = document.createElement('td');
    kelasCell.textContent = item.matkul;
    row.appendChild(kelasCell);

    const suhuCell = document.createElement('td');
    suhuCell.textContent = item.suhu;
    if(item.suhu <= 37){
        suhuCell.style = "color:green;";
    }else{
        suhuCell.style = "color:red;";
    }
    row.appendChild(suhuCell);

    const statusCell = document.createElement('td');
    statusCell.textContent = item.status;
    if(item.status == "Hadir" || item.status == "masuk"){
        statusCell.style = "color:green;";
    }else if(item.status == "Terlambat" || item.status == "Alpa"){
        statusCell.style = "color:red;";
    }else{
        statusCell.style = "color:orange;";
    }
    row.appendChild(statusCell);

    tbody.appendChild(row);
  });

  table.appendChild(tbody);
  container.appendChild(table);
}

let last_dosen_notification = null;
let last_mahasiswa_notification = null;

async function fetchdata2() {
  try {
    const response = await fetch("https://smartclass.elektrolosskediri.my.id/absensi_mahasiswa/api.php?last");
    const data = await response.json();
    if (data.mahasiswa === last_mahasiswa_notification) {
      // Skip duplicate message
      
    }
    else{
      last_mahasiswa_notification = data.mahasiswa;
      console.log(data.mhsket);
      if(data.mhsket == "Terlambat" || data.mhsket == "Alpa"){
        showToast(data.mahasiswa,bgcolor = "red");
      }else if(data.mhsket == "Hadir"){
        showToast(data.mahasiswa);
      }else showToast(data.mahasiswa, bgcolor = "orange");
    }
    if (data.dosen === last_dosen_notification) {
      // Skip duplicate message
      
    }
    else{
      last_dosen_notification = data.dosen;
      showToast(data.dosen);
    }
  }
  catch (error) {
    console.error("Gagal mengambil data:", error);
  }
}

async function fetchdata3() {
  try {
    const response = await fetch("https://smartclass.elektrolosskediri.my.id/absensi_mahasiswa/api.php?jumlah");
    const data = await response.json();
    //console.log(data);
    const jumlahmhs = document.getElementById("jumlah_mahasiswa");
    const mhs = document.getElementById("mahasiswa_hadir");
    const jumlahdosen = document.getElementById("jumlah_dosen");
    const dosen = document.getElementById("dosen_hadir");
    const jumlahmatkul = document.getElementById("jumlah_matkul");
    const matkul = document.getElementById("matkul");

    jumlahdosen.textContent = data.dosen;
    jumlahmhs.textContent = data.mahasiswa;
    mhs.textContent = data.mahasiswa_hadir;
    dosen.textContent = data.dosen_hadir;
    jumlahmatkul.textContent = data.matkul;
    matkul.textContent = data.matkulberlangsung;
  }
  catch (error) {
    console.error("Gagal mengambil data:", error);
  }
}


// Fetch data immediately and then update every second
fetchdata();
fetchdata2();
fetchdata3();
setInterval(fetchdata, 5000);
setInterval(fetchdata2, 1000);
setInterval(fetchdata3, 1000);
