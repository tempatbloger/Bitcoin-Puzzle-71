import ecdsa
import hashlib
import base58
import multiprocessing

# Target: 1NBC8uXJy1GiJ6drkiZa1WuKn51ps7EPTv
TARGET_ADDRESS = "1NBC8uXJy1GiJ6drkiZa1WuKn51ps7EPTv"

def get_address_and_pubkey(private_key_hex):
    # 1. Private Key ke Public Key
    priv_key_bytes = bytes.fromhex(private_key_hex.zfill(64))
    sk = ecdsa.SigningKey.from_string(priv_key_bytes, curve=ecdsa.SECP256k1)
    
    # Menghasilkan Pubkey (Compressed - Standar Bitcoin modern)
    pub_key_bytes = sk.verifying_key.to_string("compressed")
    pub_key_hex = pub_key_bytes.hex()
    
    # 2. Hashing Pubkey menjadi Alamat
    sha256 = hashlib.sha256(pub_key_bytes).digest()
    ripemd160 = hashlib.new('ripemd160', sha256).digest()
    
    network_byte = b'\x00' + ripemd160
    checksum = hashlib.sha256(hashlib.sha256(network_byte).digest()).digest()[:4]
    address = base58.b58encode(network_byte + checksum).decode()
    
    return address, pub_key_hex

# Cara memanggilnya:
addr, pk = get_address_and_pubkey("0000000000000000000000000000000000000000000000000000000000000001")
print(f"Alamat: {addr}")
print(f"Pubkey: {pk}")