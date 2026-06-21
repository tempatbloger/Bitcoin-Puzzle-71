// ============================================
// VANITY ADDRESS FINDER - CODESPACES
// ============================================

const EC = require('elliptic').ec;
const CryptoJS = require('crypto-js');

const ec = new EC('secp256k1');

// Base58 alphabet
const B58 = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

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

function pubkeyToP2PKH(pubHex) {
    const pubWA = CryptoJS.enc.Hex.parse(pubHex);
    const sha = CryptoJS.SHA256(pubWA);
    const ripe = CryptoJS.RIPEMD160(sha);
    const verPayloadHex = "00" + CryptoJS.enc.Hex.stringify(ripe);
    const verWA = CryptoJS.enc.Hex.parse(verPayloadHex);
    const checksum = CryptoJS.SHA256(CryptoJS.SHA256(verWA));
    const chkHex = CryptoJS.enc.Hex.stringify(checksum).slice(0, 8);
    const finalHex = verPayloadHex + chkHex;
    return base58encode(hexToBytes(finalHex));
}

function findVanityAddresses(prefix, count = 10) {
    console.log(`🔍 Mencari ${count} alamat dengan awalan "${prefix}"\n`);
    
    const results = [];
    let pubKeyInt = 1;
    let attempts = 0;
    let startTime = Date.now();
    
    while (results.length < count) {
        attempts++;
        
        const xHex = pubKeyInt.toString(16).padStart(64, '0');
        const pubKey = '02' + xHex;
        const address = pubkeyToP2PKH(pubKey);
        
        if (address.startsWith(prefix)) {
            results.push({ pubKey, address });
            console.log(`✅ #${results.length} ditemukan!`);
            console.log(`   Pubkey : ${pubKey}`);
            console.log(`   Address: ${address}\n`);
        }
        
        pubKeyInt++;
        
        if (attempts % 100000 === 0) {
            const elapsed = ((Date.now() - startTime) / 1000);
            console.log(`📊 ${attempts.toLocaleString()} attempts... (${elapsed.toFixed(1)}s, ${results.length} ditemukan)`);
        }
    }
    
    const totalWaktu = (Date.now() - startTime) / 1000;
    console.log(`\n🎉 Selesai! ${results.length} alamat ditemukan dalam ${totalWaktu.toFixed(2)} detik`);
    console.log(`📊 Total attempts: ${attempts.toLocaleString()}\n`);
    
    console.log('📋 DAFTAR ALAMAT:');
    results.forEach((r, i) => {
        console.log(`${i+1}. ${r.address}`);
    });
    
    return results;
}

// ============================================
// JALANKAN
// ============================================

const prefix = process.argv[2] || '1PW';
const count = parseInt(process.argv[3]) || 10;

console.log(`🚀 Starting vanity address search...`);
console.log(`   Prefix: ${prefix}`);
console.log(`   Count : ${count}\n`);

findVanityAddresses(prefix, count);