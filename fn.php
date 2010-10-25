<?php

function __autoload($class_name)
{
	GLOBAL $CONFIG;

	$class_file = strtolower($class_name . $CONFIG[classes_ext] . ".php");
	$file_path = $CONFIG[classes_dir] . "/" . $class_file;
	if (!is_file($file_path))
		$file_path = find_file_req($CONFIG[classes_dir],$class_file);

	if (isset($file_path) && is_file($file_path))
	{
		require_once($file_path);
		if (class_exists($class_name))
		{
			return true;
		}
		else
		{
			throw new Exception("Class $class_name doesn't exist in file $file_path");
		}	
	}
	else
	{
		throw new Exception("File $class_file doesn't exist for class $class_name");
	}

	return null;
}

function find_file_req($path,$file,$limit=-1)
{
	$dir = dir($path);

	while (($entry = $dir->read()) !== false)
	{
		if ($entry == "." || $entry == "..") continue;

		$p = $path . "/" . $entry;

		if (is_dir($p) && ($limit != 0))
		{
			$tmp = find_file_req($p,$file,$limit-1);
			if (isset($tmp)) 
			{
				return $tmp;
			}
		}
		elseif (is_file($p) && ($file == $entry))
		{
			return $p;
		}
	}

	return null;
}                    

function load_file($local_path)
{
	GLOBAL $CONFIG;

	if (!preg_match("/^\//",$local_path))
		$path = $CONFIG["main_dir"] . "/" . $local_path;
	else
		$path = $local_path;

	if ($f = fopen($path,"rb")) {
		$str = fread($f,filesize($path));
		fclose($f);
	}
	else throw new Exception("Can't read $path file");

	return $str;
}

function order_by($data,$field)
{
	$code = "return strnatcmp(\$a['$field'], \$b['$field']);";
	usort($data,create_function('$a,$b',$code));
	return $data;
}


// All values have to be escaped before inserting in any form fields.

function my_escape($ar)
{
	if (is_array($ar))
	{
		if (!count($ar))
			$out = array();
		foreach($ar as $i => $v)
			$out[my_escape_tmp($i)] = my_escape($v);
	}
	else
		$out = my_escape_tmp($ar);
	return $out;
}
function my_escape_tmp($v)
{
	if ($v === 0) return $v;
	if (get_magic_quotes_gpc())
		$v = stripslashes($v);
	$v = htmlspecialchars($v,ENT_QUOTES);
	return $v;
}
function my_form_escape($ar)
{
	return my_escape($ar);
}

function my_js_conv($out)
{
	$out = preg_replace("/\r/","&bs;r",$out);
	$out = preg_replace("/\t/","&bs;t",$out);
	$out = preg_replace("/\n/","&bs;n",$out);
	$out = preg_replace("/\"/","\\\"",$out);
	return $out;
}

function my_unescape($ar) // Восстанавливает заэскейплинную функцией escape в javascript строчку, которая обычно образуется при ajax с русскими буквами
{
	if (is_array($ar))
	{
		foreach($ar as $i => $v)
			$ar[$i] = my_unescape($v);
	}
	else
		$ar = convert_unicode($ar);
	return $ar;
}
function convert_unicode($t) 
{ 
	GLOBAL $host_data;
	return preg_replace( '#%u([0-9A-F]{4})#se','iconv("UTF-16BE",$host_data[encoding],pack("H4","$1"))', $t ); 
} 

function my_strip_tags($ar)
{
	if (is_array($ar))
	{
		$tmp_ar = $ar;
		foreach($tmp_ar as $i => $v)
			$ar[$i] = my_strip_tags($v);
	}
	else
	{
		$ar = strip_tags($ar);
	}
	return $ar;
}


// Working with dictionary.txt

function load_dictionary($full=0)
{
	$s = load_file("dictionary.txt");
	$dict = array();

	$ar = explode("\n",$s);
	foreach($ar as $i => $str)
	{
		if (!$str) continue;
		if (preg_match("/^(#|\/\/|\\\\)/",$str)) continue;
		$arr = explode("~!~",$str);
		$arr[0] = trim($arr[0]);
		if (($arr[0] && $arr[1]) || ($arr[0] && $full))
			$dict[$arr[0]] = trim($arr[1]);
	}
	return $dict;
}
function use_dictionary_callback($a)
{
	GLOBAL $dictionary;

	if (!$dictionary) $dictionary = load_dictionary();

	$var = $a[1];
	$fl1 = substr($var,0,1);
	$var = strtolower($var);
	$fl2 = substr($var,0,1);

	if ($out = $dictionary[$var])
		if ($fl1 != $fl2)
			return my_fl_strtoupper($out);
		else
			return $out;
	if ($fl1 != $fl2)
		return my_fl_strtoupper($var);
	return $var;
}


