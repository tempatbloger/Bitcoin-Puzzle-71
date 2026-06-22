<?php
// header.php
include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitcoin Analysis</title>
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
            flex-wrap: wrap;
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
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .card h2 { font-size: 17px; font-weight: 600; color: #1a1a2e; }
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
        
        .footer {
            text-align: center;
            padding: 20px 0 5px;
            color: #aaa;
            font-size: 13px;
            border-top: 1px solid #eee;
            margin-top: 20px;
        }
        
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
        <h1>🔍 Bitcoin Analysis</h1>
        <div class="header-time"><?= date('Y-m-d H:i:s') ?></div>
    </div>

    <div class="nav">
        <a href="index.php" <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : '' ?>>Dashboard</a>
        <a href="vulnerable.php" <?= basename($_SERVER['PHP_SELF']) == 'vulnerable.php' ? 'class="active"' : '' ?>>Vulnerable</a>
        <a href="transactions.php" <?= basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'class="active"' : '' ?>>Transactions</a>
        <a href="blocks.php" <?= basename($_SERVER['PHP_SELF']) == 'blocks.php' ? 'class="active"' : '' ?>>Blocks</a>
        <a href="search.php" <?= basename($_SERVER['PHP_SELF']) == 'search.php' ? 'class="active"' : '' ?>>Search</a>
    </div>