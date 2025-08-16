// utils.js
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

const hexToBytes = (hex) => {
  if (hex.length % 2) hex = '0' + hex;
  const a = [];
  for (let i = 0; i < hex.length; i += 2) {
    a.push(parseInt(hex.slice(i, i + 2), 16));
  }
  return new Uint8Array(a);
};

const wordToHex = (w) => CryptoJS.enc.Hex.stringify(w);

function pubkeyToP2PKH(pubHex) {
  const pubWA = CryptoJS.enc.Hex.parse(pubHex);
  const sha = CryptoJS.SHA256(pubWA);
  const ripe = CryptoJS.RIPEMD160(sha);
  const verPayloadHex = "00" + wordToHex(ripe);
  const verWA = CryptoJS.enc.Hex.parse(verPayloadHex);
  const checksum = CryptoJS.SHA256(CryptoJS.SHA256(verWA));
  const chkHex = wordToHex(checksum).slice(0, 8);
  const finalHex = verPayloadHex + chkHex;
  return base58encode(hexToBytes(finalHex));
}

const ec = new elliptic.ec('secp256k1');

function bigIntToPrivHex(bn) {
  let h = bn.toString(16);
  return h.padStart(64, '0');
}

// Export untuk digunakan di module lain
window.utils = {
  base58encode,
  hexToBytes,
  wordToHex,
  pubkeyToP2PKH,
  ec,
  bigIntToPrivHex
};