//String functions//

function get_word_ending($i) // добавляет числовое окончание: 1 комментарий => 1, 2 комментария => 2, 5 комментариев => 3
{
	if ($i%10 == 1 && $i%100 != 11) return 1;
	if ($i%10 > 1 && $i%10 < 5 && ($i%100 < 11 || $i%100 > 14)) return 2;
	return 3;
}

function my_strtolower($str)
{
	$str = mb_strtolower($str);
	return $str;
}

function my_strtoupper($str)
{
	$str = mb_strtoupper($str);
	return $str;
}

function my_fl_strtoupper($str)
{
	$str = trim($str);
	$str = mb_strtoupper(mb_substr($str,0,1)) . mb_substr($str,1,mb_strlen($str));
	return $str;
}

function my_substr($str,$n=200)
{
	if (mb_strlen($str) < $n) return $str;
	$str = mb_substr($str,0,$n);

	$out = preg_replace("/\.[^\.]*$/","...",$str);

	if ((mb_strlen($out) == $n) || (mb_strlen($out) < $n/2))
	{
		$out = preg_replace("/\s+\S+$/","...",$str);
	}

	return $out;
}

function makeTitle($str)
{
	$str = trim($str);

	$ar = array();
	$cnt = 0;
	while (preg_match("/\[pre\]((.|\n)*?)\[\/pre\]/",$str,$m))
	{
		$ar[$cnt] = $m[1];
		$str = preg_replace("/\[pre\]((.|\n)*?)\[\/pre\]/","~!~$cnt~!~",$str,1);
		$cnt++;
	}             

	$str = preg_replace("/(\(|\))/"," ",$str);
	$str = preg_replace("/\"/","'",$str);
	$str = preg_replace("/(\s*,\s*)+/",", ",$str);
	$str = preg_replace("/\s*,\s*$/","",$str);
	$str = preg_replace("/^\s*,\s*/","",$str);
	$str = preg_replace("/\s+/"," ",$str);
	$str = my_fl_strtoupper($str);
	$str = preg_replace("/('|\")/","",$str);

	for ($i = 0; $i < $cnt; $i++)
	{
		$str = str_replace("~!~$i~!~",$ar[$i],$str);
	}

	return $str;
}

function printDate($dt=0,$mode="")
{
	if (preg_match("/^(\d\d\d\d)\-(\d\d)\-(\d\d)/",$dt,$m))
	{
		$dt = mktime(0,0,0,$m[2],$m[3],$m[1]);
	}

	if (!$dt) $dt = time();
	$date = date("Y-m-d",((int)$dt));
	$ar = explode("-",$date);
	$ar[3] = date("w",((int)$dt));
	$ar[2] = (int)$ar[2];

	if ($mode == "added") // Типа новость добавлена "сегодня, 21 июля"
	{
		if ($date == date("Y-m-d"))
			$out = "<!--[today]-->, ";
		elseif ($date == date("Y-m-d",time()-86400))
			$out = "<!--[yesterday]-->, ";
		elseif ($date == date("Y-m-d",time()+86400))
			$out = "<!--[tomorrow]-->, ";
	}

	if ($mode == "short") // Короткая запись вида 21.07
	{
		$out = $ar[2] . "." . $ar[1];
	}
	elseif ($mode == "short2")
	{
		if ($date == date("Y-m-d"))
			$out = "<!--[today]-->";
		elseif ($date == date("Y-m-d",time()-86400))
			$out = "<!--[yesterday]-->";
		elseif ($date == date("Y-m-d",time()+86400))
			$out = "<!--[tomorrow]-->";
		else $out = $ar[2] . "." . $ar[1];
	}
	elseif ($mode == "weekday") // Запись вида "воскресенье 22 октября 2009"
	{
		$out = "<!--[weekday" . ((int)$ar[3]) . "]--> " . $ar[2] . " <!--[month_" . ((int)$ar[1]) . "2]--> " . $ar[0];
	}
	elseif ($mode == "weekday_short") // Запись вида "воскресенье 22 октября"
	{
		$out = "<!--[weekday" . ((int)$ar[3]) . "]--> " . $ar[2] . " <!--[month_" . ((int)$ar[1]) . "2]-->";
	}
	elseif ($mode == "weekday_short2") // Запись вида на "среду 22 октября"
	{
		$out = "<!--[weekday" . ((int)$ar[3]) . "2]--> " . $ar[2] . " <!--[month_" . ((int)$ar[1]) . "2]-->";
	}
	elseif ($mode == "weekday_large") // Запись вида "сегодня воскресенье 22 октября";
	{
		if ($date == date("Y-m-d"))
			$out = "<!--[today]--> ";
		elseif ($date == date("Y-m-d",time()-86400))
			$out = "<!--[yesterday]--> ";
		elseif ($date == date("Y-m-d",time()+86400))
			$out = "<!--[tomorrow]--> ";
		$out .= "<!--[weekday" . ((int)$ar[3]) . "]--> " . $ar[2] . " <!--[month_" . ((int)$ar[1]) . "2]-->";
	}
	elseif ($mode == "weekday_full") // Запись вида "сегодня воскресенье 22 октября 2009";
	{
		if ($date == date("Y-m-d"))
			$out = "<!--[today]--> ";
		elseif ($date == date("Y-m-d",time()-86400))
			$out = "<!--[yesterday]--> ";
		elseif ($date == date("Y-m-d",time()+86400))
			$out = "<!--[tomorrow]--> ";
		$out .= "<!--[weekday" . ((int)$ar[3]) . "]--> " . $ar[2] . " <!--[month_" . ((int)$ar[1]) . "2]--> " . $ar[0];
	}
	elseif ($mode == "today") // Запись вида "сегодня","завтра";
	{
		if ($date == date("Y-m-d"))
			$out = "<!--[today]-->";
		elseif ($date == date("Y-m-d",time()-86400))
			$out = "<!--[yesterday]-->";
		elseif ($date == date("Y-m-d",time()+86400))
			$out = "<!--[tomorrow]-->";
	}
	elseif ($mode == "weekday_only")
	{
		$out .= "<!--[weekday" . ((int)$ar[3]) . "]-->";
	}
	elseif ($mode == "noyear")
	{
		$out .= $ar[2] . " <!--[month_" . ((int)$ar[1]) . "2]-->";
	}
	else
	{
		$out .= $ar[2] . " <!--[month_" . ((int)$ar[1]) . "2]-->";
		if (date("Y") != $ar[0]) $out .= " " . $ar[0];
	}
	return $out;
}

