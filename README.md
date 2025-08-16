# Bitcoin-Puzzle-71
Bitcoin Puzzle 71 - Auto Chunk Scanner
Distributed private key scanner dengan sistem chunk otomatis dari Google Sheets 

Alat berbasis web untuk memindai private key Bitcoin secara terdistribusi demi memecahkan Bitcoin Puzzle 71, dengan alamat target:
1PWo3JeB9jrGwfHDNpdGK54CRas7fsVzXU

Proyek ini memungkinkan banyak pengguna berpartisipasi secara bersamaan tanpa tumpang tindih, karena setiap worker otomatis mendapatkan chunk unik dari Google Sheets

## Fitur Utama
- Otomatis ambil chunk dari Google Sheets
- Zero duplication – setiap chunk hanya dipindai sekali
- Pause & Resume – bisa dihentikan dan dilanjutkan kapan saja
- Worker ID lokal – disimpan di localStorage
- Progress real-time – progress bar & log aktivitas
- Offline-ready – bisa dijalankan tanpa internet (setelah load pertama)
- Open & kolaboratif – cocok untuk proyek komunitas

## Struktur Proyek
```
/bitcoin-puzzle-71-scanner
├── index.html              # UI utama
├── css/style.css           # Styling
├── js/
│   ├── utils.js            # Fungsi helper (Base58, pubkey, etc)
│   ├── state.js            # Manajemen state & log
│   ├── scanner.js          # Logika scan & komunikasi sheet
│   └── main.js             # Inisialisasi & event handler
└── libs/
    ├── elliptic.min.js     # Library ECDSA (secp256k1)
    └── crypto-js.min.js    # Library kriptografi (SHA256, RIPEMD160)
```
    
    
## Setup Google Sheets (Backend)
Proyek ini menggunakan Google Apps Script sebagai backend untuk mengelola chunk. Kamu perlu:

- Buat Google Sheet berisi daftar range start-end (dalam hex atau desimal).
- Buat Google Apps Script yang mengekspor endpoint:
- GET /getChunk?workerId=... → ambil chunk belum diproses
- POST /markDone → tanda chunk selesai
- Ganti URL di scanner.js:
- const GOOGLE_SCRIPT_URL = 'https://script.google.com/macros/s/XXXXXXXXXXXXXXXXXXXXX/exec';

## Cara Menggunakan
- Clone atau download repositori ini
- Download library:
- elliptic.min.js
- crypto-js.min.js
- Simpan di folder libs/
- Buka index.html di browser
- Klik "Ambil Chunk & Mulai"
- Tunggu atau lanjutkan nanti

 ## Catatan Penting
- Script ini hanya untuk edukasi dan eksperimen kriptografi
- Tidak menjamin penemuan private key
- Gunakan dengan bijak dan bertanggung jawab

## Kontribusi
Kamu bisa berkontribusi dengan:

- Menambah fitur (export log, dark mode, dll)
- Memperbaiki performa scan
- Membuat versi mobile-friendly
- Menyediakan server chunk terpusat

  ## Lisensi
Proyek ini bebas digunakan, dimodifikasi, dan didistribusikan.
