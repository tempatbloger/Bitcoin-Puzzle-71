// ============================================
// VANITY2.JS - PENCARIAN BERBASIS HASH160
// ============================================

const EC = require('elliptic').ec;
const CryptoJS = require('crypto-js');

const ec = new EC('secp256k1');

// Base58 alphabet
const B58 = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

// ============================================
// BASE58 ENCODE
// ============================================

function base58encode(bytes) {
    let zeros = 0;
    while (zeros < bytes.length && bytes[zeros] === 0) zeros++;
    let digits = [0];
    for (let i = zeros; i < bytes.length; i++) {
        let carry = bytes[i];
        for (let j = 0; j < digits.length; j++) {
            carry += digits[j] * 256;
            digits[j] = carry % 58;
            carry = Math.floor(carry / 58);
        }
        while (carry) {
            digits.push(carry % 58);
            carry = Math.floor(carry / 58);
        }
    }
    let out = "1".repeat(zeros);
    for (let k = digits.length - 1; k >= 0; k--) out += B58[digits[k]];
    return out;
}

function hexToBytes(hex) {
    if (hex.length % 2) hex = '0' + hex;
    const a = [];
    for (let i = 0; i < hex.length; i += 2) {
        a.push(parseInt(hex.slice(i, i + 2), 16));
    }
    return new Uint8Array(a);
}

// ============================================
// ALAMAT → HASH160
// ============================================

function base58Decode(address) {
    let num = BigInt(0);
    for (let i = 0; i < address.length; i++) {
        const value = B58.indexOf(address[i]);
        if (value === -1) throw new Error(`Invalid Base58 character: ${address[i]}`);
        num = num * 58n + BigInt(value);
    }
    let bytes = [];
    while (num > 0) {
        bytes.unshift(Number(num % 256n));
        num = num / 256n;
    }
    for (let i = 0; i < address.length; i++) {
        if (address[i] === '1') {
            bytes.unshift(0);
        } else {
            break;
        }
    }
    return bytes;
}

function addressToHash160(address) {
    const bytes = base58Decode(address);
    if (bytes.length !== 25) {
        throw new Error(`Invalid address length: ${bytes.length} bytes (should be 25)`);
    }
    const hash160Bytes = bytes.slice(1, 21);
    let hash160 = '';
    for (let i = 0; i < hash160Bytes.length; i++) {
        hash160 += hash160Bytes[i].toString(16).padStart(2, '0');
    }
    return hash160;
}

// ============================================
// PUBLIC KEY → HASH160 (TANPA BASE58!)
// ============================================

function pubkeyToHash160(pubHex) {
    const pubWA = CryptoJS.enc.Hex.parse(pubHex);
    const sha = CryptoJS.SHA256(pubWA);
    const ripe = CryptoJS.RIPEMD160(sha);
    return CryptoJS.enc.Hex.stringify(ripe);
}

// ============================================
// PUBLIC KEY → ALAMAT
// ============================================

function pubkeyToP2PKH(pubHex) {
    const hash160 = pubkeyToHash160(pubHex);
    const verPayloadHex = "00" + hash160;
    const verWA = CryptoJS.enc.Hex.parse(verPayloadHex);
    const checksum = CryptoJS.SHA256(CryptoJS.SHA256(verWA));
    const chkHex = CryptoJS.enc.Hex.stringify(checksum).slice(0, 8);
    const finalHex = verPayloadHex + chkHex;
    return base58encode(hexToBytes(finalHex));
}

// ============================================
// FUNGSI UTAMA: CARI BERDASARKAN ALAMAT
// ============================================

function findVanityAddress(targetAddress, count = 1) {
    console.log(`🔍 Mencari alamat: ${targetAddress}\n`);
    
    // 1. Konversi target alamat ke Hash160
    const targetHash160 = addressToHash160(targetAddress);
    console.log(`📌 Target Hash160: ${targetHash160}`);
    console.log(`📌 Panjang: ${targetHash160.length} hex (20 byte)\n`);
    
    const results = [];
    let pubKeyInt = 1;
    let attempts = 0;
    let startTime = Date.now();
    
    while (results.length < count) {
        attempts++;
        
        const xHex = pubKeyInt.toString(16).padStart(64, '0');
        const pubKey = '02' + xHex;
        
        // Hitung Hash160 (cepat!)
        const hash160 = pubkeyToHash160(pubKey);
        
        if (hash160 === targetHash160) {
            const address = pubkeyToP2PKH(pubKey);
            results.push({ pubKey, hash160, address });
            console.log(`✅ #${results.length} ditemukan!`);
            console.log(`   Pubkey : ${pubKey}`);
            console.log(`   Hash160: ${hash160}`);
            console.log(`   Address: ${address}\n`);
            break;
        }
        
        pubKeyInt++;
        
        if (attempts % 100000 === 0) {
            const elapsed = ((Date.now() - startTime) / 1000);
            console.log(`📊 ${attempts.toLocaleString()} attempts... (${elapsed.toFixed(1)}s)`);
        }
    }
    
    const totalWaktu = (Date.now() - startTime) / 1000;
    console.log(`\n🎉 Selesai! ${results.length} alamat ditemukan dalam ${totalWaktu.toFixed(2)} detik`);
    console.log(`📊 Total attempts: ${attempts.toLocaleString()}`);
    console.log(`📊 Hash rate: ${(attempts / totalWaktu).toFixed(0)} hash/s\n`);
    
    return results;
}

// ============================================
// JALANKAN
// ============================================

const target = process.argv[2] || '1PWo3CV7wXnc3pPWVdbWYKKvPZUcBtLjsL';

console.log(`🚀 Vanity2.js - Pencarian Berbasis Hash160`);
console.log(`   Target Address: ${target}\n`);

const results = findVanityAddress(target, 1);

if (results.length > 0) {
    console.log('📋 HASIL:');
    console.log(`   Address : ${results[0].address}`);
    console.log(`   Hash160 : ${results[0].hash160}`);
    console.log(`   Pubkey  : ${results[0].pubKey}`);
    console.log(`   x       : ${parseInt(results[0].pubKey.slice(2), 16).toLocaleString()}`);
}