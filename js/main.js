// main.js
document.addEventListener('DOMContentLoaded', () => {
  window.stateManager.getWorkerId();
  const UI = window.stateManager.UI;
  UI.pauseBtn.disabled = true;
  UI.stopBtn.disabled = true;
  UI.foundBtn.disabled = true;
  window.stateManager.log("Scanner siap. Klik 'Ambil Chunk & Mulai' untuk memulai.", 'info');

  // Event Listeners
  UI.startBtn.addEventListener('click', () => {
    if (!window.stateManager.state.running) {
      window.scanner.requestNewChunk();
    }
  });

  UI.pauseBtn.addEventListener('click', () => {
    if (!window.stateManager.state.running) return;
    window.stateManager.state.paused = true;
    window.stateManager.state.running = false;
    window.stateManager.UI.runState.textContent = 'paused';
    window.stateManager.log("Scanner di-pause.", 'info');
  });

  UI.stopBtn.addEventListener('click', () => {
    const state = window.stateManager.state;
    if (!state.running && !state.paused) return;
    state.running = false;
    state.paused = false;
    window.stateManager.UI.runState.textContent = 'idle';
    window.stateManager.UI.pauseBtn.disabled = true;
    window.stateManager.UI.stopBtn.disabled = true;
    window.stateManager.UI.startBtn.disabled = false;
    window.stateManager.log("Scanner dihentikan.", 'info');
  });

  UI.foundBtn.addEventListener('click', () => {
    const matches = window.stateManager.state.matches;
    if (matches.length > 0) {
      prompt("Private Key (hex):", matches[0].privHex);
    } else {
      alert("Belum ada match.");
    }
  });
});