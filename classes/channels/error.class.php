<?php

class Error extends Channel
{
	public function run_overall()
	{
		$msg = $this->app->CHDATA[$this->app->CID]["msg"];
		$type = $this->app->CHDATA[$this->app->CID]["type"];

		if ($type == "signin_required")
			$tmp = "<strong><!--[Error]-->:</strong> " . $msg . "<br /><a href='" . $this->app->makeLink(array("CID"=>"profile","page"=>"signin"),null,1) . "'><!--[Signin]--></a>&nbsp;|&nbsp;<a href='" . $this->app->makeLink(array("CID"=>"profile","page"=>"signup"),null,1) . "'><!--[Signup]--></a>";
		else
			$tmp = "<strong><!--[Error]-->:</strong> " . $msg . "<br /><a href='"  . $this->app->makeLink("CID","main",1) . "'><!--[Goto_mainpage]--></a>";

		$this->data["content"] = displayError($tmp,0,1);
	}

}

