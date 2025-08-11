const apiUrl = "https://api.smartclass.elektrolosskediri.my.id/realtime"; // Ganti dengan URL asli

const voltageDisplay1 = document.getElementById("voltage-display-1");
const ampereDisplay1 = document.getElementById("ampere-display-1");
const powerDisplay1 = document.getElementById("power-display-1");
const voltageDisplay2 = document.getElementById("voltage-display-2");
const ampereDisplay2 = document.getElementById("ampere-display-2");
const powerDisplay2 = document.getElementById("power-display-2");
const tempDisplay1 = document.getElementById("smartclass_temp1");
const humDisplay1 = document.getElementById("smartclass_humi1");
const peopleDisplay1 = document.getElementById("smartclass_people1");
const uvDisplay1 = document.getElementById("smartclass_uv1");
const lamp1Display =document.getElementById("smartclass_lamp1");
const lamp2Display =document.getElementById("smartclass_lamp2");
const amperePercentageDisplay1 = document.getElementById("ampere-percent-display-1");
const powerPercentageDisplay1 = document.getElementById("power-percent-display-1");
const amperePercentageDisplay2 = document.getElementById("ampere-percent-display-2");
const powerPercentageDisplay2 = document.getElementById("power-percent-display-2");
const volt_status_1 = document.getElementById("voltage_status_1");
const volt_status_2 = document.getElementById("voltage_status_2");

let lastMessage1 =null;

function notifvolt1(voltage1){
if(voltage1 <= 200){
  bgcolor = "rgba(180, 0, 0, 0.67)";
  message = ("TEGANGAN KELAS 1 RENDAH");
}else if(voltage1 <= 250){
  bgcolor = "rgba(0, 180, 0, 0.67)";
  message = ("TEGANGAN KELAS 1 NORMAL");
}else if(voltage1 <= 999){
  bgcolor = "rgba(180, 0, 0, 0.67)";
  message = ("TEGANGAN KELAS 1 TINGGI");
}
if (message === lastMessage1) {
      // Skip duplicate message
      return;
      }
else{
  lastMessage1 = message;
  showToast(message,bgcolor);
}
}

let lastMessage2 =null;

function notifvolt2(voltage){
if(voltage <= 200){
  bgcolor = "rgba(180, 0, 0, 0.67)";
  message = ("TEGANGAN KELAS 2 RENDAH");
}else if(voltage <= 250){
  bgcolor = "rgba(0, 180, 0, 0.67)";
  message = ("TEGANGAN KELAS 2 NORMAL");
}else if(voltage <= 999){
  bgcolor = "rgba(180, 0, 0, 0.67)";
  message = ("TEGANGAN KELAS 2 TINGGI");
}
if (message === lastMessage2) {
      // Skip duplicate message
      return;
      }
else{
  lastMessage2 = message;
  showToast(message,bgcolor);
}
}

let lastMessage3 =null;

function notifpower1(val){
if(val >= 1000 && val < 1200){
  message = ("BEBAN KELAS 1 \n HAMPIR PENUH!!");
  bgcolor = "rgba(171, 180, 0, 0.67)";
}else if(val >= 1200){
  message = ("BEBAN KELAS 1 PENUH!!");
  bgcolor = "rgba(180, 0, 0, 0.67)";
}
if (message === lastMessage3) {
      // Skip duplicate message
      return;
      }
else{
  lastMessage3 = message;
  showToast(message,bgcolor);
}
}

let lastMessage4 =null;

function notifpower2(val){
if(val >= 900 && val < 1000){
  message = ("BEBAN KELAS 2 HAMPIR PENUH!!");
  bgcolor = "rgba(171, 180, 0, 0.67)";
}else if(val >= 1000){
  message = ("BEBAN KELAS 2 PENUH!!");
  bgcolor = "rgba(180, 0, 0, 0.67)";
}
if (message === lastMessage4) {
      // Skip duplicate message
      return;
      }
else{
  lastMessage4 = message;
  showToast(message,bgcolor);
}
}

let lastMessage5 =null;

function notiftemp1(val){
if(voltage1 <= 200){
  bgcolor = "rgba(180, 0, 0, 0.67)";
  message = ("TEGANGAN KELAS 1 RENDAH");
}else if(voltage1 <= 240){
  bgcolor = "rgba(0, 180, 0, 0.67)";
  message = ("TEGANGAN KELAS 1 NORMAL");
}else if(voltage1 <= 999){
  bgcolor = "rgba(180, 0, 0, 0.67)";
  message = ("TEGANGAN KELAS 1 TINGGI");
}
if (message === lastMessage1) {
      // Skip duplicate message
      return;
      }
else{
  lastMessage1 = message;
  showToast(message,bgcolor);
}
}
let lastMessage6 =null;

function notiftemp2(val){
if(voltage1 <= 200){
  bgcolor = "rgba(180, 0, 0, 0.67)";
  message = ("TEGANGAN KELAS 1 RENDAH");
}else if(voltage1 <= 240){
  bgcolor = "rgba(0, 180, 0, 0.67)";
  message = ("TEGANGAN KELAS 1 NORMAL");
}else if(voltage1 <= 999){
  bgcolor = "rgba(180, 0, 0, 0.67)";
  message = ("TEGANGAN KELAS 1 TINGGI");
}
if (message === lastMessage1) {
      // Skip duplicate message
      return;
      }
else{
  lastMessage1 = message;
  showToast(message,bgcolor);
}
}
let lastMessage7 =null;

