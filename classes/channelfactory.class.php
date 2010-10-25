<?php

class channelfactory
{
	static protected $oInstance = null;
	protected $CONFIG = array();

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

	static public function setConfig($CONFIG)
	{
		$o = self::getInstance();
		$o->CONFIG = $CONFIG;
	}

	static public function create($app)
	{
		$o = self::getInstance();

		$CID = $app->CID;

		if (!$CID) throw new Exception(__CLASS__ . "::" . __METHOD__ . ": CID is not set");
		if (!isset($o->CONFIG[$CID])) throw new Exception(__CLASS__ . "::" . __METHOD__ . ": CID $CID not found in channels in config");

		$obj = new $CID($app);
		if ($obj instanceof channel) {
			return $obj;
		}
		else throw new Exception(__CLASS__ . "::" . __METHOD__ . ": class $CID is not instance of channel class");
	}
}
