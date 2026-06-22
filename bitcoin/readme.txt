jalankan node 

./bitcoind -datadir=/home/codespace/.bitcoin-analisis -conf=/home/codespace/.bitcoin-analisis/bitcoin.conf -daemon

================================================================================

1. EXTRACT SINGLE TRANSACTION
   python3 rsz.py <txid>
   
   Example:
   python3 rsz.py aff3d33cfe98d2de1a590ef64680bdc316d6106245fca416f02aa31a3e23cdcd

2. PROCESS BLOCKS (BATCH)
   python3 rsz_block_processor.py process <start> <end>
   
   Example:
   python3 rsz_block_processor.py process 170000 171000

3. VIEW STATISTICS
   python3 rsz_block_processor.py stats

4. DETECT NONCE REUSE
   python3 rsz_block_processor.py reuse

5. VIEW SAMPLE DATA
   python3 rsz_block_processor.py sample <number>
   
   Example:
   python3 rsz_block_processor.py sample 5

📁 FILES
================================================================================
rsz.py                    - Single transaction extractor
rsz_block_processor.py    - Batch processor
rsz_blocks.db             - Database (auto-created)

⚠️  REQUIREMENTS
================================================================================
- Python 3
- bitcoin-cli with synced node
- Datadir: /home/codespace/.bitcoin-analisis

🔍 EXAMPLE OUTPUT
================================================================================
Transaction: aff3d33cfe98d2de1a590ef64680bdc316d6106245fca416f02aa31a3e23cdcd
Total Inputs: 4

Index 0
R = 30a70f628990d4079fd4ce149e064728...
S = 252b9f3491d3eb138f955aacabf76dce...
Z = c63d6fd7d197eb845c5e3ef62bd5fea5...
PublicKey = 04b010b2ad77329c3457bcbf9d75...
---

v2 ---  
=== buat tabel ===
CREATE DATABASE bitcoin_analysis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bitcoin_analysis;

CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    txid VARCHAR(64) UNIQUE NOT NULL,
    block_height INT,
    block_time DATETIME,
    total_inputs INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_txid (txid),
    INDEX idx_block_height (block_height)
);

CREATE TABLE transaction_inputs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tx_id INT NOT NULL,
    input_index INT NOT NULL,
    r VARCHAR(64) NOT NULL,
    s VARCHAR(64) NOT NULL,
    z VARCHAR(64) NOT NULL,
    public_key VARCHAR(130) NOT NULL,
    address VARCHAR(34),
    is_vulnerable BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tx_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_r (r),
    INDEX idx_public_key (public_key),
    INDEX idx_address (address),
    INDEX idx_vulnerable (is_vulnerable),
    UNIQUE KEY unique_tx_input (tx_id, input_index)
);

CREATE TABLE scan_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scan_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_transactions INT,
    vulnerable_inputs INT,
    notes TEXT
);

USE bitcoin_analysis;
ALTER TABLE transaction_inputs MODIFY r VARCHAR(100);
ALTER TABLE transaction_inputs MODIFY s VARCHAR(100);
ALTER TABLE transaction_inputs MODIFY z VARCHAR(100);
ALTER TABLE transaction_inputs MODIFY public_key VARCHAR(150);

=== menjalankan mysql ==
sudo service mysql start
sudo service mysql status
sudo mysql <-- masuk ke mysql

=== melihat tabel blok terakhir ==
 sudo mysql -e "USE bitcoin_analysis; SELECT MAX(block_height) AS latest_block, COUNT(DISTINCT block_height) AS total_blocks, COUNT(*) AS total_transactions FROM transactions;"
 
 === perintah scan: ==
 python3 scan_blocks.py 170000 175000
 python3 scan_blocks.py 0  <== dari blok terakhir

 =====      Cek apakah ada duplikasi R        =====
 sudo mysql -e "USE bitcoin_analysis; SELECT r, COUNT(*) AS count, GROUP_CONCAT(DISTINCT txid) AS txids FROM transaction_inputs ti JOIN transactions t ON ti.tx_id = t.id GROUP BY r HAVING COUNT(*) > 1 LIMIT 10;"

=====   cek semua transaksi dengan R yang sama  =====
sudo mysql -e "USE bitcoin_analysis; SELECT t.txid, ti.input_index, ti.r, ti.s, ti.z, ti.public_key FROM transaction_inputs ti JOIN transactions t ON ti.tx_id = t.id WHERE ti.r = 'd47ce4c025c35ec440bc81d99834a624875161a26bf56ef7fdc0f5d52f843ad1';"

=====     =====