function printDateTime($dt,$mode="")
{
	$time = date("H:i",$dt);
	$date = date("Y-m-d",$dt);
	$ar = explode("-",$date);

	if ($mode != "time")
		$out = printDate($dt,$mode) . ", " . $time;
	elseif ($date == date("Y-m-d"))
		$out = "<!--[today]--> <!--[at]--> " . $time;
	elseif ($date == date("Y-m-d",time()-86400))
		$out = "<!--[yesterday]--> <!--[at]--> " . $time;
	else
		$out = printDate($dt) . " <!--[at]--> " . $time;

	return $out;
}

function printTime($dt)
{
	if (!preg_match("/^(\d\d):(\d\d)/",$dt,$m))
		$dt = date("H:i",$dt);
	$ar = explode(":",$dt);
	$out = $ar[0] . ":" . $ar[1];
	return $out;
}
				
function displayError($str,$print_title=1,$centered=0)
{
	$out = "
		<div class='ui-widget'>
			<div class='ui-state-error ui-corner-all' style='padding: .7em;'> 
				<p><span class='ui-icon ui-icon-alert' style='float: left; margin-right: .3em;'></span>" . ($print_title?"<strong><!--[Error]-->:</strong> ":"") . $str . "</p>
			</div>
		</div>
	";

	if ($centered)
		$out = "<center><div class='centerForm'>" . $out . "</div></center>";

	return $out;
}

function displaySuccess($str,$print_title=1,$centered=0)
{
	$out = "
		<div class='ui-widget'>
			<div class='ui-state-highlight ui-corner-all' style='padding: .7em;'> 
				<p><span class='ui-icon ui-icon-info' style='float: left; margin-right: .3em;'></span>" . ($print_title?"<strong><!--[Success]-->:</strong> ":"") . $str . "</p>
			</div>
		</div>
	";

	if ($centered)
		$out = "<center><div class='centerForm'>" . $out . "</div></center>";

	return $out;
}

function set_by_id($rws,$field="id")
{
	if (!is_array($rws) || !$rws) return $rws;
	$tmp_rws = array();
	foreach($rws as $rw)
		$tmp_rws[$rw[$field]] = $rw;
	return $tmp_rws;
}


function my_imagecreate($file)
{
	if ($file[type] == "image/jpeg" || $file[type] == "image/pjpeg")
	{
		$src_img = imagecreatefromjpeg($file[tmp_name]);
		$src_type = "jpg";
	}
	elseif ($file[type] == "image/png")
	{
		$src_img = imagecreatefrompng($file[tmp_name]);
		$src_type = "png";
	}
	elseif ($file[type] == "image/gif")
	{
		$src_img = imagecreatefromgif($file[tmp_name]);
		$src_type = "gif";
	}
	else throw new Exception("<!--[File_type_is_not_supported]-->");

	if (!$src_img) throw new Exception("<!--[Cannot_create_image_from_file]-->");

	$full_w = imageSX($src_img);
	$full_h = imageSY($src_img);

	if (!$full_w || !$full_h) throw new Exception("<!--[Cannot_get_image_width_or_height]-->");

	return array($src_img,$src_type,$full_w,$full_h);
}