function notifhum1(val){
if(voltage1 <= 200){
  bgcolor = "rgba(180, 0, 0, 0.67)";
  message = ("TEGANGAN KELAS 1 RENDAH");
}else if(voltage1 <= 240){
  bgcolor = "rgba(0, 180, 0, 0.67)";
  message = ("TEGANGAN KELAS 1 NORMAL");
}else if(voltage1 <= 999){
  bgcolor = "rgba(180, 0, 0, 0.67)";
  message = ("TEGANGAN KELAS 1 TINGGI");
}
if (message === lastMessage1) {
      // Skip duplicate message
      return;
      }
else{
  lastMessage1 = message;
  showToast(message,bgcolor);
}
}
let lastMessage8 =null;

function notifhum2(val){
if(voltage1 <= 200){
  bgcolor = "rgba(180, 0, 0, 0.67)";
  message = ("TEGANGAN KELAS 1 RENDAH");
}else if(voltage1 <= 240){
  bgcolor = "rgba(0, 180, 0, 0.67)";
  message = ("TEGANGAN KELAS 1 NORMAL");
}else if(voltage1 <= 999){
  bgcolor = "rgba(180, 0, 0, 0.67)";
  message = ("TEGANGAN KELAS 1 TINGGI");
}
if (message === lastMessage1) {
      // Skip duplicate message
      return;
      }
else{
  lastMessage = message;
  showToast(message,bgcolor);
}
}
let lastMessage9 =null;

function notifuvt1(val){
if(val === "Detected"){
  bgcolor = "rgba(180, 0, 0, 0.67)";
  message = ("UV KELAS 1 TERDETEKSI");
}else{
  bgcolor = "rgba(0, 180, 0, 0.67)";
  message = ("UV KELAS 1 TIDAK TERDETEKSI");
}
if (message === lastMessage9) {
      // Skip duplicate message
      return;
      }
else{
  lastMessage9 = message;
  showToast(message,bgcolor);
}
}
let lastMessage10 =null;

function notifuv2(val){
if(val = "Detectetd"){
  bgcolor = "rgba(0, 180, 0, 0.67)";
  message = ("UV KELAS 2 TERDETEKSI \n MEMATIKAN LAMPU");
}else{
  bgcolor = "rgba(0, 180, 0, 0.67)";
  message = ("UV KELAS 2 TIDAK TERDETEKSI \n MENYALAKAN LAMPU");
}
if (message === lastMessage10) {
      // Skip duplicate message
      return;
      }
else{
  lastMessage10 = message;
  showToast(message,bgcolor);
}
}

function scaleValue(value,max) {
  return (value / max) * 100;
}

function voltStatus(value){
  if(value <= 200){
    return "TO LOW VOLTAGE";
  }else if(value <= 250){
    return "NORMAL";
  }else if(value <= 999){
    return "TO HIGH VOLTAGE";
  }
}

function voltColorStatus(value){
  if(value <= 200){
    return 'red';
  }else if(value <= 250){
    return 'green';
  }else if(value <= 999){
    return 'red';
  }
}

async function fetchdata() {
  try {
    const response = await fetch(apiUrl);
    const data = await response.json();
    //console.log(data);
    const voltage1 = parseFloat(data.smartmeter1.vi).toFixed(2);
    const ampere1 = parseFloat(data.smartmeter1.ii).toFixed(3);
    const power1 = parseFloat(data.smartmeter1.pi).toFixed(3);
    const voltage2 = parseFloat(data.smartmeter2.vi).toFixed(2);
    const ampere2 = parseFloat(data.smartmeter2.ii).toFixed(3);
    const power2 = parseFloat(data.smartmeter2.pi).toFixed(3);
    const temp1 = parseFloat(data.smartclass1.temperature).toFixed(1);
    const humi1 = parseFloat(data.smartclass1.humidity).toFixed(1);
    const people1 = parseInt(data.smartclass1.people_count);
    const uv1 = data.smartclass1.uv_status;
    const lamp1 = data.smartclass1.lamp_status;

    notifvolt1(voltage1);
    notifvolt2(voltage2);
    notifpower1(power1);
    notifpower2(power2);

    notifuvt1(uv1);

    voltageDisplay1.textContent = voltage1.padStart(5, '0');
    volt_status_1.textContent = voltStatus(voltage1);
    volt_status_1.style.color = voltColorStatus(voltage1);
    ampereDisplay1.textContent = ampere1.padStart(5, '0');
    amperePercentageDisplay1.textContent = "Load : " + parseFloat(scaleValue(ampere1, 10)).toFixed(2)+"%";
    amperePercentageDisplay2.textContent = "Load : " + parseFloat(scaleValue(ampere2, 10)).toFixed(2)+"%";  
    powerPercentageDisplay1.textContent = "Load : " + parseFloat(scaleValue(power1, 2000)).toFixed(2)+"%";
    powerPercentageDisplay2.textContent = "Load : " + parseFloat(scaleValue(power2, 2000)).toFixed(2)+"%";
    powerDisplay1.textContent = power1.padStart(5, '0');
    voltageDisplay2.textContent = voltage2.padStart(5, '0');
    volt_status_2.textContent = voltStatus(voltage2);
    volt_status_2.style.color = voltColorStatus(voltage2);
    ampereDisplay2.textContent = ampere2.padStart(5, '0');
    powerDisplay2.textContent = power2.padStart(5, '0');
    tempDisplay1.textContent= temp1.padStart(3,'0')+"°C";
    humDisplay1.textContent= humi1.padStart(3,'0')+"%";
    peopleDisplay1.textContent= people1;
    uvDisplay1.textContent= uv1;
    console.log(lamp1);
    lamp1Display.textContent = lamp1;

  } catch (error) {
    voltageDisplay1.textContent = "Err";
    console.error("Gagal mengambil data:", error);
  }
}

setInterval(fetchdata, 1000);
fetchdata();