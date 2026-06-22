#!/usr/bin/env python3
# recover_key.py

import sys

def recover_private_key(r, s1, s2, z1, z2):
    # p adalah order dari curve secp256k1
    p = 0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141
    
    # Konversi string hex ke integer
    r_int = int(r, 16)
    s1_int = int(s1, 16)
    s2_int = int(s2, 16)
    z1_int = int(z1, 16)
    z2_int = int(z2, 16)
    
    # Hitung private key: (z1*s2 - z2*s1) / (r*(s1-s2))
    numerator = (z1_int * s2_int - z2_int * s1_int) % p
    denominator = (r_int * (s1_int - s2_int)) % p
    
    # Inverse denominator
    private_key = (numerator * pow(denominator, -1, p)) % p
    
    return hex(private_key)

# Data dari transaksi 9ec4bc49... (input 0 dan 1)
r = "d47ce4c025c35ec440bc81d99834a624875161a26bf56ef7fdc0f5d52f843ad1"
s1 = "44e1ff2dfd8102cf7a47c21d5c9fd5701610d04953c6836596b4fe9dd2f53e3e"
s2 = "9a5f1c75e461d7ceb1cf3cab9013eb2dc85b6d0da8c3c6e27e3a5a5b3faa5bab"
z1 = "c0e2d0a89a348de88fda08211c70d1d7e52ccef2eb9459911bf977d587784c6e"
z2 = "17b0f41c8c337ac1e18c98759e83a8cccbc368dd9d89e5f03cb633c265fd0ddc"

private_key = recover_private_key(r, s1, s2, z1, z2)
print(f"Private Key: {private_key}")
print(f"Private Key (no 0x): {private_key[2:]}")

# Cek menggunakan pasangan lain
print("\nVerifikasi dengan pasangan lain (4a85d9c8...):")
s1 = "12a8c1d5c602e382c178fbfcb957e8ecc347f1baf78a206f20a97ff4c433e146"
z1 = "475322c8d29bc30d4d55ca9e1620d9aacbfbe3f474f8577efc31bd81a0f6855d"
private_key2 = recover_private_key(r, s1, s2, z1, z2)
print(f"Private Key: {private_key2}")