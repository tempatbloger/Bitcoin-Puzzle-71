// ============================================
// VANITY.CPP - REVISI (FIXED)
// ============================================
// Compile: g++ -std=c++17 -O3 -march=native -pthread vanity.cpp -lssl -lcrypto -o vanity
// Run: ./vanity 1PWo3CV7wXnc3pPWVdbWYKKvPZUcBtLjsL 1
// ============================================

#include <iostream>
#include <string>
#include <sstream>
#include <iomanip>
#include <vector>
#include <thread>
#include <atomic>
#include <chrono>
#include <mutex>
#include <cstring>
#include <openssl/evp.h>

using namespace std;
using namespace chrono;

// ============================================
// BASE58 ALPHABET
// ============================================

const string B58 = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

// ============================================
// HEX TO BYTES
// ============================================

vector<uint8_t> hexToBytes(const string& hex) {
    vector<uint8_t> bytes;
    for (size_t i = 0; i < hex.length(); i += 2) {
        string byteString = hex.substr(i, 2);
        uint8_t byte = (uint8_t)strtol(byteString.c_str(), nullptr, 16);
        bytes.push_back(byte);
    }
    return bytes;
}

// ============================================
// BYTES TO HEX
// ============================================

string bytesToHex(const uint8_t* bytes, size_t len) {
    stringstream ss;
    ss << hex << setfill('0');
    for (size_t i = 0; i < len; i++) {
        ss << setw(2) << (int)bytes[i];
    }
    return ss.str();
}

// ============================================
// UINT64 TO HEX (64 KARAKTER)
// ============================================

string toHex64(uint64_t x) {
    stringstream ss;
    ss << hex << setfill('0') << setw(16) << x;
    string xHex = ss.str();
    string padding = string(48, '0');  // 48 nol agar total 64 karakter
    return padding + xHex;
}

// ============================================
// BASE58 ENCODE
// ============================================

string base58encode(const vector<uint8_t>& bytes) {
    int zeros = 0;
    while (zeros < (int)bytes.size() && bytes[zeros] == 0) zeros++;
    
    vector<int> digits(1, 0);
    for (size_t i = zeros; i < bytes.size(); i++) {
        int carry = bytes[i];
        for (size_t j = 0; j < digits.size(); j++) {
            carry += digits[j] * 256;
            digits[j] = carry % 58;
            carry /= 58;
        }
        while (carry) {
            digits.push_back(carry % 58);
            carry /= 58;
        }
    }
    
    string out = string(zeros, '1');
    for (int i = digits.size() - 1; i >= 0; i--) {
        out += B58[digits[i]];
    }
    return out;
}

// ============================================
// BASE58 DECODE
// ============================================

vector<uint8_t> base58Decode(const string& address) {
    vector<int> digits(1, 0);
    for (char c : address) {
        size_t value = B58.find(c);
        if (value == string::npos) {
            throw runtime_error("Invalid Base58 character");
        }
        
        int carry = (int)value;
        for (size_t j = 0; j < digits.size(); j++) {
            carry += digits[j] * 58;
            digits[j] = carry % 256;
            carry /= 256;
        }
        while (carry) {
            digits.push_back(carry % 256);
            carry /= 256;
        }
    }
    
    vector<uint8_t> bytes;
    for (int i = digits.size() - 1; i >= 0; i--) {
        bytes.push_back((uint8_t)digits[i]);
    }
    
    for (char c : address) {
        if (c == '1') {
            bytes.insert(bytes.begin(), 0);
        } else {
            break;
        }
    }
    
    return bytes;
}

// ============================================
// ALAMAT → HASH160
// ============================================

string addressToHash160(const string& address) {
    vector<uint8_t> bytes = base58Decode(address);
    if (bytes.size() != 25) {
        throw runtime_error("Invalid address length: " + to_string(bytes.size()) + " (should be 25)");
    }
    
    string hash160 = bytesToHex(&bytes[1], 20);
    return hash160;
}

// ============================================
// HASH160 (SHA-256 + RIPEMD-160)
// ============================================

string pubkeyToHash160(const string& pubHex) {
    vector<uint8_t> pubBytes = hexToBytes(pubHex);
    
    // SHA-256
    uint8_t sha256[32];
    EVP_MD_CTX* ctx = EVP_MD_CTX_new();
    EVP_DigestInit_ex(ctx, EVP_sha256(), nullptr);
    EVP_DigestUpdate(ctx, pubBytes.data(), pubBytes.size());
    EVP_DigestFinal_ex(ctx, sha256, nullptr);
    EVP_MD_CTX_free(ctx);
    
    // RIPEMD-160
    uint8_t ripemd160[20];
    ctx = EVP_MD_CTX_new();
    EVP_DigestInit_ex(ctx, EVP_ripemd160(), nullptr);
    EVP_DigestUpdate(ctx, sha256, 32);
    EVP_DigestFinal_ex(ctx, ripemd160, nullptr);
    EVP_MD_CTX_free(ctx);
    
    return bytesToHex(ripemd160, 20);
}

// ============================================
// PUBLIC KEY → ALAMAT
// ============================================