function my_imagesave($dst_img,$src_type,$filepath)
{
	if ($src_type=="jpg")
	{
		if (!@imageJPEG($dst_img,$filepath)) throw new Exception("<!--[Cant_save_jpg_file]-->");
	}
	elseif ($src_type=="gif")
	{
		if (!@imageGIF($dst_img,$filepath)) throw new Exception("<!--[Cant_save_gif_file]-->");
	}
	elseif ($src_type=="png") 
	{
		if (!@imagePNG($dst_img,$filepath)) throw new Exception("<!--[Cant_save_png_file]-->");
	}
	else throw new Exception("<!--[File_type_is_not_supported_while_saving]-->");
}

function getThumbnailParams($thumbset,$full_w,$full_h)
{
	$new_w = $new_h = $cut_x = $cut_y = 0;
	$cut_w = $full_w;
	$cut_h = $full_h;

	if ($thumbset[type] == "width")
	{
		$new_w = $thumbset[w];
		$new_h = (int)($full_h / $full_w * $new_w);
	}
	elseif ($thumbset[type] == "height")
	{
		$new_h = $thumbset[h];
		$new_w = (int)($full_w / $full_h * $new_h);
	}
	elseif ($thumbset[type] == "inbox")
	{
		if ($thumbset[h] / $thumbset[w] > $full_h / $full_w)
		{
			$new_w = $thumbset[w];
			$new_h = (int)($full_h / $full_w * $new_w);
		}
		else
		{
			$new_h = $thumbset[h];
			$new_w = (int)($full_w / $full_h * $new_h);
		}
	}
	elseif ($thumbset[type] == "sq")
	{
		$new_w = $thumbset[w];
		$new_h = $thumbset[h];

		if ($new_w/$new_h > $full_w/$full_h)
		{
			$cut_w = $full_w;
			$cut_x = 0;
			$cut_h = $full_w/$new_w*$new_h;
			$cut_y = (int)(($full_h-$cut_h)/2);
		}
		else
		{
			$cut_y = 0;
			$cut_h = $full_h;
			$cut_w = $full_h/$new_h*$new_w;
			$cut_x = (int)(($full_w-$cut_w)/2);
		}
	}
	else throw new Exception("<!--[Unknown_thumbnail_type]-->: $thumbset[type]");
			
	if (!$new_h || !$new_w) throw new Exception("<!--[Failed_generating_new_w_and_new_h]-->");

	if (($new_h>=$full_h) && ($new_w>=$full_w))
	{
		$new_h = $full_h;
		$new_w = $full_w;
	}

	return array($new_w,$new_h,$cut_x,$cut_y,$cut_w,$cut_h);
}

function my_getnewfilename($filepath,$str,$src_type)
{
	$str = preg_replace("/\.[^\.]+$/","",$str);
	$ar = preg_split("/\//",$str);
	$str = $ar[count($ar)-1];
	$str = preg_replace("/[^a-zA-Z0-9]/","",$str);
	if (!strlen($str))
		$str = "pic";
	
	while (file_exists($filepath . $str . "." . $src_type))
	{
		if (preg_match("/(\d+)$/",$str,$m))
		{
			$str = preg_replace("/(\d+)$/","",$str) . ($m[1]+1);
		}
		else
		{
			$str = $str . "2";
		}
	}			
	return $str;
}


function makePages($url,$start,$rep_on_page,$all)
{
	if ($rep_on_page>=$all) return "";
	if (preg_match("/\?/",$url)) $url .= "&start=";
	else $url .= "?start=";

	$start_page = $start-5*$rep_on_page;
	if ($start_page<0) $start_page = 0;
	$end_page = $start+5*$rep_on_page;
	if ($end_page>$all) $end_page = $all;
	$list .= "<div class='clear'></div><div class='pages'>";
	if ($start>0) $list .= "<a href='$url" . ($start-$rep_on_page) . "'>&lt;&lt;</a> ";
	for ($i = $start_page; $i < $end_page; $i += $rep_on_page)
	{
		if ($i == $start)
			$list .= "<a class='sel' href='$url" . $i . "'>" . ((int)($i/$rep_on_page+1)) . "</a> ";
		else
			$list .= "<a href='$url" . $i . "'>" . ((int)($i/$rep_on_page+1)) . "</a> ";
	}
	if ($start + $rep_on_page < $all) $list .= " <a href='$url" . ($start+$rep_on_page) . "'>&gt;&gt;</a>";
	$list .= "</div>";
	return $list;
}




