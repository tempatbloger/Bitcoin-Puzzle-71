konfigurasi
mkdir -p ~/.bitcoin
nano ~/.bitcoin/bitcoin.conf

isi
server=1
daemon=1
txindex=1
listen=1
rpcuser=bitcoinrpc
rpcpassword=ubah_password_ini
rpcallowip=127.0.0.1
rpcport=8332
dbcache=4096
maxconnections=40

====   Stop dan mulai   ==========
./bitcoin-cli -datadir=/home/ubuntu/.bitcoin stop
./bitcoind -datadir=/home/ubuntu/.bitcoin -daemon

===== Untuk monitor progress: ===
watch -n 30 './bitcoin-cli -datadir=/home/ubuntu/.bitcoin getblockcount'












