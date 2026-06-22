#!/usr/bin/env python3
import subprocess
import hashlib
import json
import sys
import struct
import re
import mysql.connector
from mysql.connector import Error
from datetime import datetime

# Konfigurasi Database
DB_CONFIG = {
    'host': 'localhost',
    'database': 'bitcoin_analysis',
    'user': 'root',
    'password': ''
}

def sha256d(data):
    """Menghitung double SHA-256 dari data."""
    return hashlib.sha256(hashlib.sha256(data).digest()).digest()

def calculate_z_for_input(tx_hex, input_index, pubkey_hex):
    """
    Menghitung nilai Z untuk input tertentu pada transaksi P2PKH.
    """
    tx_bytes = bytes.fromhex(tx_hex)
    input_count = tx_bytes[4]
    
    offset = 10
    inputs = []
    
    for i in range(input_count):
        prev_tx_hash = tx_hex[offset:offset+64]
        offset += 64
        prev_tx_vout = tx_hex[offset:offset+8]
        offset += 8
        script_len = int(tx_hex[offset:offset+2], 16)
        offset += 2
        script_sig = tx_hex[offset:offset + script_len*2]
        offset += script_len*2
        sequence = tx_hex[offset:offset+8]
        offset += 8
        inputs.append({
            'hash': prev_tx_hash,
            'vout': prev_tx_vout,
            'scriptSig': script_sig,
            'sequence': sequence
        })
    
    rest_tx = tx_hex[offset:]
    
    signable_tx = tx_hex[:10]
    
    for j in range(input_count):
        signable_tx += inputs[j]['hash'] + inputs[j]['vout']
        
        if input_index == j:
            pubkey_bytes = bytes.fromhex(pubkey_hex)
            pubkey_hash = hashlib.new('ripemd160', hashlib.sha256(pubkey_bytes).digest()).digest()
            pubkey_hash_hex = pubkey_hash.hex()
            script_pubkey = '1976a914' + pubkey_hash_hex + '88ac'
            signable_tx += script_pubkey
        else:
            signable_tx += '00'
        
        signable_tx += inputs[j]['sequence']
    
    signable_tx += rest_tx + '01000000'
    signable_bytes = bytes.fromhex(signable_tx)
    z = sha256d(signable_bytes).hex()
    
    return z

def extract_rs_from_script(script):
    """Ekstrak R dan S dari scriptSig."""
    try:
        r_pos = -1
        r_len = 0
        
        for i in range(len(script) - 2):
            if script[i:i+2] == '02':
                if i+2 < len(script):
                    len_byte = script[i+2:i+4]
                    if len_byte in ['20', '21', '1f']:
                        r_pos = i
                        r_len = int(len_byte, 16)
                        break
        
        if r_pos == -1:
            return None, None
        
        r = script[r_pos+4:r_pos+4 + r_len*2]
        
        s_start = r_pos + 4 + r_len*2
        s_pos = -1
        s_len = 0
        
        for i in range(s_start, len(script) - 2):
            if script[i:i+2] == '02':
                if i+2 < len(script):
                    len_byte = script[i+2:i+4]
                    if len_byte in ['20', '21', '1f']:
                        s_pos = i
                        s_len = int(len_byte, 16)
                        break
        
        if s_pos == -1:
            return r, None
        
        s = script[s_pos+4:s_pos+4 + s_len*2]
        
        return r, s
        
    except Exception as e:
        return None, None

def extract_pubkey_from_script(script):
    """Ekstrak public key dari scriptSig."""
    try:
        if '0141' in script:
            pk_pos = script.find('0141')
            if len(script) >= pk_pos + 2 + 130:
                pubkey = script[pk_pos+2:pk_pos+2+130]
                if len(pubkey) == 130 and pubkey.startswith('04'):
                    return pubkey
        
        if '41' in script:
            pk_pos = script.rfind('41')
            if pk_pos > 0 and script[pk_pos-2:pk_pos] != '01':
                if len(script) >= pk_pos + 2 + 130:
                    pubkey = script[pk_pos+2:pk_pos+2+130]
                    if len(pubkey) == 130 and pubkey.startswith('04'):
                        return pubkey
        
        pk_pos = script.rfind('04')
        if pk_pos != -1:
            if len(script) >= pk_pos + 130:
                pubkey = script[pk_pos:pk_pos+130]
                if len(pubkey) == 130:
                    return pubkey
        
        for i in range(len(script) - 130, -1, -1):
            if script[i:i+2] == '04':
                candidate = script[i:i+130]
                if len(candidate) == 130:
                    return candidate
        
        return None
        
    except Exception as e:
        return None

