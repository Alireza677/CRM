<?php
$host='127.0.0.1'; $db='crm2'; $user='root'; $pass=''; $port=3306;
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$stmt = $pdo->prepare('select id, proforma_number, subject, opportunity_id from proformas where id = ?');
$stmt->execute([226]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo "NOT_FOUND\n"; exit; }
foreach ($row as $k=>$v) { echo "$k=".var_export($v,true)."\n"; }
