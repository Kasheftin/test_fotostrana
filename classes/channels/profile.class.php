<?php

class Profile extends Channel
{
	protected $error = null;
	protected $success = null;

	public function run_default()
	{
		$this->app->CID = "people";
		$this->app->CHDATA[$this->app->CID]["user_id"] = $this->app->getUser("id");
	}

	public function run_signin()
	{
		return $this->display_signin_form();
	}

	public function run_signup()
	{
		return $this->display_signup_form();
	}

	public function run_change_profile()
	{
		return $this->display_change_profile_form();
	}

	public function run_write_message()
	{
		$to_user_id = (int)$_REQUEST["to"];
		if (!$to_user_id) throw new Exception("<!--[No_user]-->");

		$Q = new UsersExec();
		$Q->where("id",$to_user_id);
		$rw = $Q->f1();

		if (!$rw) throw new Exception("<!--[User]--> " . $to_user_id . " doesn't exist");

		return $this->display_write_message_form($rw);
	}

	public function run_my_messages()
	{
		if ($rws = DB::f("select * from messages where `to`=:id or `from`=:id order by dt desc",array("id"=>$this->app->getUser("id"))))
		{
			$rws = $this->load_messages_users($rws);
			return $this->display_my_messages($rws);
		}
		$this->app->setSuccess("<!--[You_have_not_any_messages_yet]-->",5);
	}

	public function run_show_message()
	{
		$message_id = (int)$_REQUEST["message_id"];
		if (!$message_id) throw new Exception("<!--[No_message]-->");
		if ($rw = DB::f1("select * from messages where id=:id",array("id"=>$message_id)))
		{
			if ($rw["to"] == $this->app->getUser("id") || $rw["from"] == $this->app->getUser("id"))
			{
				$rw = $this->load_messages_users(array(0=>$rw));
				return $this->display_message($rw[0]);
			}
			else throw new Exception("<!--[Access_denied]-->");
		}
		else throw new Exception("<!--[Message_does_not_exist]-->");
	}

	protected function load_messages_users($rws)
	{
		$users_ar = array();
		foreach($rws as $rw)
			$users_ar[$rw["from"]] = $users_ar[$rw["to"]] = 1;

		$Q = new UsersExec();
		$Q->where("ids",join(",",array_keys($users_ar)));
		$Q->nolimit = 1;

		if ($rws_users = $Q->f())
		{
			foreach($rws as $i => $rw)
			{
				$rw["rw_from"] = $rws_users["data"][$rw["from"]];
				$rw["rw_to"] = $rws_users["data"][$rw["to"]];
				$rws[$i] = $rw;
			}
		}

		return $rws;
	}

	public function run_exit()
	{
		DB::q("delete from users_sessions where sid=:sid",array("sid"=>$this->app->getUser("rw_session","sid")));
		setcookie("SID","",time()-86400,"/",$this->app->getConfig("auth","cookie_path"));
		$this->app->setSuccess("<!--[Exit_message]-->",0);
		return array("return"=>true);
	}

	protected function action_ajax_change_profile()
	{
		$formData = my_strip_tags(my_unescape($_REQUEST["formData"]));
		if (!$formData || !is_array($formData)) throw new Exception("<!--[No_formdata]-->");
		if (!$formData["birth_day"] || !$formData["birth_month"] || !$formData["birth_year"]) throw new Exception("<!--[Enter_birth_date]-->");
		if ((!$formData["fname"] || !$formData["lname"]) && !$formData["nick"]) throw new Exception("<!--[Enter_name_or_nick]-->");

		if ($formData["nick"] && DB::f1("select * from users where nick=:nick and id!=:id",array("nick"=>$formData["nick"],"id"=>$this->app->getUser("id"))))
			throw new Exception("<!--[This_nick_is_already_taken]-->");

		$formData["sex"] = (int)$formData["sex"];

		$query = "update users set fname=:fname,lname=:lname,about=:about,contact_email=:contact_email,nick=:nick,birth=:birth,sex=:sex where id=:id";

		DB::q($query,array(
			"fname"=>$formData["fname"],
			"lname"=>$formData["lname"],
			"about"=>$formData["about"],
			"contact_email"=>$formData["contact_email"],
			"nick"=>$formData["nick"],
			"birth"=>$formData["birth_year"] . "-" . $formData["birth_month"] . "-" . $formData["birth_day"],
			"sex"=>$formData["sex"],
			"id"=>$this->app->getUser("id"),
		));

		$success = "<!--[Changes_saved]-->";

		if ($formData["sex"] == 1 && $this->app->getUser("sex") == 2)
			$success = "<!--[Oh_man_howre_you_feel]-->";
		if ($formData["sex"] == 2 && $this->app->getUser("sex") == 1)
			$success = "<!--[Oh_woman_howre_you_feel]-->";

		return array("success"=>$success);
	}

