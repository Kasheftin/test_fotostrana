<?php

class People extends Channel
{
	public function run_overall()
	{
		$user_id = $this->app->CHDATA[$this->app->CID]["user_id"];
		if ($user_id)
		{
			if ($this->app->getUser("id") == $user_id)
				return $this->displayUser($this->app->getUser());
			else
			{
				$Q = new UsersExec();
				$Q->where("id",$user_id);

				if ($user = $Q->f1())
				{
					return $this->displayUser($user);
				}
				else
				{
					$this->app->setError("<!--[User_not_found]-->");
					return false;
				}
			}
		}
		else
		{
			$this->app->CID = "main";
			return true;
		}
	}

	protected function displayUser($rw)
	{
		if ($rw["photo"])
			$tmp_photo = "
				<div class='profile_photo'>
					<img src='" . $rw["photo"] . "' id='profile_photo_img' alt='" . $rw["displayName"] . "' />
				</div>
			";
		else
			$tmp_photo = "
				<div class='profile_photo' id='profile_no_photo'>
					<div><!--[Photo_not_loaded]--></div>
				</div>
			";

		$tmp_bottom = "";
		if ($this->app->getUser("id") == $rw[id])
			$tmp_bottom = "<tr><td></td><td><div class='centerButton'><a href='" . $this->app->makeLink(array("CID"=>"profile","page"=>"change_profile"),null,1) . "'><!--[Change_data_about_me]--></a></div></td></tr>";
		elseif ($this->app->getUser())
			$tmp_bottom = "<tr><td></td><td><div class='centerButton'><a href='" . $this->app->makeLink(array("CID"=>"profile","page"=>"write_message","to"=>$rw[id]),null,1) . "'><!--[Write_message]--></a></div></td></tr>";

		$out = "
			<h1>" . $rw["displayName"] . "</h1>
			<div class='user_profile'>
			<table class='info'>
				" . ($rw["fullName"]?"<tr><td class='f'><!--[Name]-->:</td><td class='v'>" . $rw["fullName"] . "</td></tr>":"") . "
				<tr><td class='f'><!--[Birth_date]-->:</td><td class='v'>" . printDate($rw["birth_dt"]) . "</td></tr>
				<tr><td class='f'><!--[Photo]-->:</td><td>" . $tmp_photo . "</td></tr>
				<tr><td class='f'><!--[Where_from]-->:</td><td class='v'>" . $rw["userGeoLinks"] . "</td></tr>
				" . ($rw["about"]?"<tr><td class='f'><!--[About_me]-->:</td><td class='v about'>" . $rw["about"] . "</td></tr>":"") . "
				" . ($rw["strench"]?"<tr><td class='f'><!--[Rating_strench]-->:</td><td class='v'><span class='rating'>$rw[rating]</span> / <span class='strench'>$rw[strench]</span></td></tr>":"") . "
				<tr><td class='f'><!--[Registered]-->:</td><td class='v'>" . printDate($rw["dt_added"]) . "</td></tr>
				<tr><td class='f'><!--[Activity]-->:</td><td class='v'><!--[Last_time_was_on_the_site_on]-->" . ($rw["dt_last_activity"]?" " . printDateTime($rw["dt_last_activity"],"time"):": <!--[never]-->") . "</td></tr>
				" . ($rw["contact_email"]?"<tr><td class='f'><!--[Contact_email]-->: </td><td>[email]" . $rw["contact_email"] . "[/email]</td></tr>":"") . "
				" . $tmp_bottom . "
			</table>
			</div>
		";

		$this->data["title"] = $rw["displayName"];
		$this->data["content"] = $out;
	}
}
