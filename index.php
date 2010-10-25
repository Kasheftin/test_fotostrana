<?php

require_once("fn.php");

$CONFIG = require("config.php");

try
{
	DB::setConfig($CONFIG["db"]);

	$geo_cities = set_by_id(DB::f("select * from geo_cities"));
	$geo_countries = set_by_id(DB::f("select * from geo_countries"));

	DEBUG::start();

	mb_internal_encoding("UTF-8");
	setlocale(LC_ALL,"ru_RU.UTF-8");
	$app = app::getInstance();
	$app->setConfig($CONFIG);
	$app->setAjax($_REQUEST["ajax"]);
	$app->run();
	$app->display();

	DEBUG::finish();

	if (($_REQUEST["debug"] || $CONFIG["debug"]) && !$_REQUEST["ajax"])
		echo DEBUG::out($CONFIG["debug_format"]);
}
catch (Exception $e)
{
	$str = "Unspecified fatal exception: " . $e->getMessage() . "\nException occurs in file " . $e->getFile() . " on line " . $e->getLine() . "\n\n";

	if ($_REQUEST["ajax"])
		echo "var error=\"" . my_js_conv($str) . "\"; ";
	else
		echo str_replace("\n","<br />",$str);

	file_put_contents($CONFIG["tmp_dir"] . "/error.log",date("Y-m-d h:i:s") . " - " . $str,FILE_APPEND);
}
