<?php

abstract class channel
{
	protected $app = null;
	protected $data = array();		// Here is data generated by channel
	protected $adata = array();		// Here is the result of an action - action error, success, result, autoredirect and so on.
	protected $CONFIG = array();

	public function run()
	{
		if ($this->CONFIG["auth_required"] && !$this->app->getUser()) throw new CHException("<!--[Signin_required]-->");
		if ($this->CONFIG["auth_denied"] && $this->app->getUser()) throw new CHException("<!--[User_is_already_signed_in]-->");

		$tmp = "run_overall";
		if (method_exists(get_class($this),$tmp) && !$this->app->is_ajax())
			return $this->$tmp();

		if ($this->CONFIG["pages"][$this->app->page]["auth_required"] && !$this->app->getUser()) throw new CHException("<!--[Signin_required]-->");
		if ($this->CONFIG["pages"][$this->app->page]["auth_denied"] && $this->app->getUser()) throw new CHException("<!--[User_is_already_signed_in]-->");

		if ($this->app->action)
		{
			$tmp = "action_" . ($this->app->is_ajax()?"ajax":$this->app->page) . "_" . $this->app->action;
			if (method_exists(get_class($this),$tmp))
			{
				DEBUG::log("Call " . $tmp,$this->app->CID);
				try
				{
					$this->adata = $this->$tmp();
					if (!$this->adata["error"] && $this->adata["return"]) return true;
				}
				catch (Exception $e)
				{
					$this->adata["error"] = $e->getMessage();
				}
			}
			else throw new CHException("Action " . $this->app->action . " for page " . $this->app->page . " doesn't exist","Channel " . $this->app->CID);
		}
		
		if ($this->app->is_ajax()) 
		{
			$this->data = $this->adata;
			return true;
		}

		$tmp = "run_" . $this->app->page;
		if (method_exists(get_class($this),$tmp))
		{
			$ar = $this->$tmp();
			if (!$this->data["title"])
				$this->data["title"] = $this->CONFIG["pages"][$this->app->page]["title"];
			return $ar;
		}
		else throw new CHException("Page " . $this->app->page . " doesn't exist","Channel " . $this->app->CID);
	}

	public function __construct($app)
	{
		$this->app = $app;
		$this->CONFIG = $this->app->getCONFIG("channels",$this->app->CID);
		DEBUG::log("CID=" . $this->app->CID . ", page=" . $this->app->page . ", action=" . $this->app->action,__METHOD__);
	}

	public function getData()
	{
		return $this->data;
	}
}
