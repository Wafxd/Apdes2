<?php
// LANGSUNG HTML, TANPA PHP SAMA SEKALI
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TEST - DESA SENDANG LAOK</title>
  <link rel="icon" href="./assets/images/logo.png">
  <link rel="stylesheet" href="./assets/css/style.css">
  <link rel="stylesheet" href="./assets/css/style1.css">
  <style>
    body { font-family: Arial; margin:0; padding:20px; background:#f0f0f0; }
    .box { background:white; padding:20px; margin-bottom:20px; border-radius:10px; }
    h1 { color:#4e73df; }
  </style>
</head>
<body>
  <div class="box">
    <h1>TEST PAGE</h1>
    <p>Jika ini muncul 2 kali, berarti server Anda bermasalah.</p>
    <p>Tanggal: <?php echo date('Y-m-d H:i:s'); ?></p>
  </div>
  
  <div class="box">
    <h2>Cek Duplikasi:</h2>
    <p>Hitung ada berapa box di atas? Seharusnya hanya 1.</p>
  </div>
</body>
</html>