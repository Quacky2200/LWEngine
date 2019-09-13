<!DOCTYPE html>
<html>
<head>
	<title><?=$this->site->formattedTitle?></title>
	<?php

	$this->echoStylesheets(array(
		"css/opensans/stylesheet.css",
		"css/main.css"
	), true);

	$this->echoScripts(array(
		"js/jquery-1.11.2.min.js",
		"js/jquery-1.11.2.min.js",
		"js/jquery-migrate-1.2.1.min.js",
		"js/script.js"
	), true);
	?>
</head>
<body>