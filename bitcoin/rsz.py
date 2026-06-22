#!/usr/bin/env python3
import subprocess
import hashlib
import json
import sys
import struct
import re

def sha256d(data):
    """Menghitung double SHA-256 dari data."""
    return hashlib.sha256(hashlib.sha256(data).digest()).digest()

def calculate_z_for_input(tx_hex, input_index, pubkey_hex):
    """
    Menghitung nilai Z untuk input tertentu pada transaksi P2PKH.
    Logika ini meniru fungsi getSignableTxn di tx_tools.js.
    """
    tx_bytes = bytes.fromhex(tx_hex)
    input_count = tx_bytes[4]  # Jumlah input (1 byte varint)
    
    # Parsing semua input
    offset = 10  # Mulai dari setelah "version + input_count"
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
    
    rest_tx = tx_hex[offset:]  # Sisa transaksi (outputs + locktime)
    
    # Bangun ulang transaksi untuk input yang ditentukan
    signable_tx = tx_hex[:10]  # version + input_count
    
    # Tambahkan semua input
    for j in range(input_count):
        # Tambahkan prev_tx_hash dan vout
        signable_tx += inputs[j]['hash'] + inputs[j]['vout']
        
        # Tentukan scriptSig untuk input ini
        if input_index == j:
            # Untuk input yang ditandatangani, gunakan scriptPubKey (P2PKH)
            pubkey_bytes = bytes.fromhex(pubkey_hex)
            pubkey_hash = hashlib.new('ripemd160', hashlib.sha256(pubkey_bytes).digest()).digest()
            pubkey_hash_hex = pubkey_hash.hex()
            # Format: OP_DUP OP_HASH160 <pubKeyHash> OP_EQUALVERIFY OP_CHECKSIG
            # 0x19 = 25 bytes (varint length)
            script_pubkey = '1976a914' + pubkey_hash_hex + '88ac'
            signable_tx += script_pubkey
        else:
            # Untuk input lain, scriptSig dikosongkan (0x00)
            signable_tx += '00'
        
        # Tambahkan sequence
        signable_tx += inputs[j]['sequence']
    
    # Tambahkan sisa transaksi (outputs + locktime) + SIGHASH_ALL (0x01000000)
    signable_tx += rest_tx + '01000000'
    
    # Hitung double SHA-256
    signable_bytes = bytes.fromhex(signable_tx)
    z = sha256d(signable_bytes).hex()
    
    return z

def extract_rs_from_script(script):
    """
    Ekstrak R dan S dari scriptSig (format DER).
    Menangani berbagai panjang R dan S.
    """
    try:
        # Cari R - pola 02 diikuti dengan panjang (20, 21, 1f, dll)
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
        
        # Ekstrak R
        r = script[r_pos+4:r_pos+4 + r_len*2]
        
        # Cari S - pola 02 berikutnya
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
        
        # Ekstrak S
        s = script[s_pos+4:s_pos+4 + s_len*2]
        
        return r, s
        
    except Exception as e:
        return None, None

def extract_pubkey_from_script(script):
    """
    Ekstrak public key dari scriptSig.
    Public key selalu di akhir scriptSig setelah signature.
    """
    try:
        # Cari pola 0141 (01 + 41 = push 65 bytes)
        if '0141' in script:
            pk_pos = script.find('0141')
            if len(script) >= pk_pos + 2 + 130:
                pubkey = script[pk_pos+2:pk_pos+2+130]
                if len(pubkey) == 130 and pubkey.startswith('04'):
                    return pubkey
        
        # Cari pola 41 di akhir script (tanpa 01 di depan)
        if '41' in script:
            pk_pos = script.rfind('41')
            # Pastikan bukan bagian dari 0141
            if pk_pos > 0 and script[pk_pos-2:pk_pos] != '01':
                if len(script) >= pk_pos + 2 + 130:
                    pubkey = script[pk_pos+2:pk_pos+2+130]
                    if len(pubkey) == 130 and pubkey.startswith('04'):
                        return pubkey
        
        # Fallback: cari 04 di akhir script
        pk_pos = script.rfind('04')
        if pk_pos != -1:
            if len(script) >= pk_pos + 130:
                pubkey = script[pk_pos:pk_pos+130]
                if len(pubkey) == 130:
                    remaining = script[pk_pos+130:]
                    if len(remaining) == 0 or remaining == '':
                        return pubkey
        
        # Scanning dari belakang
        for i in range(len(script) - 130, -1, -1):
            if script[i:i+2] == '04':
                candidate = script[i:i+130]
                if len(candidate) == 130:
                    remaining = script[i+130:]
                    if len(remaining) == 0 or remaining == '':
                        return candidate
        
        return None
        
    except Exception as e:
        return None

def get_rsz(txid):
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
        print(f"Error: Failed to get raw transaction. Make sure bitcoin-cli is configured correctly.")
        print(f"Error details: {e}")
        sys.exit(1)
    
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
        print(f"Error details: {e}")
        sys.exit(1)
    
    print(f'Transaction: {txid}')
    print(f'Total Inputs: {len(tx["vin"])}\n')
    print('=' * 80)
    
    # Loop untuk SETIAP input
    for i, vin in enumerate(tx['vin']):
        print(f'\nIndex {i}')
        
        # Cek apakah ada scriptSig
        if 'scriptSig' not in vin:
            print('  [SKIP] No scriptSig found (possibly SegWit or P2SH)')
            continue
        
        script = vin['scriptSig']['hex']
        
        # Ekstrak R dan S
        r, s = extract_rs_from_script(script)
        
        if r is None:
            print('  [ERROR] Failed to extract R')
            continue
        
        if s is None:
            print('  [ERROR] Failed to extract S')
            continue
        
        # Ekstrak PublicKey dari akhir script
        pubkey = extract_pubkey_from_script(script)
        
        if pubkey is None:
            print('  [ERROR] Failed to extract PublicKey')
            # Debug: tampilkan scriptSig
            print(f'  ScriptSig: {script}')
            continue
        
        # Hitung Z dengan metode yang benar
        z = calculate_z_for_input(raw, i, pubkey)
        
        # Tampilkan hasil
        print(f'R = {r}')
        print(f'S = {s}')
        print(f'Z = {z}')
        print(f'PublicKey = {pubkey}')
        
        print('-' * 40)

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print('Usage: python3 rsz.py <txid>')
        print('Example: python3 rsz.py aff3d33cfe98d2de1a590ef64680bdc316d6106245fca416f02aa31a3e23cdcd')
        sys.exit(1)
    
    txid = sys.argv[1]
    
    # Validasi TXID
    if len(txid) != 64 or not all(c in '0123456789abcdefABCDEF' for c in txid):
        print('Error: Invalid TXID format. Must be 64 hex characters.')
        sys.exit(1)
    
    get_rsz(txid)