def address_from_pubkey(pubkey):
    """Generate address dari public key."""
    try:
        pubkey_bytes = bytes.fromhex(pubkey)
        sha = hashlib.sha256(pubkey_bytes).digest()
        ripe = hashlib.new('ripemd160', sha).digest()
        # Versi 0x00 untuk mainnet
        versioned = b'\x00' + ripe
        checksum = hashlib.sha256(hashlib.sha256(versioned).digest()).digest()[:4]
        address = versioned + checksum
        # Base58 encoding sederhana
        import base58
        return base58.b58encode(address).decode()
    except:
        return None

def check_vulnerability(cursor, r, public_key):
    """Cek apakah R sudah pernah digunakan dengan public key yang sama."""
    cursor.execute("""
        SELECT COUNT(*) FROM transaction_inputs ti
        JOIN transactions t ON ti.tx_id = t.id
        WHERE ti.r = %s AND ti.public_key = %s
    """, (r, public_key))
    count = cursor.fetchone()[0]
    return count > 0

def save_to_database(txid, block_height, block_time, inputs_data):
    """Menyimpan hasil ekstraksi ke database."""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        # Insert transaksi
        cursor.execute("""
            INSERT INTO transactions (txid, block_height, block_time, total_inputs)
            VALUES (%s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE 
                block_height = VALUES(block_height),
                block_time = VALUES(block_time),
                total_inputs = VALUES(total_inputs)
        """, (txid, block_height, block_time, len(inputs_data)))
        
        # Ambil ID transaksi
        cursor.execute("SELECT id FROM transactions WHERE txid = %s", (txid,))
        tx_id = cursor.fetchone()[0]
        
        # Insert input
        for idx, data in enumerate(inputs_data):
            is_vuln = check_vulnerability(cursor, data['r'], data['public_key'])
            
            cursor.execute("""
                INSERT INTO transaction_inputs 
                (tx_id, input_index, r, s, z, public_key, address, is_vulnerable)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                    r = VALUES(r), s = VALUES(s), z = VALUES(z),
                    public_key = VALUES(public_key), 
                    address = VALUES(address),
                    is_vulnerable = VALUES(is_vulnerable)
            """, (
                tx_id, idx, data['r'], data['s'], data['z'],
                data['public_key'], data.get('address'), is_vuln
            ))
        
        conn.commit()
        print(f"✓ Data saved for TXID: {txid}")
        return True
        
    except Error as e:
        print(f"Database error: {e}")
        return False
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

def get_rsz(txid, block_height=None, block_time=None):
    """Fungsi utama untuk mengekstrak R, S, Z, dan PublicKey dari transaksi."""
    
    # Ambil raw transaction
    try:
        raw = subprocess.check_output([
            './bitcoin-cli',
            '-datadir=/home/codespace/.bitcoin-analisis',
            'getrawtransaction',
            txid
        ]).decode().strip()
    except subprocess.CalledProcessError as e:
        print(f"Error: Failed to get raw transaction.")
        return False
    
    # Ambil detail transaksi
    try:
        tx = json.loads(subprocess.check_output([
            './bitcoin-cli',
            '-datadir=/home/codespace/.bitcoin-analisis',
            'getrawtransaction',
            txid,
            '1'
        ]).decode())
    except subprocess.CalledProcessError as e:
        print(f"Error: Failed to get transaction details.")
        return False
    
    # Jika block_height dan block_time tidak diberikan, ambil dari data
    if block_height is None and 'blockheight' in tx:
        block_height = tx.get('blockheight')
    if block_time is None and 'blocktime' in tx:
        block_time = datetime.fromtimestamp(tx.get('blocktime', 0)).strftime('%Y-%m-%d %H:%M:%S')
    
    inputs_data = []
    
    for i, vin in enumerate(tx['vin']):
        if 'scriptSig' not in vin:
            continue
        
        script = vin['scriptSig']['hex']
        
        # Ekstrak R dan S
        r, s = extract_rs_from_script(script)
        if r is None or s is None:
            continue
        
        # Ekstrak PublicKey
        pubkey = extract_pubkey_from_script(script)
        if pubkey is None:
            continue
        
        # Hitung Z
        z = calculate_z_for_input(raw, i, pubkey)
        
        # Generate address
        address = address_from_pubkey(pubkey)
        
        inputs_data.append({
            'r': r,
            's': s,
            'z': z,
            'public_key': pubkey,
            'address': address
        })
    
    if inputs_data:
        # Simpan ke database
        save_to_database(txid, block_height, block_time, inputs_data)
        return True
    
    return False

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print('Usage: python3 rsz2.py <txid>')
        print('Example: python3 rsz2.py aff3d33cfe98d2de1a590ef64680bdc316d6106245fca416f02aa31a3e23cdcd')
        sys.exit(1)
    
    txid = sys.argv[1]
    
    if len(txid) != 64 or not all(c in '0123456789abcdefABCDEF' for c in txid):
        print('Error: Invalid TXID format.')
        sys.exit(1)
    
    get_rsz(txid)