	protected function action_ajax_change_password()
	{
		$formData = $_REQUEST["formData"];
		if (!$formData["new_password"] || !$formData["new_password2"]) throw new Exception("<!--[No_new_password]-->");
		if (!$formData["old_password"]) throw new Exception("<!--[Enter_current_password]-->");
		if (md5($formData["old_password"]) != $this->app->getUser("password")) throw new Exception("<!--[Incorrect_current_password]-->");
		if ($formData["new_password"] != $formData["new_password2"]) throw new Exception("<!--[New_passwords_mismatch]-->");
		if (strlen($formData["new_password"]) < 5) throw new Exception("<!--[Too_short_new_password]-->");

		DB::q("update users set password=:password where id=:id",array("password"=>md5($formData["new_password"]),"id"=>$this->app->getUser("id")));
		$success = "<!--[Saves_changed]-->";

		return array("success"=>$success);
	}

	protected function action_ajax_upload_photo()
	{
		$file = $_FILES["file"];
		if (!$file) throw new Exception("<!--[No_file]-->");
		if (!is_uploaded_file($file["tmp_name"])) throw new Exception("<!--[File_is_not_uploaded]-->");

		$thumbs = array();
		$thumbs["orig"]	= array("w"=>192,"h"=>192,"type"=>"inbox");	//оригинал любого аватара уменьшается до 300x500 px
		$thumbs["sq"]	= array("w"=>97,"h"=>97,"type"=>"sq");		//мелкий аватар в строчке с ником юзера в комментах и везде вообще

		list($src_img,$src_type,$full_w,$full_h) = my_imagecreate($file);

		$global_dir = $this->app->getCONFIG("users_avatars_global_dir") . "/" . $this->app->getUser("id");
		if (!is_dir($global_dir)) mkdir($global_dir,0777,1);

		$filename = my_getnewfilename($global_dir,$file["name"],$src_type);

		foreach($thumbs as $ii => $thumbset)
		{
			list($new_w,$new_h,$cut_x,$cut_y,$cut_w,$cut_h) = getThumbnailParams($thumbset,$full_w,$full_h);
			if (!($dst_img = ImageCreateTrueColor($new_w,$new_h))) throw new Exception("<!--[Failed_creating_thumbnail_image]-->");
			if (!imagecopyresampled($dst_img,$src_img,0,0,$cut_x,$cut_y,$new_w,$new_h,$cut_w,$cut_h)) throw new Exception("<!--[Failed_imagecopyresambled]-->");
			my_imagesave($dst_img,$src_type,$global_dir . "/" . $filename . ($ii=="orig"?"":"_" . $ii) . "." . $src_type);
			@imagedestroy($dst_img);
		}
		
		imageDestroy($src_img);

		$tmp = $this->app->getCONFIG("users_avatars_local_dir") . "/" . $this->app->getUser("id") . "/" . $filename . "." . $src_type;

		DB::q("update users set photo=:photo where id=:id",array("photo"=>$tmp,"id"=>$this->app->getUser("id")));
		
		return array("file_url"=>$tmp,"success"=>"<!--[New_user_avatar_uploaded]-->");
	}

	protected function action_ajax_set_geo()
	{
		GLOBAL $geo_cities,$geo_countries; 
		
		$country_id = (int)$_REQUEST["country_id"];
		$city_id = (int)$_REQUEST["city_id"];
		if ($city_id) $country_id = $geo_cities[$city_id]["country_id"];

		DB::q("update users set country_id=:country_id,city_id=:city_id where id=:id",array("country_id"=>$country_id,"city_id"=>$city_id,"id"=>$this->app->getUser("id")));

		return array("success"=>"<!--[City_changed]-->","city_name"=>($city_id?$geo_cities[$city_id]["name"]:"<!--[Not_choosen]-->"));
	}

	protected function action_ajax_delete_user_photo()
	{
		DB::q("update users set photo='' where id=:id",array("id"=>$this->app->getUser("id")));
		return array("success"=>"<!--[Photo_deleted]-->");
	}

