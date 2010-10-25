<?php

class Main extends Channel
{
	public function run_default()
	{
		GLOBAL $geo_cities,$geo_countries;


		$formData = $_REQUEST["formData"];
		$formData["country_id"] = (int)$formData["country_id"];
		$formData["city_id"] = (int)$formData["city_id"];

		$Q = new UsersExec();
		$Q->limit = $this->app->getCONFIG("users_ipp");
		$Q->start = (int)$_REQUEST["start"];

		if ($formData["sex"])
			$Q->where("sex",$formData["sex"]);
		if ($formData["country_id"])
			$Q->where("country_id",$formData["country_id"]);
		if ($formData["city_id"])
			$Q->where("city_id",$formData["city_id"]);
		if ($formData["age_from"])
			$Q->where("age_from",$formData["age_from"]);
		if ($formData["age_to"])
			$Q->where("age_to",$formData["age_to"]);

		if ($formData["sex"] || $formData["country_id"] || $formData["city_id"] || $formData["age_from"] || $formData["age_to"])
		{	
			$title .= "<!--[Sex_familiar_" . ((int)$formData["sex"]) . "]--> ";
			if ($formData["city_id"])
				$title .= "<!--[in]--> " . ($geo_cities[$formData["city_id"]]["name2"]?$geo_cities[$formData["city_id"]]["name2"]:$geo_cities[$formData["city_id"]]["name"]) . " ";
			elseif ($formData["country_id"])
				$title .= "<!--[in]--> " . $geo_countries[$formData["country_id"]]["name2"] . " ";
			if ($formData["age_from"])
				$title .= "<!--[s]--> " . $formData["age_from"];
			if ($formData["age_to"])
				$title .= "<!--[do]--> " . $formData["age_to"];
			if ($formData["age_from"] || $formData["age_to"])
				$title .= " <!--[let]--> ";
		}

		$link_ar = array();
		$link_ar["CID"] = "main";
		$link_ar["formData[sex]"] = $formData["sex"];
		$link_ar["formData[country_id]"] = $formData["country_id"];
		$link_ar["formData[city_id]"] = $formDat["city_id"];
		$link_ar["formData[age_from]"] = $formData["age_from"];
		$link_ar["formData[age_to]"] = $formData["age_to"];
		


		$data = $Q->f();
		if ($rws = $data["data"])
		{
			foreach($rws as $rw)
				$tmp .= $this->displayUser($rw);
		}
		else
			$tmp .= displayError("<!--[People_not_found]-->",0,1);

		$out = "
			<table class='wide'><tr>
				<td class='top'>
					" . ($title?"<h1>" . $title . "</h1>":"") . "
					<div class='mainpage_photos'>" . $tmp . "</div>
					" . makePages($this->app->makeLink($link_ar),$Q->start,$Q->limit,$data["data_cnt"]) . "
				</td>
				<td class='top' style='width:230px;'>" . $this->displayFilter() . "</td>
			</tr></table>
		";

		$this->data["title"] = $title;
		$this->data["content"] = $out;
	}

	protected function displayUser($rw)
	{
		$out = "<div class='uid'><a href='" . $this->app->makeLink(array("CID"=>"people","user_id"=>$rw["id"])) . "'><img src='" . $rw["avatar"] . "'></a></div>";
		return $out;
	}

	protected function displayFilter()
	{
		$formData = my_form_escape($_REQUEST["formData"]);

		$formData["sex"] = (int)$formData["sex"];
		for ($i = 0; $i <= 2; $i++)
			$tmp_sex .= ($tmp_sex?"<br />":"") . "<input type='radio' value='$i' id='formData_sex_$i' name='formData[sex]'" . ($formData["sex"]==$i?" checked selected checked='checked' selected='selected'":"") . "><label for='formData_sex_$i'><!--[Sex$i]--></label>";

		for ($i = 16; $i <= 60; $i++)
		{
			$tmp_age1 .= "<option value='" . $i . "'" . ($formData["age_from"]==$i?" selected":"") . ">" . $i . "</option>";
			$tmp_age2 .= "<option value='" . $i . "'" . ($formData["age_to"]==$i?" selected":"") . ">" . $i . "</option>";
		}

		$tmp_age .= "<select name='formData[age_from]'><option value='0'" . ($formData["age_from"]?"":" selected") . "><!--[Not_important]--></option>" . $tmp_age1 . "</select>";
		$tmp_age .= "&nbsp;-&nbsp;";
		$tmp_age .= "<select name='formData[age_to]'><option value='0'" . ($formData["age_to"]?"":" selected") . "><!--[Not_important]--></option>" . $tmp_age2 . "</select>";

		$out = "
			<form id='filterform' action='" . $this->app->makeLink("CID","main",1) . "'>
			<div class='h'><!--[Sex]-->:</div>
			<div class='p'>" . $tmp_sex . "</div>
			<div class='h'><!--[Country]-->:</div>
			<div class='p'>
				<select style='width:170px;' name='formData[country_id]' class='selectCountry' default='" . $formData["country_id"] . "' onchangetarget='filter_selectCity'><option value='undefined'><!--[Loading]-->...</option></select>
			</div>
			<div class='h'><!--[City]--></div>
			<div class='p'>
				<select style='width:170px;' id='filter_selectCity' name='formData[city_id]' class='selectCity' default='" . $formData["city_id"] . "'><option value='undefined'><!--[Loading]-->...</option></select>
			</div>
			<div class='h'><!--[Age]--></div>
			<div class='p'>" . $tmp_age . "</div>
			<div class='centerButton'><a href='javascript:void(0);' onclick='javascript:$(\"#filterform\").submit();'><!--[Search]--></a></div>
			</form>
		";

		$out = "
			<div class='sideBlock'>
				<h2><!--[Set_filter]--></h2>
				<div class='inner filter'>" . $out . "</div>
			</div>
		";

		return $out;
	}
}
