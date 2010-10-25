<?php

require_once(dirname(__FILE__) . "/../fn.php");
require_once("req.php");

$CONFIG = require(dirname(__FILE__) . "/../config.php");

DB::setConfig($CONFIG["db"]);
DEBUG::start();

ob_implicit_flush(1);
mb_internal_encoding("UTF-8");
setlocale(LC_ALL,"ru_RU.UTF-8");


for ($i = 0; $i<2; $i++)
{
	$str = get_data(req("POST","fotostrana.ru","/mainpage/",array("cityId"=>0,"otherCity"=>"","search[gender]"=>$i?"w":"m","search[age]"=>0,"search[ageTo]=100")));
	$str = iconv("windows-1251","utf-8",$str);

	preg_match_all("/pops.*?=\s*[\"']([^\"']+)[\"']/i",$str,$ar);
	
	foreach($ar[1] as $str)
	{
		$arr = explode("|",$str);
		$data = array("name"=>$arr[0],"photo"=>$arr[1],"age"=>$arr[2],"city_name"=>$arr[3],"about"=>$arr[5],"photo_sq"=>$arr[6]);
	
		if (preg_match("/user\_\d+\/\d+\/(\d+)\./",$data["photo"],$m))
			$data["source_id"] = $m[1];
		else continue;
	
		if ($rw = DB::f1("select * from users where source_id=:source_id",array("source_id"=>$data["source_id"])))
		{
			echo "user $data[source_id] $data[name] already added<br>\n";
			continue;
		}

		if ($rw = DB::f1("select id,country_id from geo_cities where name=:name",array("name"=>$data["city_name"])))
		{
			$data["city_id"] = $rw["id"];
			$data["country_id"] = $rw["country_id"];
		}
//		else $data["city_id"] = DB::q("insert into geo_cities(`country_id`,`name`) values(:country_id,:name)",array("country_id"=>$data["country_id"],"name"=>$data["city_name"]));
		else continue;

		$query = "
			insert into users(`email`,`password`,`sex`,`birth`,`nick`,`about`,`dt_added`,`photo`,`country_id`,`city_id`,`source_id`)
			values(:email,:password,:sex,:birth,:nick,:about,:dt_added,:photo,:country_id,:city_id,:source_id)
		";

		$ar = array(
			"email"=>$data["source_id"] . "@noemail.com",
			"password"=>"123123",
			"sex"=>$i+1,
			"birth"=>((int)(date("Y")-$data["age"])) . "-01-01",
			"nick"=>$data["name"],
			"about"=>$data["about"],
			"dt_added"=>time(),
			"photo"=>"",
			"country_id"=>$data["country_id"],
			"city_id"=>$data["city_id"],
			"source_id"=>$data["source_id"],
		);

		if ($new_user_id = DB::q($query,$ar))
		{
			echo "$new_user_id $data[name] - added<br>\n";
		}
		else
		{
			echo "\n\n<br><br>\n\nFAILED ADDING:\n<br>\n";
			print_r($data);
			echo "<br><br>\n\n";
			break;
		}

		$path = $CONFIG["users_avatars_global_dir"] . "/" . $new_user_id;
		if (!is_dir($path)) mkdir($path,0777,1);

		if ($ph = file_get_contents($data["photo"]))
		{
			$f = fopen($path . "/" . $new_user_id . ".jpg","w");
			fwrite($f,$ph);
			fclose($f);
		}

		if ($ph = file_get_contents($data["photo_sq"]))
		{
			$f = fopen($path . "/" . $new_user_id . "_sq.jpg","w");
			fwrite($f,$ph);
			fclose($f);
		}

		DB::q("update users set photo=:photo where id=:id",array("id"=>$new_user_id,"photo"=>$CONFIG["users_avatars_local_dir"] . "/" . $new_user_id . "/" . $new_user_id . ".jpg"));
	}
}

DEBUG::finish();
DEBUG::out($CONFIG["debug_format"]);

