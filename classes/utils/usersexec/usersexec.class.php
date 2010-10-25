<?php

class UsersExec
{
	protected $where = array();
	protected $app = null;

	public $nolimit = null;
	public $nopages = null;
	public $start = 0;
	public $limit = null;

	public function where($type,$value)
	{
		$this->where[] = array("type"=>$type,"value"=>$value);
	}

	public function f1()
	{
		$this->limit = 1;
		$this->nopages = 1;
		$rws = $this->run();
		if ($rws && is_array($rws))
			return reset($rws);
		return null;
	}

	public function f()
	{
		return $this->run();
	}

	public function run()
	{
		$ar = array();
		$ar["where"][] = 1;

		if ($this->where)
		{
		foreach($this->where as $i => $rw)
		{
			if (!$rw["value"]) continue;

			if ($rw["type"] == "id") { $ar["where"][] = "id=:id" . $i; $ar["data"]["id" . $i] = $rw["value"]; }
			elseif ($rw["type"] == "ids") { $ar["where"][] = "id in (" . $rw["value"] . ")"; }
			elseif ($rw["type"] == "country_id") { $ar["where"][] = "country_id=:country_id" . $i; $ar["data"]["country_id" . $i] = $rw["value"]; }
			elseif ($rw["type"] == "city_id") { $ar["where"][] = "city_id=:city_id" . $i; $ar["data"]["city_id" . $i] = $rw["value"]; }
			elseif ($rw["type"] == "tag_id")
			{
				$rws = DB::f("select :wq from users_u2t where tag_id in (:tags)",array("tags"=>$rw["value"]));
				$ids = "";
				foreach($rws as $rw2)
					$ids .= ($ids?",":"") . $rw2[":wq"];
				if ($ids) { $ar["where"][] = "id in (:ids" . $i . ")"; $ar["data"]["ids" . $i] = $ids; }
				else return null;
			}
			elseif ($rw["type"] == "sex") { $ar["where"][] = "sex=:sex" . $i; $ar["data"]["sex" . $i] = $rw["value"]; }
			elseif ($rw["type"] == "age_from") { $ar["where"][] = "birth<=:birth_from" . $i; $ar["data"]["birth_from" . $i] = ((int)(date("Y")-$rw["value"])) . date("-m-d"); }
			elseif ($rw["type"] == "age_to") { $ar["where"][] = "birth>=:birth_to" . $i; $ar["data"]["birth_to" . $i] = ((int)(date("Y")-$rw["value"])) . date("-m-d"); }
		}
		}

		if (!$this->nopages)
		{
			if ($rw = DB::f1("select count(*) as cnt from users where " . join(" and ",$ar["where"]),$ar["data"]))
				$data_cnt = $rw["cnt"];
		}

		$rws = array();

		if ($this->nopages || $data_cnt)
			$rws = set_by_id(DB::f("select * from users where " . join(" and ",$ar["where"]) . (!$this->nolimit&&$this->limit?" limit " . $this->start . "," . $this->limit:""),$ar["data"]));

		if (!$rws) return null;

		GLOBAL $geo_cities,$geo_countries;

		foreach($rws as $rw)
		{
			$rw[displayName] = $rw[display_name] = $rw[show_name] = $rw[displayname] = ($rw[nick]?$rw[nick]:$rw[fname] . " " . $rw[lname]);
			$rw[fullName] = $rw[full_name] = $rw[fullname] = $rw[fname] . ($rw[fname]&&$rw[lname]?" ":"") . $rw[lname];
			$rw[userUrl] = "/people/u$rw[id].html";
			$rw[userLink] = "<a href='/people/u$rw[id].html'>$rw[displayName]</a>";
			$rw[settings_str] = $rw[settings];
			$rw[settings] = unserialize($rw[settings]);

			if ($rw[city_id])
			{
				$rw[userGeo] = $geo_cities[$rw[city_id]][name];
				$rw[userGeoLinks] = "<a href='/?formData[city_id]=$rw[city_id]&formData[country_id]=$rw[country_id]'>" . $geo_cities[$rw[city_id]][name] . "</a>";
				$rw[country_id] = $geo_cities[$rw[city_id]][country_id];
			}
			if ($rw[country_id])
			{
				$rw[userGeo] .= ($rw[userGeo]?", ":"") . $geo_countries[$rw[country_id]][name];
				$rw[userGeoLinks] .= ($rw[userGeoLinks]?", ":"") . "<a href='/?formData[country_id]=$rw[country_id]'>" . $geo_countries[$rw[country_id]][name] . "</a>";
			}

			$ar = explode("-",$rw[birth]);
			$rw[birth_dt] = mktime(0,0,0,$ar[1],$ar[2],$ar[0]);

			if ($rw[photo])
			{
				$rw[avatar] = preg_replace("/\.([^\.]+)$/","_sq.\\1",$rw[photo]);
			}

			$dt = (time() - $rw[dt_added])/86400;
			if ($dt < 7)
				$tmp = "<!--[less_than_a_week]-->";
			elseif ($dt < 14)
				$tmp = "<!--[week]-->";
			elseif ($dt < 30)
				$tmp = ((int)($dt/7)) . " <!--[weeks]-->";
			elseif ($dt < 60)
				$tmp = "<!--[month]-->";
				elseif (((int)($dt/30)) < 5)
				$tmp = ((int)($dt/30)) . " <!--[months1]-->";
			elseif ($dt < 365)
				$tmp = ((int)($dt/30)) . " <!--[months2]-->";	
			elseif ($dt < 365*2)
				$tmp = "<!--[year]-->";
			else
				$tmp = ((int)($dt/365)) . " <!--[years]-->";
	
			$tmp .= " <!--[on_the_site]-->";
			$rw[on_site] = $tmp;

			$data[$rw[id]] = $rw;
		}

		if ($this->nopages) return $data;
		return array("data"=>$data,"data_cnt"=>$data_cnt);
	}
}

