========================================
  VANITY.CPP - CARA MENJALANKAN
========================================

1. COMPILE (cukup 1 kali):
   g++ -std=c++17 -O3 -march=native -pthread vanity.cpp -lssl -lcrypto -o vanity

2. JALANKAN:
   ./vanity [ALAMAT] [THREAD]

   Contoh:
   ./vanity 1PWo3CV7wXnc3pPWVdbWYKKvPZUcBtLjsL 1
   ./vanity 1PWo3CV7wXnc3pPWVdbWYKKvPZUcBtLjsL 2
   ./vanity 1PWo3CV7wXnc3pPWVdbWYKKvPZUcBtLjsL 4

3. HASIL:
   - Akan menampilkan x, pubkey, hash160, dan alamat
   - Progress setiap 100.000 attempts
   - Hash rate real-time

========================================
