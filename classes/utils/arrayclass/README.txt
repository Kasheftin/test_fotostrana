
2009.03.28 by Kasheftin

Класс - универсальный костыль.
Используется, когда нужен "типа" массив, который можно передавать в функцию без &, и там его изменять (передается ссылка на объект а не его копия).


Пример:
	$ar = new ArrayClass(array("var1"=>1,"var2"=>2));
	$ar[var3] = 3; 
	$ar->var4 = 4;
	echo $ar->var1 . $ar[var2] . $ar->var3 . $ar[var4];

Результат: 12345




Пример:
	$ar = new ArrayClass(array("var1"=>1,"var2"=>2,"var3"=>3,"var4"=>4","var5"=>5));
	unset($ar[var3]);
	foreach($ar as $i => $v)
	{
		my_func($ar);
		echo $v;
	}
	function my_func($obj) { unset($obj[var1]); unset($obj[var2]); unset($obj[var5]); $obj->var6 = 6; $obj[var7] = 7; }

Результат: 1467




При использовании Iterator foreach не создает копию объекта, а работает со ссылкой на него. 
Поэтому работа ArrayClass при foreach отличается от foreach c обычным массивом:

	$ar = array(1,2,3);
	foreach($ar as $v) foreach($ar as $vv) { echo $v . $vv; }

Результат: 111213212223313233 

	$ar = new ArrayClass(array(1,2,3));
	foreach($ar as $v) foreach($ar as $vv) { echo $v . $vv; }

Результат: 111213


