<?php


function req($protocol, $host, $url, $vars = "", $cookies = "")
{
	$myvars = array(
		"Accept" => "image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, application/vnd.ms-excel, application/msword, application/x-shockwave-flash, application/vnd.ms-powerpoint, */*",
	  	"Accept-Language" => "ru", 
		"User-Agent" => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)",
		"Accept-Charset" => "windows-1251"
	);

	if (is_array($cookies))
	{
		reset($cookies);
		while (list(,$v) = each($cookies))
		{
			$v = preg_replace("/path=[^\s]*/","",$v);
			$cook_out .= "Cookie: " . trim($v) . "\n";
		}
	}
	elseif ($cookies) 
	{
		$cookies = preg_replace("/path=[^\s]*/","",$cookies);
		$cook_out = "Cookie: " . trim($cookies) . "\r\n";
	}

	$content = "";


	if (isset($vars))
	{
		if (!is_array($vars) && $vars)
		{
			$ar = split("&",$vars);
			$vars = array();
			foreach($vars as $v)
			{
				$ar = split("=",$v);
				$vars[$ar[0]] = $ar[1];
			}
		}

		if (is_array($vars) && count($vars))
			foreach ($vars as $k => $v)
			{
				if (is_array($v))
					foreach($v as $kk => $vv)
						$content .= ($content?"&":"") . $k . "%5b;" . ($kk?$kk:"") . "%5d;=" . urlencode($vv);
				else
					$content .= ($content?"&":"") . $k . "=" . urlencode($v);
			}
	}


	$request .=	"$protocol $url HTTP/1.1\r\n";
	$request .=	"Host: $host\r\n";
	$request .=	"Accept: " . $myvars["Accept"] . "\r\n";
	$request .=	"Accept-Language: " . $myvars["Accept-Language"] . "\r\n";
	$request .=	"Accept-Charset: " . $myvars["Accept-Charset"] . "\r\n";
	$request .=	$cook_out;
	$request .=	"Cache-Control: no-cache\r\n";
	$request .= 	"Pragma: no-cache\r\n";
	$request .= 	"Connection: close\r\n";
	$request .= 	"User-Agent: " . $myvars["User-Agent"] . "\r\n";
	$request .= 	"Content-Type: application/x-www-form-urlencoded\r\n";
	$request .= 	"Content-Length: " . strlen($content) . "\r\n";
	$request .= 	"\r\n";
	$request .=	$content;


	if ($fp = @fsockopen($host, 80, $errn, $errstr)) 
	{
		fputs($fp, $request);
		while (!feof($fp))
			$s .= fgets($fp, 128);
	} else
		$s = "error[$errn]: $errstr";

	return $s;
}


function get_data($s)
{
	list ($header, $data) = explode("\r\n\r\n", $s, 2);
	if (preg_match("/Transfer-Encoding: chunked/i", $header))
		$out = read_chunks($data);
	else
		$out = $data;
	return $out;
}


function read_chunks($str)
{
	$len = strlen($str);
	$s = $str;
	while (($pos < $len) && (strlen($s) > 0))
	{
		$a = explode("\r\n", $s, 2);
		$out .= substr($a[1], 0, hexdec($a[0]));
		$s = substr($a[1], hexdec($a[0])+2);
	}

	return $out;
}


?>