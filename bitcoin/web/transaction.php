<?php
// transaction.php
include 'header.php';

$txid = isset($_GET['txid']) ? $_GET['txid'] : '';

if (empty($txid) || strlen($txid) != 64) {
    die('Invalid TXID. Please provide a valid 64-character transaction ID.');
}

// Get transaction info
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE txid = ?");
$stmt->execute([$txid]);
$tx = $stmt->fetch();

if (!$tx) {
    die('Transaction not found in database.');
}

// Get inputs
$stmt = $pdo->prepare("SELECT * FROM transaction_inputs WHERE tx_id = ? ORDER BY input_index");
$stmt->execute([$tx['id']]);
$inputs = $stmt->fetchAll();
?>

<div class="card">
    <h2>📋 Transaction Info</h2>
    <div class="info-grid">
        <div class="info-item">
            <span class="label">TXID</span>
            <span class="value mono"><?= htmlspecialchars($tx['txid']) ?></span>
        </div>
        <div class="info-item">
            <span class="label">Block</span>
            <span class="value"><a href="block.php?height=<?= $tx['block_height'] ?>" class="tx-link"><?= number_format($tx['block_height']) ?></a></span>
        </div>
        <div class="info-item">
            <span class="label">Block Time</span>
            <span class="value"><?= htmlspecialchars($tx['block_time']) ?></span>
        </div>
        <div class="info-item">
            <span class="label">Total Inputs</span>
            <span class="value"><?= count($inputs) ?></span>
        </div>
    </div>
</div>

<div class="card">
    <h2>📥 Inputs (<?= count($inputs) ?>)</h2>
    <?php if (count($inputs) > 0): ?>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>#</th>
                <th>R (Nonce)</th>
                <th>S</th>
                <th>Z</th>
                <th>Public Key</th>
            </tr>
            <?php foreach ($inputs as $idx => $input): ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td class="mono" style="max-width: 300px; word-break: break-all;"><?= htmlspecialchars($input['r']) ?></td>
                <td class="mono" style="max-width: 300px; word-break: break-all;"><?= htmlspecialchars($input['s']) ?></td>
                <td class="mono" style="max-width: 300px; word-break: break-all;"><?= htmlspecialchars($input['z']) ?></td>
                <td class="mono" style="max-width: 300px; word-break: break-all;"><?= htmlspecialchars($input['public_key']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php else: ?>
        <p style="color: #888;">No inputs found.</p>
    <?php endif; ?>
</div>

<?php if (count($inputs) > 1): ?>
<div class="card">
    <h2>🔴 Vulnerability Check</h2>
    <?php
    $r_values = array_column($inputs, 'r');
    $duplicates = array_diff_assoc($r_values, array_unique($r_values));
    
    if (count($duplicates) > 0):
    ?>
        <p style="color: #dc3545; font-weight: 500;">
            ⚠️ This transaction has duplicate R values! 
            This indicates nonce reuse vulnerability.
        </p>
        <p style="color: #888; font-size: 14px; margin-top: 8px;">
            Duplicate R found in inputs: <?= implode(', ', array_keys($duplicates) + 1) ?>
        </p>
    <?php else: ?>
        <p style="color: #28a745;">✓ No duplicate R found in this transaction.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>