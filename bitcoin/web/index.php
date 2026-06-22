<?php include 'header.php'; ?>
    <?php
    $stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM transactions) AS total_tx,
            (SELECT COUNT(*) FROM transaction_inputs) AS total_inputs,
            (SELECT COUNT(*) FROM transaction_inputs WHERE is_vulnerable = 1) AS vulnerable_count,
            (SELECT MAX(block_height) FROM transactions) AS latest_block,
            (SELECT COUNT(DISTINCT r) FROM transaction_inputs WHERE is_vulnerable = 1) AS unique_vuln_r
    ")->fetch();
    ?>

    <div class="stats">
        <div class="stat-card">
            <div class="label">Transactions</div>
            <div class="value"><?= number_format($stats['total_tx']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Inputs</div>
            <div class="value"><?= number_format($stats['total_inputs']) ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Vulnerable</div>
            <div class="value <?= $stats['vulnerable_count'] > 0 ? 'red' : 'green' ?>">
                <?= number_format($stats['vulnerable_count']) ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="label">Latest Block</div>
            <div class="value"><?= number_format($stats['latest_block']) ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>🔴 Vulnerable Inputs <span class="badge badge-vuln"><?= number_format($stats['vulnerable_count']) ?></span></h2>
            <a href="vulnerable.php" style="color: #f7931a; text-decoration: none; font-size: 14px;">View all →</a>
        </div>
        <?php
        $vuln = $pdo->query("
            SELECT ti.r, COUNT(*) AS count, 
                   GROUP_CONCAT(DISTINCT LEFT(t.txid, 16) SEPARATOR ', ') AS txids,
                   MIN(t.block_height) AS first_block,
                   MAX(t.block_height) AS last_block
            FROM transaction_inputs ti
            JOIN transactions t ON ti.tx_id = t.id
            WHERE ti.is_vulnerable = 1
            GROUP BY ti.r, ti.public_key
            ORDER BY count DESC
            LIMIT 8
        ");
        
        if ($vuln->rowCount() > 0) {
            echo '<div class="table-wrapper"><table>';
            echo '<tr><th>R (Nonce)</th><th>Usage</th><th>Block Range</th><th>Transactions</th></tr>';
            foreach ($vuln as $row) {
                $txids = explode(', ', $row['txids']);
                $display = [];
                foreach (array_slice($txids, 0, 3) as $tx) {
                    $display[] = "<a href='transaction.php?txid=$tx' class='tx-link'>$tx...</a>";
                }
                $more = count($txids) > 3 ? " +" . (count($txids) - 3) . " more" : "";
                echo "<tr>
                    <td class='mono' style='max-width: 300px; word-break: break-all;'>" . $row['r'] . "</td>
                    <td><span class='status-badge status-vuln'>{$row['count']}x</span></td>
                    <td>{$row['first_block']} → {$row['last_block']}</td>
                    <td>" . implode(', ', $display) . $more . "</td>
                </tr>";
            }
            echo '</table></div>';
        } else {
            echo '<p style="color: #28a745; padding: 10px 0;">✅ No vulnerable inputs found.</p>';
        }
        ?>
    </div>

    <?php include 'footer.php'; ?>