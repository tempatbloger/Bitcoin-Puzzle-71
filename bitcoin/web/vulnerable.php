<?php include 'header.php'; ?>
    <?php
    // Stats
    $stats = $pdo->query("
        SELECT 
            COUNT(*) AS total_vuln,
            COUNT(DISTINCT r) AS unique_r,
            COUNT(DISTINCT public_key) AS unique_keys
        FROM transaction_inputs 
        WHERE is_vulnerable = 1
    ")->fetch();
    ?>

    <div class="stats">
        <div class="stat-card">
            <div class="label">Total Vulnerable Inputs</div>
            <div class="value red"><?= number_format($stats['total_vuln']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Unique R (Nonce)</div>
            <div class="value"><?= number_format($stats['unique_r']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Unique Public Keys</div>
            <div class="value"><?= number_format($stats['unique_keys']) ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>🔴 All Vulnerable Inputs <span class="badge badge-vuln"><?= number_format($stats['total_vuln']) ?></span></h2>
        </div>

        <?php
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $per_page = 50;
        $offset = ($page - 1) * $per_page;
        
        // Count total
$count_stmt = $pdo->query("SELECT COUNT(*) FROM transaction_inputs WHERE is_vulnerable = 1");
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

// Get data - perbaiki query
$stmt = $pdo->prepare("
    SELECT ti.r, ti.s, ti.z, ti.public_key, ti.input_index,
           t.txid, t.block_height, t.block_time
    FROM transaction_inputs ti
    JOIN transactions t ON ti.tx_id = t.id
    WHERE ti.is_vulnerable = 1
    ORDER BY t.block_height DESC, ti.input_index
    LIMIT " . (int)$per_page . " OFFSET " . (int)$offset
);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php if (!empty($rows)): ?>
        <div class="table-wrapper">
            <table>
                <tr>
                    <th>#</th>
                    <th>TXID</th>
                    <th>Block</th>
                    <th>R (Nonce)</th>
                    <th>S</th>
                    <th>Z</th>
                    <th>Public Key</th>
                    <th>Action</th>
                </tr>
                <?php 
                $num = $offset + 1;
                foreach ($rows as $row): 
                ?>
                <tr>
                    <td><?= $num++ ?></td>
                    <td class="mono" style="max-width: 120px;"><?= substr($row['txid'], 0, 16) ?>...</td>
                    <td><?= number_format($row['block_height']) ?></td>
                    <td class="mono" style="max-width: 200px; word-break: break-all; font-size: 10px;"><?= $row['r'] ?></td>
                    <td class="mono" style="max-width: 160px; font-size: 10px;"><?= substr($row['s'], 0, 24) ?>...</td>
                    <td class="mono" style="max-width: 160px; font-size: 10px;"><?= substr($row['z'], 0, 24) ?>...</td>
                    <td class="mono" style="max-width: 140px; font-size: 10px;"><?= substr($row['public_key'], 0, 20) ?>...</td>
                    <td><a href='transaction.php?txid=<?= $row['txid'] ?>' class='tx-link'>Detail</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">← Prev</a>
            <?php else: ?>
                <a class="disabled">← Prev</a>
            <?php endif; ?>
            
            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>">Next →</a>
            <?php else: ?>
                <a class="disabled">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
            <p style="color: #28a745; padding: 20px 0;">✅ No vulnerable inputs found.</p>
        <?php endif; ?>
    </div>

   <?php include 'footer.php'; ?>