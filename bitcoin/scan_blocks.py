#!/usr/bin/env python3
import subprocess
import json
import sys
import time
from datetime import datetime
import mysql.connector
from mysql.connector import Error

# Import fungsi dari rsz2.py
from rsz2 import get_rsz, DB_CONFIG

def get_block_hash(height):
    """Mendapatkan hash blok dari height."""
    try:
        block_hash = subprocess.check_output([
            './bitcoin-cli',
            '-datadir=/home/codespace/.bitcoin-analisis',
            'getblockhash',
            str(height)
        ]).decode().strip()
        return block_hash
    except subprocess.CalledProcessError as e:
        print(f"Error getting block hash for height {height}: {e}")
        return None

def get_block_info(height):
    """Mendapatkan informasi blok."""
    try:
        block_hash = get_block_hash(height)
        if not block_hash:
            return None, None
        
        block = json.loads(subprocess.check_output([
            './bitcoin-cli',
            '-datadir=/home/codespace/.bitcoin-analisis',
            'getblock',
            block_hash
        ]).decode())
        
        block_time = datetime.fromtimestamp(block['time']).strftime('%Y-%m-%d %H:%M:%S')
        return block['tx'], block_time
    except subprocess.CalledProcessError as e:
        print(f"Error getting block info for height {height}: {e}")
        return None, None

def scan_blocks(start_block, end_block):
    """Memindai range blok dan menyimpan ke database."""
    
    total_tx = 0
    success_tx = 0
    failed_tx = 0
    
    print(f"Starting scan from block {start_block} to {end_block}")
    print("=" * 60)
    
    start_time = time.time()
    
    for height in range(start_block, end_block + 1):
        print(f"\nProcessing block {height}...")
        
        tx_list, block_time = get_block_info(height)
        if not tx_list:
            print(f"  [SKIP] Failed to get block {height}")
            continue
        
        print(f"  Transactions: {len(tx_list)}")
        
        for idx, txid in enumerate(tx_list):
            print(f"    [{idx+1}/{len(tx_list)}] TXID: {txid[:16]}...", end=" ", flush=True)
            
            try:
                result = get_rsz(txid, height, block_time)
                if result:
                    success_tx += 1
                    print("✓")
                else:
                    failed_tx += 1
                    print("✗ (no input)")
            except Exception as e:
                failed_tx += 1
                print(f"✗ ({str(e)[:30]})")
            
            total_tx += 1
        
        # Update scan_results setelah setiap blok
        update_scan_results(height, total_tx, success_tx, failed_tx)
    
    elapsed = time.time() - start_time
    print("\n" + "=" * 60)
    print(f"SCAN COMPLETED!")
    print(f"  Blocks scanned: {end_block - start_block + 1}")
    print(f"  Total transactions: {total_tx}")
    print(f"  Successfully saved: {success_tx}")
    print(f"  Failed/skipped: {failed_tx}")
    print(f"  Time elapsed: {elapsed:.2f} seconds")

def update_scan_results(block_height, total_tx, success_tx, failed_tx):
    """Update tabel scan_results."""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        cursor.execute("""
            INSERT INTO scan_results (notes, total_transactions, vulnerable_inputs)
            VALUES (%s, %s, %s)
        """, (f"Up to block {block_height}", total_tx, 0))
        
        conn.commit()
    except Error as e:
        print(f"Database error: {e}")
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

def get_latest_scanned_block():
    """Mendapatkan block terakhir yang sudah dipindai."""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        cursor.execute("""
            SELECT MAX(block_height) FROM transactions
        """)
        result = cursor.fetchone()[0]
        return result if result else 0
        
    except Error as e:
        print(f"Database error: {e}")
        return 0
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print('Usage: python3 scan_blocks.py <start_block> <end_block>')
        print('Example: python3 scan_blocks.py 1 1000')
        print('Or scan from last scanned: python3 scan_blocks.py 0')
        sys.exit(1)
    
    if len(sys.argv) == 2 and sys.argv[1] == '0':
        # Scan dari block terakhir yang sudah dipindai
        start = get_latest_scanned_block() + 1
        print(f"Resuming from block {start}")
        # Ambil blok terbaru
        try:
            latest = int(subprocess.check_output([
                './bitcoin-cli',
                '-datadir=/home/codespace/.bitcoin-analisis',
                'getblockcount'
            ]).decode().strip())
            end = latest
        except:
            end = start + 1000
        scan_blocks(start, end)
    else:
        
        start = int(sys.argv[1])
        end = int(sys.argv[2])
        scan_blocks(start, end)