<?php


class Debug
{
	static protected $oInstance = null;
	protected $start = null;
	protected $end = null;
	protected $dta = array();		// That is for assoc-dt-pointers.
	protected $dts = array();		// That is for default (numeric) dt-pointers.
	protected $log = array();
 
	static public function getInstance() 
	{
		if (isset(self::$oInstance) and (self::$oInstance instanceof self)) 
		{
			return self::$oInstance;
		} 
		else 
		{
			self::$oInstance= new self();
			return self::$oInstance;
		}
	}
	public function __clone() { }
	protected function __construct() { }

	static public function begin()
	{
		$o = self::getInstance();
		$o->start = $o->t();
	}

	static public function start()
	{
		$o = self::getInstance();
		$o->start = $o->t();
	}

	static public function end()
	{
		$o = self::getInstance();
		$o->end = $o->t();
	}

	static public function finish()
	{
		$o = self::getInstance();
		$o->end = $o->t();
	}

	static public function log($message,$type,$dt_pointer=null,$use_dt_pointer=0)
	{
		$o = self::getInstance();

		$ar = array("message"=>$message,"type"=>$type,"time"=>$o->t());

		if ($dt_pointer || $use_dt_pointer)
		{
			if ($dt_pointer)
			{
				$ar["time_start"] = $o->dta[$dt_pointer];
				unset($o->dta[$dt_pointer]);
			}
			else
				$ar["time_start"] = array_pop($o->dts);
		}

		$o->log[] = $ar;
	}

	static public function log_start($dt_pointer=null)
	{
		$o = self::getInstance();
		if ($dt_pointer)
			$o->dta[$dt_pointer] = $o->t();
		else
			$o->dts[] = $o->t();
	}

	static public function log_end($message,$type,$dt_pointer=null)
	{
		self::log($message,$type,$dt_pointer,1);
	}

	static public function out($format="text")
	{
		$o = self::getInstance();

		$out = "FULLTIME: " . ($o->end - $o->start);
		foreach($o->log as $ar)
			$out .= "\n\n" . ($ar[type]?"[" . $ar[type] . "] ":"") . ($ar[time_start]?"[" . sprintf("%01.4f",$ar[time]-$ar[time_start]) . "] ":"") . $ar[message];

		if ($format == "html")
			$out = "<div style='margin: 10px; border: 1px solid #dedede; padding: 5px; font-size: 0.85em; text-align: left;'><strong>DEBUG INFO</strong><br /><br />\n\n" . str_replace("\n","<br />",$out) . "\n\n</div>";
		else
			$out = "\n\n<!--\n=====DEBUG INFO=========\n\n" . $out . "\n\n=====DEBUG INFO END=====\n-->\n";

		return $out;
	}

	static protected function t()
	{
		list($usec, $sec) = explode(" ",microtime()); return ((float)$usec + (float)$sec); 
	}
}

