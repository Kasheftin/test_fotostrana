<?php


/*
	2010.06.01 by Kasheftin
	PHPUnit test class for ArrayClass
*/


require_once("PHPUnit/Framework.php");
require_once("arrayclass.class.php");


class ArrayClassTest extends PHPUnit_Framework_TestCase
{

	public function testInit()
	{
		$ar = array("var1"=>rand(0,100),md5("var2")=>rand(-100,0),md5("var3")=>md5("test string"));

		$obj = new ArrayClass($ar);
		foreach($ar as $i => $v)
			$this->assertEquals($v,$obj[$i]);
		$this->assertEquals($ar[var1],$obj->var1);
		$obj = null;	
	}

	public function testUnlinkInForeach()
	{
		$obj = new ArrayClass();

		$obj->var1 = rand(-100,100);
		$obj->var2 = rand(-100,100);
		for ($i = 3; $i < 10; $i++)
			$obj["var".$i] = rand(-100,100);

		$compare = $str = "";

		for ($i = 1; $i < 10; $i++)
		{
			if ($i == 2 || $i == 3 || $i == 7) continue;
			$compare .= "var" . $i . "=" . $obj["var".$i] . ";";
		}

		foreach($obj as $i => $v)
		{
			unset($obj[var1]);
			unset($obj[var2]);
			unset($obj[var3]);
			unset($obj[var7]);
			$str .= "$i=$v;";
		}

		$this->assertEquals($compare,$str);		
	}

	public function testDoubleForeach()
	{
		$obj = new ArrayClass();

		$obj->var1 = rand(-100,100);
		$obj->var2 = rand(-100,100);
		for ($i = 3; $i < 10; $i++)
			$obj["var".$i] = rand(-100,100);

		$compare = $str = "";

		foreach($obj as $i => $v)
			$compare .= "i:var1=" . $obj[var1] . ";ii:$i=$v;";

		foreach($obj as $i => $v)
			foreach($obj as $ii => $vv)
				$str .= "i:$i=$v;ii:$ii=$vv;";
	
		$this->assertEquals($compare,$str);
	}

	public function testSubFunc()
	{
		$ar = new ArrayClass(array("var1"=>1,"var2"=>2,"var3"=>3));
		$ar[var4] = 4; $ar->var5 = 5;
		unset($ar[var3]);
		foreach($ar as $i => $v)
		{
			$this->tmp_subfunc($ar);
			$str .= $v;
		}
		$this->assertEquals("1467",$str);
	}

	private function tmp_subfunc($obj)
	{
		unset($obj[var1]); unset($obj[var2]); unset($obj[var5]); $obj->var6 = 6; $obj[var7] = 7; 
	}

}

