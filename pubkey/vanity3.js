// ============================================
// VANITY3.JS - WORKER THREADS (MULTI-CORE)
// ============================================

const { Worker, isMainThread, parentPort, workerData } = require('worker_threads');
const CryptoJS = require('crypto-js');
const os = require('os');

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

// ============================================
// HASH160 (SAMA SEPERTI VANITY2)
// ============================================

function pubkeyToHash160(pubHex) {
    const pubWA = CryptoJS.enc.Hex.parse(pubHex);
    const sha = CryptoJS.SHA256(pubWA);
    const ripe = CryptoJS.RIPEMD160(sha);
    return CryptoJS.enc.Hex.stringify(ripe);
}

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
// WORKER: TUGAS SCAN RENTANG X
// ============================================

if (!isMainThread) {
    // Worker process
    const { targetHash160, startX, endX, workerId } = workerData;
    
    let x = startX;
    let attempts = 0;
    let xHex = '';
    let pubKey = '';
    let hash160 = '';
    
    while (x < endX) {
        attempts++;
        xHex = x.toString(16).padStart(64, '0');
        pubKey = '02' + xHex;
        hash160 = pubkeyToHash160(pubKey);
        
        if (hash160 === targetHash160) {
            const address = pubkeyToP2PKH(pubKey);
            parentPort.postMessage({ 
                found: true, 
                x, 
                pubKey, 
                hash160, 
                address,
                attempts 
            });
            process.exit(0);
        }
        
        x++;
        
        // Progress setiap 100k (hanya worker 1 yang report)
        if (workerId === 1 && attempts % 100000 === 0) {
            parentPort.postMessage({ 
                progress: true, 
                attempts,
                x: x - 1
            });
        }
    }
    
    parentPort.postMessage({ 
        done: true, 
        attempts,
        workerId
    });
}

// ============================================
// MAIN: JALANKAN WORKER
// ============================================

if (isMainThread) {
    const target = process.argv[2] || '1PWo3CV7wXnc3pPWVdbWYKKvPZUcBtLjsL';
    const numWorkers = parseInt(process.argv[3]) || 2;
    
    console.log(`🚀 Vanity3.js - Worker Threads (${numWorkers} Core)`);
    console.log(`   Target   : ${target}`);
    console.log(`   Workers  : ${numWorkers}\n`);
    
    const targetHash160 = addressToHash160(target);
    console.log(`📌 Target Hash160: ${targetHash160}\n`);
    
    const startTime = Date.now();
    let totalAttempts = 0;
    let workersDone = 0;
    let foundResult = null;
    
    // Bagi rentang x
    const CHUNK_SIZE = 10000000; // 10 juta per worker
    const workers = [];
    
    for (let i = 0; i < numWorkers; i++) {
        const startX = i * CHUNK_SIZE + 1;
        const endX = (i + 1) * CHUNK_SIZE + 1;
        
        const worker = new Worker(__filename, {
            workerData: {
                targetHash160,
                startX,
                endX,
                workerId: i + 1
            }
        });
        
        workers.push(worker);
        
        worker.on('message', (msg) => {
            if (msg.found) {
                foundResult = msg;
                console.log(`\n✅ DITEMUKAN oleh Worker ${i + 1}!`);
                console.log(`   x        : ${msg.x.toLocaleString()}`);
                console.log(`   Pubkey   : ${msg.pubKey}`);
                console.log(`   Hash160  : ${msg.hash160}`);
                console.log(`   Address  : ${msg.address}`);
                console.log(`   Attempts : ${msg.attempts.toLocaleString()}`);
                
                // Stop semua worker
                workers.forEach(w => w.terminate());
                const elapsed = (Date.now() - startTime) / 1000;
                console.log(`   Waktu    : ${elapsed.toFixed(2)} detik`);
                console.log(`   Total    : ${(totalAttempts + msg.attempts).toLocaleString()}`);
            }
            
            if (msg.progress) {
                const elapsed = (Date.now() - startTime) / 1000;
                const rate = (totalAttempts + msg.attempts) / elapsed;
                console.log(`📊 Worker ${i+1}: ${msg.x.toLocaleString()}... (${elapsed.toFixed(1)}s, ${Math.round(rate).toLocaleString()} hash/s)`);
            }
            
            if (msg.done) {
                workersDone++;
                totalAttempts += msg.attempts;
                console.log(`   Worker ${msg.workerId} selesai: ${msg.attempts.toLocaleString()} attempts`);
            }
        });
        
        worker.on('error', (err) => {
            console.error(`Worker error: ${err}`);
        });
    }
    
    // Tunggu semua worker selesai
    const checkDone = setInterval(() => {
        if (workersDone === numWorkers || foundResult) {
            clearInterval(checkDone);
            if (!foundResult) {
                const elapsed = (Date.now() - startTime) / 1000;
                console.log(`\n❌ Tidak ditemukan dalam rentang yang discan`);
                console.log(`📊 Total attempts: ${totalAttempts.toLocaleString()}`);
                console.log(`📊 Waktu: ${elapsed.toFixed(2)} detik`);
                console.log(`📊 Hash rate: ${Math.round(totalAttempts / elapsed).toLocaleString()} hash/s`);
            }
        }
    }, 1000);
}