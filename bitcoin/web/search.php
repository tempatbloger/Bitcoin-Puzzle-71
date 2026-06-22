<?php
// search.php
include 'config.php';

$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_type = isset($_GET['type']) ? $_GET['type'] : 'txid';
$results = [];
$error = '';

if (!empty($search_term)) {
    try {
        if ($search_type == 'txid') {
            // Search by TXID
            $stmt = $pdo->prepare("
                SELECT t.txid, t.block_height, t.block_time, 
                       COUNT(ti.id) AS inputs,
                       MAX(ti.is_vulnerable) AS has_vuln
                FROM transactions t
                LEFT JOIN transaction_inputs ti ON t.id = ti.tx_id
                WHERE t.txid LIKE ?
                GROUP BY t.id
                ORDER BY t.block_height DESC
                LIMIT 50
            ");
            $stmt->execute(["%$search_term%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } elseif ($search_type == 'r') {
            // Search by R (nonce)
            $stmt = $pdo->prepare("
                SELECT ti.r, ti.s, ti.z, ti.public_key, ti.input_index,
                       t.txid, t.block_height, t.block_time,
                       ti.is_vulnerable
                FROM transaction_inputs ti
                JOIN transactions t ON ti.tx_id = t.id
                WHERE ti.r LIKE ?
                ORDER BY t.block_height DESC
                LIMIT 100
            ");
            $stmt->execute(["%$search_term%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } elseif ($search_type == 'public_key') {
            // Search by Public Key
            $stmt = $pdo->prepare("
                SELECT ti.r, ti.s, ti.z, ti.public_key, ti.input_index,
                       t.txid, t.block_height, t.block_time,
                       ti.is_vulnerable
                FROM transaction_inputs ti
                JOIN transactions t ON ti.tx_id = t.id
                WHERE ti.public_key LIKE ?
                ORDER BY t.block_height DESC
                LIMIT 100
            ");
            $stmt->execute(["%$search_term%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } elseif ($search_type == 'address') {
            // Search by Address
            $stmt = $pdo->prepare("
                SELECT ti.r, ti.s, ti.z, ti.public_key, ti.input_index, ti.address,
                       t.txid, t.block_height, t.block_time,
                       ti.is_vulnerable
                FROM transaction_inputs ti
                JOIN transactions t ON ti.tx_id = t.id
                WHERE ti.address LIKE ?
                ORDER BY t.block_height DESC
                LIMIT 100
            ");
            $stmt->execute(["%$search_term%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
    } catch (PDOException $e) {
        $error = "Search error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search</title>
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
        
        .card { 
            background: #ffffff; 
            border-radius: 8px; 
            padding: 20px; 
            margin-bottom: 20px; 
            border: 1px solid #eef0f5;
        }
        .card h2 { font-size: 17px; font-weight: 600; color: #1a1a2e; margin-bottom: 14px; }
        
        .search-box {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .search-box input[type="text"] {
            flex: 1;
            min-width: 250px;
            padding: 12px 18px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            background: #fff;
            color: #1a1a2e;
        }
        .search-box input[type="text"]:focus {
            outline: none;
            border-color: #f7931a;
        }
        .search-box select {
            padding: 12px 18px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            background: #fff;
            color: #1a1a2e;
            cursor: pointer;
        }
        .search-box select:focus {
            outline: none;
            border-color: #f7931a;
        }
        .search-box button {
            padding: 12px 40px;
            border: none;
            border-radius: 8px;
            background: #f7931a;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .search-box button:hover { background: #e08510; }
        
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
            font-size: 11px; 
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
        
        .no-results {
            padding: 30px 0;
            color: #888;
            text-align: center;
            font-size: 16px;
        }
        
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
            .header { flex-direction: column; align-items: flex-start; gap: 8px; }
            .search-box input[type="text"] { min-width: 100%; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🔎 Search</h1>
        <div>
            <span class="header-time"><?= date('Y-m-d H:i:s') ?></span>
            <a href="index.php" class="back-link" style="margin-left: 15px;">← Dashboard</a>
        </div>
    </div>

    <div class="nav">
        <a href="index.php">Dashboard</a>
        <a href="vulnerable.php">Vulnerable</a>
        <a href="transactions.php">Transactions</a>
        <a href="search.php" class="active">Search</a>
    </div>

    <div class="card">
        <h2>🔍 Search Database</h2>
        <form method="GET" action="search.php">
            <div class="search-box">
                <input type="text" name="q" placeholder="Enter TXID, R, Public Key, or Address..." 
                       value="<?= htmlspecialchars($search_term) ?>" required>
                <select name="type">
                    <option value="txid" <?= $search_type == 'txid' ? 'selected' : '' ?>>TXID</option>
                    <option value="r" <?= $search_type == 'r' ? 'selected' : '' ?>>R (Nonce)</option>
                    <option value="public_key" <?= $search_type == 'public_key' ? 'selected' : '' ?>>Public Key</option>
                    <option value="address" <?= $search_type == 'address' ? 'selected' : '' ?>>Address</option>
                </select>
                <button type="submit">Search</button>
            </div>
        </form>
        
        <?php if (!empty($error)): ?>
            <p style="color: #dc3545; padding: 10px;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        
        <?php if (!empty($search_term)): ?>
            <p style="color: #888; font-size: 14px; margin-bottom: 15px;">
                Found <?= count($results) ?> result(s) for "<strong><?= htmlspecialchars($search_term) ?></strong>" 
                in <strong><?= strtoupper($search_type) ?></strong>
            </p>
            
            <?php if (!empty($results)): ?>
                <div class="table-wrapper">
                    <?php if ($search_type == 'txid'): ?>
                        <table>
                            <tr>
                                <th>#</th>
                                <th>TXID</th>
                                <th>Block</th>
                                <th>Time</th>
                                <th>Inputs</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                            <?php $num = 1; foreach ($results as $row): ?>
                            <tr>
                                <td><?= $num++ ?></td>
                                <td class="mono"><?= htmlspecialchars($row['txid']) ?></td>
                                <td><?= number_format($row['block_height']) ?></td>
                                <td><?= htmlspecialchars($row['block_time']) ?></td>
                                <td><?= $row['inputs'] ?></td>
                                <td>
                                    <?php if ($row['has_vuln'] == 1): ?>
                                        <span class="status-badge status-vuln">Vulnerable</span>
                                    <?php else: ?>
                                        <span class="status-badge status-safe">Safe</span>
                                    <?php endif; ?>
                                </td>
                                <td><a href='transaction.php?txid=<?= $row['txid'] ?>' class='tx-link'>Detail</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <table>
                            <tr>
                                <th>#</th>
                                <th>TXID</th>
                                <th>Block</th>
                                <th>R</th>
                                <th>S</th>
                                <th>Z</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                            <?php $num = 1; foreach ($results as $row): ?>
                            <tr>
                                <td><?= $num++ ?></td>
                                <td class="mono" style="max-width: 120px;"><?= substr($row['txid'], 0, 16) ?>...</td>
                                <td><?= number_format($row['block_height']) ?></td>
                                <td class="mono" style="max-width: 120px;"><?= substr($row['r'], 0, 20) ?>...</td>
                                <td class="mono" style="max-width: 120px;"><?= substr($row['s'], 0, 20) ?>...</td>
                                <td class="mono" style="max-width: 120px;"><?= substr($row['z'], 0, 20) ?>...</td>
                                <td>
                                    <?php if ($row['is_vulnerable'] == 1): ?>
                                        <span class="status-badge status-vuln">Vulnerable</span>
                                    <?php else: ?>
                                        <span class="status-badge status-safe">Safe</span>
                                    <?php endif; ?>
                                </td>
                                <td><a href='transaction.php?txid=<?= $row['txid'] ?>' class='tx-link'>Detail</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    🔍 No results found for "<strong><?= htmlspecialchars($search_term) ?></strong>"
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-results" style="padding: 40px 0;">
                🔍 Enter a search term above to find transactions, R values, public keys, or addresses.
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        Bitcoin Analysis Tool &bull; <?= date('Y-m-d H:i:s') ?>
    </div>
</div>
</body>
</html>