<?php
// blocks.php
include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blocks</title>
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
        
        .pagination {
            display: flex;
            gap: 6px;
            justify-content: center;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .pagination a {
            padding: 6px 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #555;
            font-size: 13px;
        }
        .pagination a:hover { background: #f5f7fa; }
        .pagination a.active { background: #f7931a; color: #fff; border-color: #f7931a; }
        .pagination a.disabled { color: #ccc; pointer-events: none; }
        
        .footer {
            text-align: center;
            padding: 20px 0 5px;
            color: #aaa;
            font-size: 13px;
            border-top: 1px solid #eee;
            margin-top: 20px;
        }
        
        .filter-box {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-box input, .filter-box select {
            padding: 8px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            background: #fff;
        }
        .filter-box input:focus, .filter-box select:focus {
            outline: none;
            border-color: #f7931a;
        }
        .filter-box button {
            padding: 8px 20px;
            border: none;
            background: #f7931a;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        .filter-box button:hover { background: #e08510; }
        
        @media (max-width: 600px) {
            body { padding: 15px; }
            .stats { grid-template-columns: 1fr 1fr; }
            .stat-card .value { font-size: 20px; }
            .header { flex-direction: column; align-items: flex-start; gap: 8px; }
            table { font-size: 11px; }
            th, td { padding: 6px 8px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📦 Blocks</h1>
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

    <?php
    // Stats
    $stats = $pdo->query("
        SELECT 
            COUNT(DISTINCT block_height) AS total_blocks,
            MIN(block_height) AS first_block,
            MAX(block_height) AS latest_block,
            SUM(total_inputs) AS total_inputs,
            (SELECT COUNT(*) FROM transaction_inputs WHERE is_vulnerable = 1) AS vulnerable_count
        FROM transactions
    ")->fetch();
    ?>

    <div class="stats">
        <div class="stat-card">
            <div class="label">Total Blocks</div>
            <div class="value"><?= number_format($stats['total_blocks']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Block Range</div>
            <div class="value" style="font-size: 18px;"><?= number_format($stats['first_block']) ?> → <?= number_format($stats['latest_block']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Total Inputs</div>
            <div class="value"><?= number_format($stats['total_inputs']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Vulnerable Inputs</div>
            <div class="value <?= $stats['vulnerable_count'] > 0 ? 'red' : '' ?>">
                <?= number_format($stats['vulnerable_count']) ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h2>📋 Block List <span class="badge badge-vuln"><?= number_format($stats['total_blocks']) ?></span></h2>
            <div class="filter-box">
                <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <input type="number" name="min" placeholder="Min block" value="<?= isset($_GET['min']) ? htmlspecialchars($_GET['min']) : '' ?>">
                    <input type="number" name="max" placeholder="Max block" value="<?= isset($_GET['max']) ? htmlspecialchars($_GET['max']) : '' ?>">
                    <select name="filter">
                        <option value="all" <?= (!isset($_GET['filter']) || $_GET['filter'] == 'all') ? 'selected' : '' ?>>All</option>
                        <option value="vulnerable" <?= (isset($_GET['filter']) && $_GET['filter'] == 'vulnerable') ? 'selected' : '' ?>>Has Vulnerable</option>
                    </select>
                    <button type="submit">Filter</button>
                    <a href="blocks.php" style="padding: 8px 20px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: #555;">Reset</a>
                </form>
            </div>
        </div>

        <?php
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $per_page = 50;
        $offset = ($page - 1) * $per_page;
        
        // Build query with filters
        $min_block = isset($_GET['min']) ? (int)$_GET['min'] : 0;
        $max_block = isset($_GET['max']) ? (int)$_GET['max'] : 0;
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        
        $where = [];
        $params = [];
        
        if ($min_block > 0) {
            $where[] = "block_height >= ?";
            $params[] = $min_block;
        }
        if ($max_block > 0) {
            $where[] = "block_height <= ?";
            $params[] = $max_block;
        }
        if ($filter == 'vulnerable') {
            $where[] = "vulnerable_count > 0";
        }
        
        $where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Get block data
        $sql = "
            SELECT 
                block_height,
                MIN(block_time) AS block_time,
                COUNT(*) AS total_transactions,
                SUM(total_inputs) AS total_inputs,
                (SELECT COUNT(*) FROM transaction_inputs ti 
                 JOIN transactions t2 ON ti.tx_id = t2.id 
                 WHERE t2.block_height = t.block_height AND ti.is_vulnerable = 1) AS vulnerable_count
            FROM transactions t
            $where_sql
            GROUP BY block_height
            ORDER BY block_height DESC
            LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php if (!empty($rows)): ?>
        <div class="table-wrapper">
            <table>
                <tr>
                    <th>#</th>
                    <th>Block</th>
                    <th>Time</th>
                    <th>Transactions</th>
                    <th>Inputs</th>
                    <th>Vulnerable</th>
                    <th>Action</th>
                </tr>
                <?php 
                $num = $offset + 1;
                foreach ($rows as $row): 
                ?>
                <tr>
                    <td><?= $num++ ?></td>
                    <td><a href='block.php?height=<?= $row['block_height'] ?>' class='tx-link'><?= number_format($row['block_height']) ?></a></td>
                    <td><?= htmlspecialchars($row['block_time']) ?></td>
                    <td><?= number_format($row['total_transactions']) ?></td>
                    <td><?= number_format($row['total_inputs']) ?></td>
                    <td>
                        <?php if ($row['vulnerable_count'] > 0): ?>
                            <span class="status-badge status-vuln"><?= number_format($row['vulnerable_count']) ?></span>
                        <?php else: ?>
                            <span class="status-badge status-safe">0</span>
                        <?php endif; ?>
                    </td>
                    <td><a href='block.php?height=<?= $row['block_height'] ?>' class='tx-link'>View</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <?php 
        // Count total for pagination
        $count_sql = "SELECT COUNT(DISTINCT block_height) FROM transactions t $where_sql";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetchColumn();
        $total_pages = ceil($total / $per_page);
        ?>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php 
            $query_params = [];
            if ($min_block > 0) $query_params['min'] = $min_block;
            if ($max_block > 0) $query_params['max'] = $max_block;
            if ($filter != 'all') $query_params['filter'] = $filter;
            $query_string = !empty($query_params) ? '&' . http_build_query($query_params) : '';
            ?>
            
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 . $query_string ?>">← Prev</a>
            <?php else: ?>
                <a class="disabled">← Prev</a>
            <?php endif; ?>
            
            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="?page=<?= $i . $query_string ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 . $query_string ?>">Next →</a>
            <?php else: ?>
                <a class="disabled">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
            <p style="color: #888; padding: 20px 0;">No blocks found.</p>
        <?php endif; ?>
    </div>

    <div class="footer">
        Bitcoin Analysis Tool &bull; <?= date('Y-m-d H:i:s') ?>
    </div>
</div>
</body>
</html>