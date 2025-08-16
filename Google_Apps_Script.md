# BTC Puzzle Chunk Coordinator (Updated with Stats)
```
const SHEET_NAME = "Sheet1"; // Ganti jika nama sheet berbeda

function doGet(e) {
  const action = e.parameter.action;

  if (action === "getChunk") {
    return getFreeChunk(e);
  } else if (action === "markDone") {
    return markChunkDone(e);
  } else if (action === "getStats") {
    return getChunkStats();
  } else {
    return ContentService.createTextOutput(
      JSON.stringify({ error: "Invalid action. Use: getChunk, markDone, getStats" })
    ).setMimeType(ContentService.MimeType.JSON);
  }
}

function doPost(e) {
  const action = e.parameter.action;
  if (action === "markDone") {
    return markChunkDone(e);
  } else {
    return ContentService.createTextOutput(JSON.stringify({ error: "Invalid POST action" }));
  }
}

// === AMBIL CHUNK ===
function getFreeChunk(e) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName(SHEET_NAME);
  const data = sheet.getDataRange().getValues();
  const headers = data[0];

  for (let i = 1; i < data.length; i++) {
    if (data[i][3] === "free") {
      const chunkId = data[i][0];
      const startHex = data[i][1];
      const endHex = data[i][2];

      sheet.getRange(i + 1, 4).setValue("taken"); // Status = taken
      sheet.getRange(i + 1, 5).setValue(e.parameter.workerId || "unknown");
      sheet.getRange(i + 1, 6).setValue(new Date());

      const output = {
        success: true,
        chunkId: chunkId,
        start: "0x" + startHex,
        end: "0x" + endHex
      };
      return ContentService.createTextOutput(JSON.stringify(output))
                         .setMimeType(ContentService.MimeType.JSON);
    }
  }

  const output = {
    success: false,
    error: "No free chunks available"
  };
  return ContentService.createTextOutput(JSON.stringify(output))
                     .setMimeType(ContentService.MimeType.JSON);
}

// === TANDAI SELESAI ===
function markChunkDone(e) {
  const chunkId = e.parameter.chunkId;
  const success = e.parameter.success || false;
  const notes = e.parameter.notes || "";

  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName(SHEET_NAME);
  const data = sheet.getDataRange().getValues();

  for (let i = 1; i < data.length; i++) {
    if (data[i][0] == chunkId) {
      sheet.getRange(i + 1, 4).setValue("done");
      sheet.getRange(i + 1, 7).setValue(notes);
      sheet.getRange(i + 1, 6).setValue(new Date()); // Update timestamp
      const output = { success: true };
      return ContentService.createTextOutput(JSON.stringify(output))
                         .setMimeType(ContentService.MimeType.JSON);
    }
  }

  return ContentService.createTextOutput(JSON.stringify({ success: false, error: "Chunk not found" }))
                     .setMimeType(ContentService.MimeType.JSON);
}

// === STATISTIK ===
function getChunkStats() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName(SHEET_NAME);
  const data = sheet.getDataRange().getValues();
  
  // Lewati header
  const rows = data.slice(1);
  
  let free = 0, taken = 0, done = 0;
  
  rows.forEach(row => {
    const status = (row[3] || "").toString().toLowerCase();
    if (status === "done") done++;
    else if (status === "taken") taken++;
    else free++;
  });

  const total = rows.length;
  const progress = total > 0 ? ((done / total) * 100).toFixed(2) : "0.00";

  const stats = {
    total: total,
    done: done,
    taken: taken,
    free: free,
    progress: progress
  };

  return ContentService.createTextOutput(JSON.stringify(stats))
                     .setMimeType(ContentService.MimeType.JSON);
}
```
# GenerateChunks
```
function generateChunks() {
  var sheetName = "Sheet1";
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(sheetName);

  if (!sheet) {
    sheet = ss.insertSheet(sheetName);
  }

  sheet.clear();

  // Gunakan string untuk angka besar — aman dari error parsing
  var START_HEX = "400000000000000000";  // 0x400...
  var CHUNK_COUNT = 10000;                   // Uji 5 dulu
  var KEYS_PER_CHUNK = 1000000;          // 1 juta per chunk

  // Header
  var data = [
    ["ChunkID", "StartHex", "EndHex", "Status", "WorkerID", "Timestamp", "Notes"]
  ];

  // Konversi hex string ke BigInt
  var startBase = BigInt("0x" + START_HEX);

  for (var i = 0; i < CHUNK_COUNT; i++) {
    var start = startBase + (BigInt(KEYS_PER_CHUNK) * BigInt(i));
    var end = start + BigInt(KEYS_PER_CHUNK) - BigInt(1);

    data.push([
      i,
      start.toString(16).toUpperCase(),
      end.toString(16).toUpperCase(),
      "free",
      "",
      "",
      ""
    ]);
  }

  // Tulis ke sheet
  var range = sheet.getRange(1, 1, data.length, 7);
  range.setValues(data);

  Browser.msgBox("✅ " + CHUNK_COUNT + " chunk berhasil dibuat!");
}
```