	protected function action_my_messages_delete_messages()
	{
		$formData = $_REQUEST["formData"];
		if ($formData["sel"] && is_array($formData["sel"]))
		{
			foreach($formData["sel"] as $message_id => $ii)
			{
				DB::q("delete from messages where id=:id and (`to`=:user_id or `from`=:user_id)",array("id"=>$message_id,"user_id"=>$this->app->getUser("id")));
			}
		}
		return array("success"=>"<!--[Messages_deleted]-->");
	}

	protected function action_signin_do()
	{
		$formData = $_REQUEST["formData"];
		if (!$formData || !is_array($formData)) throw new Exception("<!--[No_formdata]-->");
		if (!$formData["auth_email"]) throw new Exception("<!--[Empty_email]-->");
		if (!preg_match("/^[a-zA-Z0-9\-\_\.@]+$/",$formData[auth_email])) throw new Exception("<!--[Email_contains_error]-->");
		if (!$formData["auth_password"]) throw new Exception("<!--[Empty_password]-->");
		if (!preg_match("/^[a-zA-Z0-9]+$/",$formData["auth_password"])) throw new Exception("<!--[Password_contains_error]-->");

		if ($rw = DB::f1("select * from users where email=:email",array("email"=>$formData["auth_email"])))
		{
			if ($rw[password] == md5($formData["auth_password"]))
			{
				$SID = $rw["id"] . $rw["email"] . $rw["password"] . rand(1,9999);
				$SID = md5($SID);
				DB::q("insert into users_sessions(`sid`,`user_id`,`dt`) values(:sid,:id,:dt)",array("sid"=>$SID,"id"=>$rw["id"],"dt"=>time()));
				setcookie("SID",$SID,0,"/",$this->app->getConfig("auth","cookie_domain"));

				$rw_settings = unserialize($rw["settings"]);
				if ($formData["rememberme"])
					$rw_settings["rememberme"] = 1;
				else
					unset($rw_settings["rememberme"]);
				DB::q("update users set settings=:settings,dt_last_activity=:time where id=:id",array("id"=>$rw["id"],"settings"=>serialize($rw_settings),"time"=>time()));

				$this->app->setSuccess("<!--[Signin_success]-->",0);
				return array("return"=>true);
			}
			else throw new Exception("<!--[Incorrect_password]-->");
		}
		else throw new Exception("<!--[No_user]-->");
	}

	protected function display_signin_form()
	{
		$formData = $_REQUEST["formData"];
		$formData = my_form_escape($formData);

		$out = "
			<center>
			<div class='centerForm'>
			<h1>" . $this->CONFIG["pages"][$this->app->page]["title"] . "</h1>
			<div class='sep'></div>
			" . ($this->adata["error"]?displayError($this->adata["error"]) . "<div class='sep'></div>":"") . "
			<form method='post' action='" . $this->app->makeLink() . "'>
			<input type='hidden' name='action' value='do'>
				<div class='fname'><!--[Email]--></div>
				<div><input class='big' name='formData[auth_email]' value='" . $formData["auth_email"] . "'></div>
				<div class='fname'><!--[Password]--></div>
				<div><input class='big' type='password' name='formData[auth_password]' value=''></div>
				<div><input type='checkbox' name='formData[rememberme]' value='1' id='formData_rememberme'" . ($formData["rememberme"]?" checked":"") . "><label for='formData_rememberme'><!--[Remember_me_for_two_weeks_and_autologin]--></label></div>
				<div class='sep'></div>
				<div class='center'><input class='button' type='submit' value='<!--[Signin]-->'>&nbsp;&nbsp;<span class='small'><a href='" . $this->app->makeLink("page","restore_password") . "'><!--[forgotten_password]--></a></span></div>
			</form>
			</div>
			</center>
		";

		$this->data["content"] = $out;
	}

