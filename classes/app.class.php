<?php

/*
 * This is the main router class.
 */

class app
{
	static private $oInstance = null;
	protected $data = array();		// The results of all subrouters (channels) works are appended here.
	protected $out = "";			// Final HTML-code that is build of data and template in buildOut method.
	protected $CONFIG = array();		// We try to make all object as more local as possible. Here's copy from config.php.
	protected $user = null;			// Authorized user stored here.
	protected $is_ajax = false;

	public $CHDATA = array();		// This is array that contains data, grouped by channels (subrouters), channels transfer data from one to another through this array.
	public $CID = null;			// This is channel (subrouter) id.
	public $page = null;
	public $action = null;	


	static public function getInstance()
	{
		if (isset(self::$oInstance) && (self::$oInstance instanceof self)) {
			return self::$oInstance;
		}
		else {
			self::$oInstance = new self();
			return self::$oInstance;
		}
	}
	public function __clone() { }
	protected function __construct() { }

	public function setConfig($CONFIG)
	{
		DB::setConfig($CONFIG["db"]);
		ChannelFactory::setConfig($CONFIG["channels"]);
		Auth::setConfig($CONFIG["auth"]);
		$this->CONFIG = $CONFIG;
	}

	public function run()
	{
		Auth::run();
		if ($user = Auth::getUser())
			$this->user = $user;
		
		$this->parseRequest();

		$old_CID = null;
		$is_called_common = 0;

		while ($this->CID != $old_CID) 
		{
			$old_CID = $this->CID;
			try
			{
				$channel = ChannelFactory::create($this);
				$channel->run();
				$this->storeData($channel->getData());
				if (($this->CID == $old_CID) && !$is_called_common && !$this->is_ajax) {
					$is_called_common = 1;
					$this->CID = "common";
				}
			}
			catch (CHException $e)
			{
				if ($this->is_ajax)
					$this->data["error"] = "[" . $e->getType() . "] " . $e->getMessage();
				else
					$this->setError($e->getMessage(),$e->getType());
			}
		}

		if ($this->is_ajax)
			$this->buildAjaxOut();
		else
			$this->buildOut();
	}

	public function parseRequest()
	{
		if (isset($_REQUEST["CID"])) {
			if (in_array($_REQUEST["CID"],$this->CONFIG["channels"]))
				$this->CID = $_REQUEST["CID"];
			else 
				$this->setError("Channel " . $_REQUEST["CID"] . " doesn't exist in config","common");
		}

		if (isset($_REQUEST["page"])) {
			$this->page = $_REQUEST["page"];
		}

		if (isset($_REQUEST["action"])) { 
			$this->action = $_REQUEST["action"];
		}

		$surl = strtolower($_SERVER["REQUEST_URI"]);
		$surl = preg_replace("/\?.*$/","",$surl);
		$ar = explode("/",$surl);
		foreach($ar as $v)
		{
			if (!$v) continue;

			$uri_found = 0;

			if (!isset($this->CID)) {
				foreach($this->CONFIG["channels"] as $i => $rw)
					if ($rw["domain"] == $v) {
						$uri_found = 1;
						$this->CID = $i;
						continue;
					}
			}

			if ($uri_found) continue;

			if (!isset($this->page)) {
				$this->page = $v;
			}

			if (preg_match("/u(\d+)\.html/",$v,$m))
				$this->CHDATA[$this->CID]["user_id"] = $m[1];
		}

		if (!$this->CID) $this->CID = $this->CONFIG["default_channel"];
		if (!$this->page) $this->page = $this->CONFIG["default_page"];
	
		if (isset($_REQUEST["user_id"])) {
			$this->CHDATA[$this->CID]["user_id"] = $_REQUEST["user_id"];
		}
	}