string pubkeyToAddress(const string& pubHex) {
    string hash160 = pubkeyToHash160(pubHex);
    
    string verPayload = "00" + hash160;
    vector<uint8_t> verBytes = hexToBytes(verPayload);
    
    uint8_t sha1[32];
    EVP_MD_CTX* ctx = EVP_MD_CTX_new();
    EVP_DigestInit_ex(ctx, EVP_sha256(), nullptr);
    EVP_DigestUpdate(ctx, verBytes.data(), verBytes.size());
    EVP_DigestFinal_ex(ctx, sha1, nullptr);
    
    uint8_t sha2[32];
    EVP_DigestInit_ex(ctx, EVP_sha256(), nullptr);
    EVP_DigestUpdate(ctx, sha1, 32);
    EVP_DigestFinal_ex(ctx, sha2, nullptr);
    EVP_MD_CTX_free(ctx);
    
    string checksum = bytesToHex(sha2, 4);
    string finalHex = verPayload + checksum;
    vector<uint8_t> finalBytes = hexToBytes(finalHex);
    
    return base58encode(finalBytes);
}

// ============================================
// THREAD: SCAN RANGE
// ============================================

atomic<bool> foundFlag(false);
mutex coutMutex;

void scanRange(uint64_t startX, uint64_t endX, const string& targetHash160, 
               int threadId, atomic<uint64_t>& totalAttempts, 
               steady_clock::time_point startTime) {
    uint64_t x = startX;
    uint64_t attempts = 0;
    
    while (x < endX && !foundFlag.load()) {
        attempts++;
        
        string xHex = toHex64(x);
        string pubKey = "02" + xHex;
        string hash160 = pubkeyToHash160(pubKey);
        
        if (hash160 == targetHash160) {
            string address = pubkeyToAddress(pubKey);
            foundFlag.store(true);
            
            lock_guard<mutex> lock(coutMutex);
            cout << "\n✅ DITEMUKAN oleh Thread " << threadId << "!" << endl;
            cout << "   x        : " << x << endl;
            cout << "   Pubkey   : " << pubKey << endl;
            cout << "   Hash160  : " << hash160 << endl;
            cout << "   Address  : " << address << endl;
            cout << "   Attempts : " << attempts << endl;
            return;
        }
        
        x++;
        
        // Progress setiap 100.000 attempts
        if (attempts % 100000 == 0) {
            totalAttempts += 100000;
            auto now = steady_clock::now();
            auto elapsed = duration_cast<seconds>(now - startTime).count();
            double rate = (elapsed > 0) ? (double)totalAttempts.load() / elapsed : 0;
            
            lock_guard<mutex> lock(coutMutex);
            cout << "📊 Thread " << threadId 
                 << ": " << totalAttempts.load() << " attempts..." 
                 << " (" << elapsed << "s, " << (int)rate << " hash/s)" << endl;
        }
    }
}

// ============================================
// MAIN
// ============================================

int main(int argc, char* argv[]) {
    cout << "========================================" << endl;
    cout << "  VANITY.CPP - REVISI" << endl;
    cout << "========================================" << endl;
    
    string targetAddress = (argc > 1) ? argv[1] : "1PWo3CV7wXnc3pPWVdbWYKKvPZUcBtLjsL";
    int numThreads = (argc > 2) ? stoi(argv[2]) : 1;
    uint64_t maxAttempts = (argc > 3) ? stoull(argv[3]) : 20000000;
    
    cout << "   Target   : " << targetAddress << endl;
    cout << "   Threads  : " << numThreads << endl;
    cout << "   Max      : " << maxAttempts << endl;
    cout << endl;
    
    try {
        string targetHash160 = addressToHash160(targetAddress);
        cout << "📌 Target Hash160: " << targetHash160 << endl;
        cout << "   Panjang : " << targetHash160.length() << " hex (20 byte)" << endl;
        cout << endl;
        
        uint64_t chunkSize = maxAttempts / numThreads;
        vector<thread> threads;
        atomic<uint64_t> totalAttempts(0);
        
        auto startTime = steady_clock::now();
        
        cout << "🔍 Memulai pencarian dengan " << numThreads << " thread..." << endl;
        cout << "   (Progress setiap 100.000 attempts)" << endl;
        cout << endl;
        
        for (int i = 0; i < numThreads; i++) {
            uint64_t startX = i * chunkSize + 1;
            uint64_t endX = (i + 1) * chunkSize + 1;
            if (i == numThreads - 1) endX = maxAttempts + 1;
            
            threads.emplace_back(scanRange, startX, endX, targetHash160, 
                                 i + 1, ref(totalAttempts), startTime);
        }
        
        for (auto& t : threads) {
            t.join();
        }
        
        auto endTime = steady_clock::now();
        auto duration = duration_cast<seconds>(endTime - startTime).count();
        
        cout << "\n========================================" << endl;
        cout << "  SELESAI!" << endl;
        cout << "========================================" << endl;
        cout << "   Total attempts: " << totalAttempts.load() << endl;
        cout << "   Waktu         : " << duration << " detik" << endl;
        if (duration > 0) {
            cout << "   Hash rate     : " << (totalAttempts.load() / duration) << " hash/s" << endl;
        }
        cout << endl;
        
        if (totalAttempts.load() >= maxAttempts) {
            cout << "⚠️  Target tidak ditemukan dalam " << maxAttempts << " attempts" << endl;
            cout << "   (x = 1 sampai " << maxAttempts << ")" << endl;
        }
        
    } catch (const exception& e) {
        cerr << "❌ Error: " << e.what() << endl;
        return 1;
    }
    
    return 0;
}