	protected function action_signup_do()
	{
		$formData = $_REQUEST["formData"];

		if (!$formData || !is_array($formData)) throw new Exception("<!--[No_formdata]-->");
		if (!$formData["email"]) throw new Exception("<!--[Empty_email]-->");
		if (!preg_match("/^[a-zA-Z0-9\-\_\.@]+$/",$formData["email"])) throw new Exception("<!--[Email_contains_error]-->");
		if (!preg_match("/^\w[\w\.\-]*@\w[\w\.\-]*\.\w+$/i",$formData["email"])) throw new Exception("<!--[Email_contains_error]-->");

		if (!$formData["birth_day"] || !$formData["birth_month"] || !$formData["birth_year"]) throw new Exception("<!--[Enter_birth_date]-->");
		if (!$formData["fname"]) throw new Exception("<!--[Enter_name]-->");
		if (!$formData["nick"]) throw new Exception("<!--[Enter_nick]-->");

		if (!$formData["password"]) throw new Exception("<!--[Empty_password]-->");
		if (strlen($formData["password"])<5) throw new Exception("<!--[Short_password]-->");
		if (!preg_match("/^[a-zA-Z0-9]+$/",$formData["password"])) throw new Exception("<!--[Password_contains_error]-->");
		if (!$formData["password_repeat"]) throw new Exception("<!--[Empty_password_repeat]-->");
		if ($formData["password_repeat"] != $formData["password"]) throw new Exception("<!--[Passwords_mismatch]-->");

		$formData["nick"] = trim($formData["nick"]);
		if (!preg_match("/^[\w\_]+$/i",$formData["nick"])) throw new Exception("<!--[Nick_contains_incorect_characters]-->");

		$formData["sex"] = (int)$formData["sex"];

		if (DB::f1("select * from users where email=:email",array("email"=>$formData["email"])))
			throw new Exception("<!--[This_email_is_already_taken]-->");

		if ($formData[nick] && DB::f1("select * from users where nick=:nick",array("nick"=>$formData["nick"])))
			throw new Exception("<!--[This_nick_is_already_taken]-->");

		$query = "
			insert into users(`id`,`email`,`password`,`sex`,`birth`,`fname`,`lname`,`nick`,`dt_added`) 
			values('',:email,:password,:sex,:birth,:fname,:lname,:nick,:time)
		";

		$ar = array(
			"email" => $formData["email"],
			"password" => md5($formData["password"]),
			"sex" => $formData["sex"],
			"birth" =>  $formData["birth_year"] . "-" . $formData["birth_month"] . "-" . $formData["birth_day"],
			"fname" => $formData["fname"],
			"lname" => $formData["lname"],
			"nick" => $formData["nick"],
			"time" => time(),
		);

		if ($new_user_id = DB::q($query,$ar))
		{
			$this->app->setSuccess("<!--[Signup_success]-->",5);
			return array("new_user_id"=>$new_user_id,"return"=>true);
		}

		throw new Exception("<!--[user_not_added]-->");
	}

	protected function display_signup_form()
	{
		$formData = $_REQUEST["formData"];
		$formData = my_form_escape($formData);
		
		$tmp_birth = "<select name='formData[birth_day]'><option value='0'" . ($formData["birth_day"]?"":" selected") . ">--</option>";
		for ($i = 1; $i <= 31; $i++)
			$tmp_birth .= "<option value='$i'" . ($i==$formData["birth_day"]?" selected":"") . ">$i</option>";
		$tmp_birth .= "</select><select name='formData[birth_month]'><option value='0'" . ($formData["birth_month"]?"":" selected") . ">----</option>";
		for ($i = 1; $i <= 12; $i++)
			$tmp_birth .= "<option value='$i'" . ($i==$formData["birth_month"]?" selected":"") . "><!--[Month_" . $i . "2]--></option>";
		$tmp_birth .= "</select><select name='formData[birth_year]'><option value='0'" . ($formData["birth_year"]?"":" selected") . ">----</option>";
		for ($i = 1930; $i <= date("Y")-10; $i++)
			$tmp_birth .= "<option value='$i'" . ($i==$formData["birth_year"]?" selected":"") . ">$i</option>";
		$tmp_birth .= "</select>";

		$formData["sex"] = (int)$formData["sex"];
		for ($i = 0; $i <= 2; $i++)
			$tmp_sex .= ($tmp_sex?" &nbsp; ":"") . "<input type='radio' value='$i' id='signup_form_sex_$i' name='formData[sex]'" . ($formData["sex"]==$i?" checked selected checked='checked' selected='selected'":"") . "><label for='signup_form_sex_$i'><!--[Sex$i]--></label>";

		$out = "
			<center>
			<div class='centerForm'>
			<h1>" . $this->CONFIG["pages"][$this->app->page]["title"] . "</h1>
			<div class='sep'></div>
			" . ($this->adata["error"]?displayError($this->adata["error"]) . "<div class='sep'></div>":"") . "
			<form method='post' action='" . $this->app->makeLink() . "'>
			<input type='hidden' name='action' value='do'>
				<div class='fname'><!--[Email]--></div>
				<div><input class='big' name='formData[email]' value='" . $formData["email"] . "'></div>
				<div class='s'><!--[Signup_email_help]--></div>
				<div class='fname'><!--[Password]--></div>
				<div><input class='big' type='password' name='formData[password]' value=''></div>
				<div class='s'><!--[Signup_password_help]--></div>
				<div class='fname'><!--[Password_repeat]--></div>
				<div><input class='big' type='password' name='formData[password_repeat]' value=''></div>
				<div class='sep'></div>
				<table class='w'>           
					<tr><td><div class='fname'><!--[Sex]--></div></td><td><div>" . $tmp_sex . "</div></td></tr>
					<tr><td><div class='fname'><!--[Birthdate]--></div></td><td><div>" . $tmp_birth . "</div></td></tr>
					<tr><td><div class='fname'><!--[Fname]--></div></td><td><div><input class='sbig' name='formData[fname]' value='" . $formData["fname"] . "'></div></td></tr>
					<tr><td><div class='fname'><!--[Lname]--></div></td><td><div><input class='sbig' name='formData[lname]' value='" . $formData["lname"] . "'></div></td></tr>
					<tr><td><div class='fname'><!--[Nick]--></div></td><td><div><input class='sbig' name='formData[nick]' value='" . $formData["nick"] . "'></div></td></tr>
				</table>
				<div class='sep'></div>
				<div class='center'><input class='button' type='submit' value='<!--[Signup]-->'></div>
			</form>
			</div>
			</center>
		";

		$this->data["content"] = $out;
	}