	public function makeLink($a1=null,$a2=null,$disable_default=0)
	{	
		if (!$disable_default)
			$vrs = array("CID"=>$this->CID,"page"=>$this->page);

		if (is_string($a1) && isset($a2)) 
			$vrs[$a1] = $a2;
		elseif (is_array($a1)) 
			$vrs = $a1;
		elseif (is_string($a1))
		{
			$ar = explode("&",$a1);
			foreach($ar as $v)
			{
				$arr = explode("=",$v);
				$p = trim($arr[0]);
				$v = trim($arr[1]);
				$vrs[$p] = $v;
			}
		}
	
		$out = "/";
		$params = array();

		foreach($vrs as $i => $v)
		{
			if (!$v) continue;

			if ($bpr) { $params[] = "$i=$v"; continue; }

			if ($i == "CID" && isset($this->CONFIG["channels"][$v]["domain"]))
				$out .= $this->CONFIG["channels"][$v]["domain"] . "/";
			elseif ($i == "page" && isset($this->CONFIG["channels"][$vrs["CID"]]["pages"][$v]["domain"]))
				$out .= $this->CONFIG["channels"][$vrs["CID"]]["pages"][$v]["domain"] . "/";
			elseif ($i == "user_id")
			{
				$out .= "u" . $v . ".html";
				$bpr = 1;
			}
			else
				$params[] = "$i=$v";
		}

		$out = preg_replace("/\/+/","/",$out);

		if ($params)
			$out .= "?" . join("&",$params);

		return $out;
	}

	public function getUser($field=null)
	{
		if ($this->user && $field && $this->user[$field]) return $this->user[$field];
		if ($this->user && !$field) return $this->user;
		return null;
	}

	public function getCONFIG($var1=null,$var2=null,$var3=null)
	{
		if (isset($var1)) {
			if (isset($var2)) {
				if (isset($var3))
					return $this->CONFIG[$var1][$var2][$var3];
				else
					return $this->CONFIG[$var1][$var2];
			}
			else return $this->CONFIG[$var1];
		}
		else return $this->CONFIG;
	}

	public function setError($msg,$type=null)
	{
		$this->CID = "error";
		$this->CHDATA[$this->CID]["type"] = $type;
		$this->CHDATA[$this->CID]["msg"] = $msg;
		DEBUG::log("msg=" . $msg . ", type=" . $type,__METHOD__);
	}

	public function setSuccess($msg,$autoredirect=0,$autoredirect_url=null)
	{
		$this->CID = "success";
		$this->CHDATA[$this->CID]["autoredirect"] = $autoredirect;
		$this->CHDATA[$this->CID]["autoredirect_url"] = $autoredirect_url;
		$this->CHDATA[$this->CID]["msg"] = $msg;
		DEBUG::log("msg=" . $msg,__METHOD__);
	}

	public function setAjax($b)
	{
		if ($b)
			$this->is_ajax = true;
	}

	public function isAjax()
	{
		return $this->is_ajax;
	}

	public function is_ajax()
	{
		return $this->is_ajax;
	}

	public function display()
	{
		echo $this->out;
	}

	protected function storeData($ar)
	{
		if (!$ar || !is_array($ar)) return false;

		if ($ar["title"]) $this->data["title"] .= ($this->data["title"]?$this->CONFIG["title_sep"]:"") . $ar["title"];
		unset($ar["title"]);

		foreach($ar as $i => $v)
		{
			if (is_array($v)) {
				if (is_array($this->data[$i]))
					$this->data[$i] = array_merge($this->data[$i],$v);
				else
					$this->data[$i] = $v;
			}
			else
				$this->data[$i] .= $v;
		}

		return $this->data;
	}

	protected function buildOut()
	{
		if (is_array($this->data["js_scripts"])) 
			foreach($this->data["js_scripts"] as $i => $ii)
				$this->data["scripts"] .= ($this->data["scripts"]?"\n":"") . "<script type='text/javascript' src='$i'></script>";

		if (is_array($this->data["css_styles"]))
			foreach($this->data["css_styles"] as $i => $ii)
				$this->data["styles"] .= ($this->data["styles"]?"\n":"") . "<link rel='stylesheet' type='text/css' href='$i' />";

		$tmpl = load_file($this->CONFIG["tmpl"]);

		foreach($this->data as $repl => $v)
			$tmpl = str_replace("<!-- $repl -->",$v,$tmpl);

		$tmpl = preg_replace_callback("/<!--\[([^\[\]]+)\]-->/","use_dictionary_callback",$tmpl);

		$this->out = $tmpl;
	}

	protected function buildAjaxOut()
	{
		foreach($this->data as $repl => $v)
		{
			$out .= "var $repl=\"" . my_js_conv($v) . "\"; ";
		}
		
		$out = preg_replace_callback("/<!--\[([^\[\]]+)\]-->/","use_dictionary_callback",$out);

		$this->out = $out;
	}
}
