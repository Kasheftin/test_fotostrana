<?php

class Success extends Channel
{
	public function run_overall()
	{
		$data = $this->app->CHDATA[$this->app->CID];

		$tmp = "<strong><!--[Success]-->:</strong> " . $data["msg"];

		if (!$data["autoredirect_url"]) $data["autoredirect_url"] = $this->app->makeLink("CID","main",1);

		if (isset($data["autoredirect"]) && $data["autoredirect"] == 0)
		{
			header("Location: " . $data["autoredirect_url"]);
			exit();
		}

		if ($data["autoredirect"])
		{

			$tmp .= "
				<br /><!--[Autoredirect_in]--> <span id='autoredirect_timer'>" . $data["autoredirect"] . "</span> <!--[sec]-->. <a href='" . $data["autoredirect_url"] . "'><!--[Goto_now]-->.</a>
				<script type='text/javascript'>
				var autoredirect_time = " . $data["autoredirect"] . ";
				function do_autoredirect()
				{
					autoredirect_time--;
					if (autoredirect_time <= 0)
					{
						document.location = '" . $data["autoredirect_url"] . "';
					}
					document.getElementById('autoredirect_timer').innerHTML = autoredirect_time;
				}
				setInterval('do_autoredirect()',1000);
				</script>
			";
		}

		$this->data["content"] = displaySuccess($tmp,0,1);
		$this->data["title"] = "Success: " . ($data["type"]?"[" . $data[type] . "] ":"") . $data["msg"];
	}
}