	protected function action_write_message_do()
	{
		$formData = $_REQUEST["formData"];
		$to_user_id = (int)$_REQUEST["to"];
		if (!$to_user_id) throw new Exception("<!--[No_user]-->");

		$Q = new UsersExec();
		$Q->where("id",$to_user_id);
		$rw = $Q->f1();

		if (!$rw) throw new Exception("<!--[User]--> " . $to_user_id . " doesn't exist");

		if (!$formData["title"]) throw new Exception("<!--[Enter_message_title]-->");
		if (!$formData["text"]) throw new Exception("<!--[Enter_message_text]-->");

		if (DB::q("insert into messages(`id`,`from`,`to`,`title`,`text`,`dt`) values('',:from,:to,:title,:text,:dt)",array("from"=>$this->app->getUser("id"),"to"=>$to_user_id,"title"=>$formData["title"],"text"=>$formData["text"],"dt"=>time())))
		{
			$this->app->setSuccess("<!--[Message_sent]-->",5,$this->app->makeLink(array("CID"=>"people","user_id"=>$to_user_id)));
			return array("success"=>"<!--[Message_sent]-->","return"=>true);
		}
		else throw new Exception("<!--[Failed_sending_message]-->");
	}

	protected function display_write_message_form($rw)
	{
		$formData = my_form_escape($_REQUEST["formData"]);

		$out = "
			<center>
			<div class='centerForm'>
			<h1>" . $this->CONFIG["pages"][$this->app->page]["title"] . "</h1>
			<div class='sep'></div>
			" . ($this->adata["error"]?displayError($this->adata["error"]) . "<div class='sep'></div>":"") . "
			<form method='post' action='" . $this->app->makeLink() . "'>
			<input type='hidden' name='action' value='do'>
			<input type='hidden' name='to' value='" . $rw["id"] . "'>
			<table class='wide'>
				<tr><td class'fname'><!--[To_who]--></div></td><td style='width:300px;'>" . $rw["displayName"] . "</td></tr>
				<tr><td class='fname'><!--[Title]--></td><td><input tabindex='2' class='w' name='formData[title]' value='" . $formData["title"] . "'></td></tr>
				<tr><td class='fname'><!--[Message_text]--></td><td><textarea class='w' tabindex='3' class='maintext' id='maintext' name='formData[text]'>" . $formData["text"] . "</textarea></td></tr>
			</table>
			<div class='sep'></div>
			<div class='center'><input tabindex='4' class='submit' type='submit' value='<!--[Send_message]-->'></div>
			</form>
			</div>
			</center>
		";

		$this->data["content"] = $out;
	}

