<!DOCTYPE html>
<html>
<head>
	<?php
	echo $this->getGeneratedTitle($template, true);
	//Assume we're in our public directory.
	$template->addMultipleCSS(array(
		"css/opensans/stylesheet.css",
		"css/main.css"
	), true);
	$template->addMultipleJS(array(
		"js/jquery-1.11.2.min.js",
		"js/jquery-1.11.2.min.js",
		"js/jquery-migrate-1.2.1.min.js",
		"js/script.js"
	), true);
	$template->echoCSSAndJSDependencies();
	?>
</head>
<body>