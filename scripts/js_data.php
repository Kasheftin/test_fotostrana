<?php

require_once(dirname(__FILE__) . "/../fn.php");
require_once("req.php");

$CONFIG = require(dirname(__FILE__) . "/../config.php");

DB::setConfig($CONFIG["db"]);
DEBUG::start();

ob_implicit_flush(1);
mb_internal_encoding("UTF-8");
setlocale(LC_ALL,"ru_RU.UTF-8");


$out = "

var countries = new Array();
var cities = new Array();

countries[0] = '<!--[not_choosena]-->';\ncities[0] = new Array();\ncities[0][0] = '<!--[not_choosen]-->';

";


$geo_countries = set_by_id(DB::f("select * from geo_countries"));
$geo_cities = set_by_id(DB::f("select * from geo_cities"));


foreach($geo_countries as $rw)
{
	$rw = my_js_conv($rw);
	$out .= "countries[" . $rw[id] . "] = '" . $rw[name] . "';\ncities[" . $rw[id] . "] = new Array();\ncities[" . $rw[id] . "][0] = '<!--[not_choosen]-->';\n";
}

$out .= "\n\n";


foreach($geo_cities as $rw)
{
	$rw = my_js_conv($rw);
	$out .= "cities[" . $rw[country_id] . "][" . $rw[id] . "] = '" . $rw[name] . "';\n";
}


$out = preg_replace_callback("/<!--\[([^\[\]]+)\]-->/","use_dictionary_callback",$out);
echo $out;

  
?>
