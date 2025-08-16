// state.js
let state = {
  current: null,
  end: null,
  running: false,
  paused: false,
  matches: [],
  startedAt: null,
  chunkId: null,
  chunkStart: null,
  chunkEnd: null,
  workerId: null
};

const UI = {
  startBtn: document.getElementById('startBtn'),
  pauseBtn: document.getElementById('pauseBtn'),
  stopBtn: document.getElementById('stopBtn'),
  foundBtn: document.getElementById('foundBtn'),
  currentChunk: document.getElementById('currentChunk'),
  matchesCount: document.getElementById('matchesCount'),
  elapsed: document.getElementById('elapsed'),
  runState: document.getElementById('runState'),
  chunkProgress: document.getElementById('chunkProgress')
};

function getWorkerId() {
  let id = localStorage.getItem('btc_worker_id');
  if (!id) {
    id = 'w_' + Math.random().toString(36).substr(2, 8);
    localStorage.setItem('btc_worker_id', id);
  }
  document.getElementById('workerId').textContent = id;
  return id;
}

function log(message, type = 'info') {
  const logEl = document.getElementById('log');
  const entry = document.createElement('div');
  entry.className = type;
  entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
  logEl.appendChild(entry);
  logEl.scrollTop = logEl.scrollHeight;
}

// Update progress bar
function updateProgress() {
  if (!state.current || !state.end) return;
  const progress = state.current > state.end
    ? 100
    : Number((state.current - state.chunkStart) * 100n / (state.chunkEnd - state.chunkStart + 1n));
  UI.chunkProgress.style.width = Math.max(0, Math.min(100, progress)) + '%';
}

window.stateManager = {
  state,
  UI,
  getWorkerId,
  log,
  updateProgress
};