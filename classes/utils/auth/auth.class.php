<?php

class Auth
{
	static protected $oInstance = null;
	protected $CONFIG = array();
	protected $user = null;

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

	static public function run()
	{
		$o = self::getInstance();
		
		$SID = $_COOKIE["SID"];
		if (!$SID) return null;

		try
		{
			if ($SID && !preg_match("/^[a-z0-9]+$/",$SID)) throw new Exception("SID contains incorrect characters");
			$SID = preg_replace("/[^a-z0-9]/","",$SID);
			if (!$SID) throw new Exception("SID is empty");

			if ($rw = DB::f1("select * from users_sessions where sid=:SID",array("SID"=>$SID))) {
				$rw_session = $rw;
			}
			else throw new Exception("Auth session not found");

			$Q = new UsersExec();
			$Q->where("id",$rw_session["user_id"]);

			if ($rw = $Q->f1())
			{
				$user = $rw;
				$user["rw_session"] = $rw;

				if ($user["settings"]["rememberme"])
					setcookie("SID",$SID,time() + $o->CONFIG["rememberme_time"],"/",$o->CONFIG["cookie_domain"]);
				else
					setcookie("SID",$SID,0,"/",$o->CONFIG["cookie_domain"]);

				if ($o->CONFIG["enable_online"]) {
					$online_file = $o->CONFIG["online_cache_dir"] . "/" . (((int)(time()/$o->CONFIG["online_interval"]))%2) . "/" . $user->id;
					touch($online_file);
				}

				 $o->user = $user;
				return true;
			}
			else throw new Exception("User id=" . $rw_session["user_id"] . " not found");
		}
		catch (Exception $e)
		{
			DEBUG::log("Auth Exception: " . $e->getMessage(),__CLASS__);
			if ($SID)
				DB::q("delete from users_sessions where sid=:SID",array("SID"=>$SID));
			$_COOKIE["SID"] = "";
			setcookie("SID","",time()-86400,"/",$o->CONFIG["cookie_domain"]);
		}
	}

	static public function getUser()
	{
		$o = self::getInstance();
		return $o->user;
	}
}
