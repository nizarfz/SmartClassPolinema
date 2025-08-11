# SmartClassPolinema
sistem monitoring kondisi kelas IoT yang terintegrasi dari berberapa perangkat embedded lainya

Harware :
- Raspberry PI 4 (hardware for TV client and server)
- X86 (backend and webserver)

Instalasi
1. Install Postgresql
2. Buat akun dan database
3. masuk viretual environment python
4. install requirement.txt pada folder hardware dan backend
5. jalankan program backend dengan perintah : uvicorn --host 0.0.0.0 --port 8000 --reload
6. jika tidak terjadi error bisa langsung menambahkan script backend dan hardware ke systemctl agar berjalan otomatis
7. Install webserver (Apache2 or Nginx etc)
8. Arahkan akses web ke folder web
