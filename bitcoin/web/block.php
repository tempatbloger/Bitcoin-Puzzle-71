<?php
// block.php
include 'config.php';

$height = isset($_GET['height']) ? (int)$_GET['height'] : 0;

if ($height <= 0) {
    die('Invalid block height. Please provide a valid block number.');
}

// Get block info
$stmt = $pdo->prepare("
    SELECT 
        block_height,
        MIN(block_time) AS block_time,
        COUNT(*) AS total_transactions,
        SUM(total_inputs) AS total_inputs,
        (SELECT COUNT(*) FROM transaction_inputs ti 
         JOIN transactions t2 ON ti.tx_id = t2.id 
         WHERE t2.block_height = ? AND ti.is_vulnerable = 1) AS vulnerable_count
    FROM transactions
    WHERE block_height = ?
");
$stmt->execute([$height, $height]);
$block = $stmt->fetch();

if (!$block) {
    die('Block not found in database. Please scan this block first.');
}

// Get transactions in this block
$stmt = $pdo->prepare("
    SELECT t.txid, t.total_inputs,
           MAX(ti.is_vulnerable) AS has_vuln,
           COUNT(ti.id) AS input_count
    FROM transactions t
    LEFT JOIN transaction_inputs ti ON t.id = ti.tx_id
    WHERE t.block_height = ?
    GROUP BY t.id
    ORDER BY t.id
");
$stmt->execute([$height]);
$transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Block <?= number_format($height) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #ffffff; 
            color: #1a1a2e; 
            padding: 30px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eef0f5;
        }
        .header h1 { font-size: 24px; font-weight: 600; color: #f7931a; }
        .header-time { color: #888; font-size: 13px; }
        .back-link { 
            color: #555; 
            text-decoration: none; 
            font-size: 14px;
            padding: 6px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        .back-link:hover { background: #f5f7fa; }
        
        .nav { 
            display: flex; 
            gap: 6px; 
            margin-bottom: 25px;
            background: #f5f7fa;
            padding: 6px;
            border-radius: 8px;
        }
        .nav a { 
            color: #555; 
            text-decoration: none; 
            padding: 8px 20px; 
            border-radius: 6px; 
            font-size: 14px;
            font-weight: 500;
        }
        .nav a:hover { background: #e8ecf2; }
        .nav a.active { background: #f7931a; color: #fff; }
        
        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); 
            gap: 15px; 
            margin-bottom: 25px; 
        }
        .stat-card { 
            background: #f8f9fc; 
            padding: 16px 20px; 
            border-radius: 8px; 
            border: 1px solid #eef0f5;
        }
        .stat-card .label { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 0.3px; }
        .stat-card .value { font-size: 26px; font-weight: 600; color: #1a1a2e; margin-top: 2px; }
        .stat-card .value.red { color: #dc3545; }
        .stat-card .value.green { color: #28a745; }
        
        .card { 
            background: #ffffff; 
            border-radius: 8px; 
            padding: 20px; 
            margin-bottom: 20px; 
            border: 1px solid #eef0f5;
        }
        .card h2 { font-size: 17px; font-weight: 600; color: #1a1a2e; margin-bottom: 14px; }
        .card h2 .badge {
            font-size: 12px;
            padding: 2px 10px;
            border-radius: 12px;
            margin-left: 8px;
        }
        .badge-vuln { background: #fce4e4; color: #dc3545; }
        .badge-safe { background: #e6f4ea; color: #28a745; }
        
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { 
            padding: 10px 12px; 
            text-align: left; 
            border-bottom: 1px solid #f0f2f5;
        }
        th { 
            background: #f8f9fc; 
            color: #666; 
            font-weight: 600; 
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        tr:hover { background: #fafbfc; }
        .mono { 
            font-family: 'Courier New', monospace; 
            font-size: 12px; 
            word-break: break-all;
            color: #333;
        }
        .tx-link { 
            color: #f7931a; 
            text-decoration: none; 
            font-weight: 500;
        }
        .tx-link:hover { text-decoration: underline; }
        
        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-vuln { background: #fce4e4; color: #dc3545; }
        .status-safe { background: #e6f4ea; color: #28a745; }
        
        .block-nav {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .block-nav a {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #555;
            font-size: 14px;
        }
        .block-nav a:hover { background: #f5f7fa; }
        .block-nav input {
            padding: 8px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            width: 150px;
        }
        .block-nav input:focus { outline: none; border-color: #f7931a; }
        .block-nav button {
            padding: 8px 20px;
            border: none;
            background: #f7931a;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        .block-nav button:hover { background: #e08510; }
        
        .footer {
            text-align: center;
            padding: 20px 0 5px;
            color: #aaa;
            font-size: 13px;
            border-top: 1px solid #eee;
            margin-top: 20px;
        }
        
        @media (max-width: 600px) {
            body { padding: 15px; }
            .stats { grid-template-columns: 1fr 1fr; }
            .stat-card .value { font-size: 20px; }
            .header { flex-direction: column; align-items: flex-start; gap: 8px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📦 Block #<?= number_format($height) ?></h1>
        <div>
            <span class="header-time"><?= date('Y-m-d H:i:s') ?></span>
            <a href="index.php" class="back-link" style="margin-left: 15px;">← Dashboard</a>
        </div>
    </div>

    <div class="nav">
        <a href="index.php">Dashboard</a>
        <a href="vulnerable.php">Vulnerable</a>
        <a href="transactions.php">Transactions</a>
        <a href="search.php">Search</a>
    </div>

    <!-- Block Navigation -->
    <div class="block-nav">
        <a href="?height=<?= max(1, $height - 1) ?>">← Previous Block</a>
        <a href="?height=<?= $height + 1 ?>">Next Block →</a>
        <form method="GET" style="display: flex; gap: 8px;">
            <input type="number" name="height" placeholder="Block number..." value="">
            <button type="submit">Go</button>
        </form>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <div class="label">Block Time</div>
            <div class="value" style="font-size: 18px;"><?= htmlspecialchars($block['block_time']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Transactions</div>
            <div class="value"><?= number_format($block['total_transactions']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Total Inputs</div>
            <div class="value"><?= number_format($block['total_inputs']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Vulnerable Inputs</div>
            <div class="value <?= $block['vulnerable_count'] > 0 ? 'red' : 'green' ?>">
                <?= number_format($block['vulnerable_count']) ?>
            </div>
        </div>
    </div>

    <!-- Transactions -->
    <div class="card">
        <h2>📋 Transactions in Block <span class="badge badge-vuln"><?= number_format($block['total_transactions']) ?></span></h2>
        <?php if (!empty($transactions)): ?>
        <div class="table-wrapper">
            <table>
                <tr>
                    <th>#</th>
                    <th>TXID</th>
                    <th>Inputs</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php $num = 1; foreach ($transactions as $row): ?>
                <tr>
                    <td><?= $num++ ?></td>
                    <td class="mono" style="max-width: 300px; word-break: break-all;"><?= htmlspecialchars($row['txid']) ?></td>
                    <td><?= $row['input_count'] ?></td>
                    <td>
                        <?php if ($row['has_vuln'] == 1): ?>
                            <span class="status-badge status-vuln">⚠ Vulnerable</span>
                        <?php else: ?>
                            <span class="status-badge status-safe">✓ Safe</span>
                        <?php endif; ?>
                    </td>
                    <td><a href='transaction.php?txid=<?= $row['txid'] ?>' class='tx-link'>Detail</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php else: ?>
            <p style="color: #888;">No transactions found in this block.</p>
        <?php endif; ?>
    </div>

    <div class="footer">
        Bitcoin Analysis Tool &bull; Block <?= number_format($height) ?>
    </div>
</div>
</body>
</html>