	protected function display_my_messages($rws)
	{
		$out .= "
			<form method='post' action='" . $this->app->makeLink() . "' id='messages_form'>
			<input type='hidden' name='action' value='delete_messages'>
			<table class='tbl_short'>
				<tr>
					<th class='checkbox'></th>
					<th><!--[Title]--></th>
					<th><!--[To_who]--></th>
					<th class='from'><!--[From]--></th>
					<th class='date'><!--[Date]--></th>
				</tr>
		";

		foreach($rws as $rw)
			$out .= "
				<tr>
					<td><input type='checkbox' name='formData[sel][" . $rw["id"] . "]' value='1'></td>
					<td class='m'><a href='" . $this->app->makeLink(array("CID"=>"profile","page"=>"show_message","message_id"=>$rw["id"])) . "'>" . $rw["title"] . "</a></td>
					<td class='v'><a href='" . $this->app->makeLink(array("CID"=>"people","user_id"=>$rw["to"])) . "'>" . $rw["rw_to"]["displayName"] . "</a></td>
					<td class='v'><a href='" . $this->app->makeLink(array("CID"=>"people","user_id"=>$rw["from"])) . "'>" . $rw["rw_from"]["displayName"] . "</a></td>
					<td class='v'>" . printDate($rw["dt"]) . "</td>
				</tr>
			";

		$out .= "
			</table>
			<div class='leftButton'><a href='javascript:void(0);' onclick='javascript:$(\"#messages_form\").submit();'><!--[Delete_messages]--></a></div>
		";

		$this->data["content"] = $out;
	}
	
	protected function display_message($rw)
	{
		$out = "
		<div class='message'>
			<div class='h'>" . $rw["title"] . "</div>
			<div class='text'>" . preg_replace("/\n/","<br />",$rw["text"]) . "</div>
			<div class='sub'>
				<div class='date'><!--[added3]--> " . printDateTime($rw["dt"]) . "</div>
				<div class='to'><!--[to_who]-->: <a href='" . $this->app->makeLink(array("CID"=>"people","user_id"=>$rw["to"])) . "'>" . $rw["rw_to"]["displayName"] . "</a></div>
				<div class='from'><!--[from]-->: <a href='" . $this->app->makeLink(array("CID"=>"people","user_id"=>$rw["from"])) . "'>" . $rw["rw_from"]["displayName"] . "</a></div>
				<div class='delete'>
					<a href='" . $this->app->makeLink(array("CID"=>"profile","page"=>"my_messages","action"=>"delete_messages","formData[sel][" . $rw[id] . "]"=>1)) . "'><!--[Delete_message]--></a>
				</div>
			</div>
			<div class='clear'></div>
			<div class='centerButton'><a href='" . $this->app->makeLink(array("CID"=>"profile","page"=>"write_message","to"=>($rw["to"]==$this->app->getUser("id")?$rw["from"]:$rw["to"]))) . "'><!--[Reply]--></a></div>
		</div>
		";

		$this->data["content"] = $out;
	}

