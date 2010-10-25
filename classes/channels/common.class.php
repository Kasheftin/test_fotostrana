<?php

class Common extends Channel
{
	public function run_overall()
	{
		$this->data["topmenu"] = $this->displayTopMenu();
		$this->data["profilelinks"] = $this->displayProfileLinks();
		$this->data["bottom"] = $this->displayBottom();
		$this->data["title"] = $this->CONFIG["title"];
		$this->data["selectgeo"] = $this->displaySelectGeo();
		$this->data["country_id"] = (int)$this->app->getUser("country_id");
		$this->data["city_id"] = (int)$this->app->getUser("city_id");
	}

	protected function displayTopMenu()
	{
		$out = "";
		$ar = order_by($this->app->getCONFIG("channels"),"topmenu_position");
		foreach($ar as $rw)
		{
			if (!$rw["topmenu_position"]) continue;
			if ($rw["auth_required"] && !$this->app->getUser()) continue;
			if ($rw["auth_denied"] && $this->app->getUser()) continue;
			$out .= ($out?"&nbsp;|&nbsp;":"") . "<a href='" . $this->app->makeLink("CID",$rw[id],1) . "'>" . $rw["topmenu"] . "</a>";
		}
		return $out;
	}

	protected function displayProfileLinks()
	{
		$out = "";
		$ar = order_by($this->app->getConfig("channels","profile","pages"),"topmenu_position");
		foreach($ar as $rw)
		{
			if (!$rw["topmenu_position"]) continue;
			if ($rw["auth_required"] && !$this->app->getUser()) continue;
			if ($rw["auth_denied"] && $this->app->getUser()) continue;
			$out .= ($out?"&nbsp;|&nbsp;":"") . "<a href='" . $this->app->makeLink(array("CID"=>"profile","page"=>$rw[id]),null,1) . "'>" . $rw["topmenu"] . "</a>";
		}
		if ($this->app->getUser())
			$out = "<!--[Short_greeting]-->, " . $this->app->getUser("displayName") . "&nbsp;|&nbsp;" . $out;
		return $out;	
	}

	protected function displayBottom()
	{
		$out = "&copy; " . date("Y") . " <a href='http://www.ragneta.com/'>Raganeta</a>";
		return $out;
	}

	protected function displaySelectGeo()
	{
		GLOBAL $geo_countries,$geo_cities;

		if (!$this->app->getUser()) return "";
		
		$out = "<!--[Your_city]-->: <a id='geoDivCityName' href='javascript:void(0);' onclick='switchDiv(\"selectGeoDiv\");return false;'>";
		if ($this->app->getUser("city_id")) $out .= $geo_cities[$this->app->getUser("city_id")]["name"];
		else $out .= "<!--[Not_choosen]-->";
		$out .= "</a>";

		$out .= "
			<div id='selectGeoDiv'>
				<div id='selectGeoDiv_close'><a href='javascript:void(0);' onclick='switchDiv(\"selectGeoDiv\");return false;'><img src='/im/w_close.gif' alt='Close' /></a></div>
				<table>
					<tr><td><!--[Country]--></td><td><select id='selectCountry' class='selectCountry' default='" . $this->app->getUser("country_id") . "' onchangetarget='selectCity'><option value='undefined'><!--[Loading]-->...</option></select></td></tr>
					<tr><td><!--[City]--></td><td><select id='selectCity' class='selectCity' default='" . $this->app->getUser("city_id") . "'><option value='undefined'><!--[Loading]-->...</option></select></td></tr>
					<tr><td></td><td><button onclick='setUserGeo();'><!--[Select]--></button></td></tr>
				</table>
			</div>
		";

		return $out;
}


}
