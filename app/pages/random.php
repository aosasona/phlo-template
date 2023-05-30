<?php
// to demonstrate normal PHP support (mixed with html, css, js, etc)
$random = rand(0, 100);
?>

<html lang="en">
<head>
	<link rel="stylesheet" href="/public/css/style.css">
	<script src="/public/js/index.js"></script>
	<title>Random</title>
</head>
<body id="app">
<div class="content_container">
	<h1>Random Number</h1>
	<p>The random number is: <?= $random ?></p>
</div>
</body>
</html>