	protected function display_change_profile_form()
	{
		$this->data["js_scripts"]["/js/jquery.ocupload-1.1.2.js"] = 1;
		$this->data["js_scripts"]["/js/jquery.myload.js"] = 1;
		$this->data["js_scripts"]["/js/profile_fn.js"] = 1;

		$rw = my_form_escape($this->app->getUser());

		$ar = explode("-",$rw[birth]);
		$tmp_birth = "<select name='formData[birth_day]'><option value='0'" . ($ar[2]?"":" selected") . ">--</option>";
		for ($i = 1; $i <= 31; $i++)
			$tmp_birth .= "<option value='$i'" . ($i==$ar[2]?" selected":"") . ">$i</option>";
		$tmp_birth .= "</select><select name='formData[birth_month]'><option value='0'" . ($ar[1]?"":" selected") . ">----</option>";
		for ($i = 1; $i <= 12; $i++)
			$tmp_birth .= "<option value='$i'" . ($i==$ar[1]?" selected":"") . "><!--[Month_" . $i . "2]--></option>";
		$tmp_birth .= "</select><select name='formData[birth_year]'><option value='0'" . ($ar[0]?"":" selected") . ">----</option>";
		for ($i = 1930; $i <= date("Y")-10; $i++)
			$tmp_birth .= "<option value='$i'" . ($i==$ar[0]?" selected":"") . ">$i</option>";
		$tmp_birth .= "</select>";

		$tmp_photo = "
			<div class='profile_photo' id='profile_photo'" . ($rw["photo"]?"":" style='display:none;'") . ">
				<img src='" . $rw["photo"] . "' id='profile_photo_img' alt='" . $rw["displayName"] . "' />
				<span class='sub'><a href='javascript:void(0);' onclick='delete_user_photo();return false;'><!--[Delete_photo]--></a></span>
			</div>
			<div class='profile_photo' id='profile_no_photo'" . ($rw["photo"]?" style='display:none;'":"") . "><div><!--[Photo_not_loaded]--></div></div>
		";

		for ($i = 0; $i <= 2; $i++)
			$tmp_sex .= ($tmp_sex?" &nbsp; ":"") . "<input type='radio' value='$i' id='signup_form_sex_$i' name='formData[sex]'" . ($rw["sex"]==$i?" checked selected checked='checked' selected='selected'":"") . "><label for='signup_form_sex_$i'><!--[Sex$i]--></label>";

		$out = "
			<h1>" . $this->CONFIG["pages"][$this->app->page]["title"] . "</h1>
			<center>
			<div class='centerForm2'>
				<div class='hh'><!--[Main_profile_data]--></div>
				<table class='form' id='main_form'>
				<input type='hidden' name='action' value='change_profile'>
					<tr><td class='f'><!--[Email]-->:</td><td><input class='inp' name='formData[email]' disabled value='" . $rw["email"] . "'></td></tr>
					<tr><td class='f'><!--[Fname]-->:</td><td><input class='inp' name='formData[fname]' value='" . $rw["fname"] . "'></td></tr>
					<tr><td class='f'><!--[Lname]-->:</td><td><input class='inp' name='formData[lname]' value='" . $rw["lname"] . "'></td></tr>
					<tr><td class='f'><!--[Nick]-->:</td><td><input class='inp' name='formData[nick]' value='" . $rw["nick"] . "'></td></tr>
					<tr><td class='f'><!--[Sex]-->:</td><td>" . $tmp_sex . "</td></tr>
					<tr><td class='f'><!--[Birthdate]-->:</td><td>" . $tmp_birth . "</td></tr>
					<tr><td class='f'><!--[About_me]-->:</td><td><textarea name='formData[about]'>" . $rw["about"] . "</textarea></td></tr>
					<tr><td class='f'><!--[Contact_email]-->:<div class='small'><!--[Contact_email_help]--></div></td><td><input class='inp' name='formData[contact_email]' value='" . $rw["contact_email"] . "'></td></tr>
				</table>
				<div class='sep'></div>
				<table class='save_buttons'><tr>
					<td><div class='button'><a href='javascript:void(0);' onclick='send_form(\"#main_form\",\"#main_form_loading\");return false;'><!--[Save_changes]--></a></div></td>
					<td><div class='loading' id='main_form_loading'></div></td>
				</tr></table>

				<div class='hh'><!--[Photo]--></div>
				" . $tmp_photo . "
				<div class='sep'></div>
				<table class='save_buttons'><tr>
					<td><div class='button'><a href='javascript:void(0);' id='photo_form_button'><!--[Upload_new_photo]--></a></div></td>
					<td><div class='loading' id='photo_form_loading'></div></td>
				</tr></table>

				<div class='hh'><!--[Changing_password]--></div>
				<table class='form' id='password_form'>
				<input type='hidden' name='action' value='change_password'>
					<tr><td class='f'><!--[New_password]-->:</td><td><input class='inp' type='password' name='formData[new_password]' value=''></td></tr>
					<tr><td class='f'><!--[New_password2]-->:</td><td><input class='inp' type='password' name='formData[new_password2]' value=''></td></tr>
					<tr><td colspan='2'><div class='rsep'></div></td></tr>
					<tr><td class='f'><!--[Current_password]-->:</td><td><input class='inp' type='password' name='formData[old_password]' value=''></td></tr>
				</table>
				<div class='sep'></div>
				<table class='save_buttons'><tr>
					<td><div class='button'><a href='javascript:void(0);' onclick='send_form(\"#password_form\",\"#password_form_loading\");return false;'><!--[Save_changes]--></a></div></td>
					<td><div class='loading' id='password_form_loading'></div></td>
				</tr></table>
			</div>
			</center>
		";

		$this->data["content"] = $out;
	}
}

