// scanner.js
const GOOGLE_SCRIPT_URL = 'https://script.google.com/macros/s/AKfycbyhXsXFoW4e3kHoa1zK_3MC9uYiqPdWvI7jQdbNHTVrCv0SfVViXnKYe3UTpiZQ_2yI_w/exec';
const TARGET_ADDRESS = "1PWo3JeB9jrGwfHDNpdGK54CRas7fsVzXU";
const BATCH_SIZE = 1000;
const SCAN_DELAY = 1;

async function getChunkFromSheet() {
  try {
    const workerId = window.stateManager.getWorkerId();
    const url = `${GOOGLE_SCRIPT_URL}?action=getChunk&workerId=${encodeURIComponent(workerId)}`;
    const res = await fetch(url);
    const data = await res.json();

    if (data.success) {
      const state = window.stateManager.state;
      state.current = BigInt(data.start);
      state.end = BigInt(data.end);
      state.chunkId = data.chunkId;
      state.workerId = workerId;
      state.startedAt = Date.now();
      state.matches = [];
      state.chunkStart = state.current;
      state.chunkEnd = state.end;

      window.stateManager.UI.currentChunk.textContent = `#${data.chunkId} (${data.start} ? ${data.end})`;
      window.stateManager.log(`Chunk #${data.chunkId} diterima. Mulai scan...`, 'success');
      return true;
    } else {
      window.stateManager.log("Tidak ada chunk tersedia: " + (data.error || "tidak diketahui"), 'error');
      alert("Tidak ada chunk tersedia. Coba lagi nanti.");
      return false;
    }
  } catch (e) {
    window.stateManager.log("Gagal ambil chunk: " + e.message, 'error');
    alert("Gagal terhubung ke server. Cek URL script.");
    return false;
  }
}

async function reportDone(success, notes = "") {
  const state = window.stateManager.state;
  if (!state.chunkId) return;
  try {
    const url = GOOGLE_SCRIPT_URL;
    const body = new URLSearchParams({
      action: 'markDone',
      chunkId: state.chunkId,
      success: success ? 'true' : 'false',
      notes: notes
    });
    await fetch(url, { method: 'POST', body });
    window.stateManager.log(`Laporan dikirim: chunk #${state.chunkId}, success=${success}`, 'success');
  } catch (e) {
    window.stateManager.log("Gagal kirim laporan ke sheet", 'error');
  }
}

async function generateBatch(start, count) {
  const ec = window.utils.ec;
  const privHex = window.utils.bigIntToPrivHex;
  const pubkeyToP2PKH = window.utils.pubkeyToP2PKH;

  for (let j = 0n; j < count; j++) {
    const state = window.stateManager.state;
    if (state.paused || !state.running) break;
    const key = start + j;
    const hex = privHex(key);
    try {
      const keyPair = ec.keyFromPrivate(hex);
      const pubHex = keyPair.getPublic(true, 'hex');
      const addr = pubkeyToP2PKH(pubHex);
      if (addr === TARGET_ADDRESS) {
        const match = { idx: key.toString(), privHex: hex, addr };
        state.matches.push(match);
        window.stateManager.log(`? MATCH DITEMUKAN! Private Key: ${hex}`, 'match');
        alert(`?? SELAMAT! Anda menemukan kunci!\n${hex}`);
        reportDone(true, hex);
        window.stateManager.UI.foundBtn.disabled = false;
      }
    } catch (e) { /* skip */ }
  }
}

async function runScanner() {
  const state = window.stateManager.state;
  if (!state.current || state.running) return;

  state.running = true;
  state.paused = false;
  const UI = window.stateManager.UI;
  UI.runState.textContent = 'running';
  UI.elapsed.textContent = '0s';
  UI.pauseBtn.disabled = false;
  UI.stopBtn.disabled = false;
  UI.startBtn.disabled = true;

  const timer = setInterval(() => {
    const s = Math.floor((Date.now() - state.startedAt) / 1000);
    UI.elapsed.textContent = s + 's';
  }, 1000);

  while (state.running && !state.paused && state.current <= state.end) {
    const remaining = state.end - state.current + 1n;
    const count = remaining < BigInt(BATCH_SIZE) ? remaining : BigInt(BATCH_SIZE);
    await generateBatch(state.current, count);
    state.current += count;
    window.stateManager.updateProgress();
    await new Promise(r => setTimeout(r, SCAN_DELAY));
  }

  state.running = false;
  clearInterval(timer);
  UI.runState.textContent = 'idle';

  if (state.current > state.end) {
    window.stateManager.log("Chunk selesai. Mengambil chunk baru...", 'success');
    reportDone(false, "selesai");
    setTimeout(requestNewChunk, 2000);
  }
}

async function requestNewChunk() {
  if (await getChunkFromSheet()) {
    runScanner();
  } else {
    window.stateManager.log("Tidak bisa ambil chunk baru. Coba lagi nanti.", 'error');
    const UI = window.stateManager.UI;
    UI.startBtn.disabled = false;
    UI.pauseBtn.disabled = true;
    UI.stopBtn.disabled = true;
  }
}

window.scanner = { requestNewChunk, runScanner, reportDone };