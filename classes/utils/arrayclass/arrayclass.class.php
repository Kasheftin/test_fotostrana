<?php


/*	
	2009.03.28 by Kasheftin
*/


class ArrayClass implements ArrayAccess, Iterator
{
	protected $vrs = array();
	protected $start,$end,$current,$current_unlink_flag = false;

	public function __construct($vrs = null)
	{
		if (isset($vrs) && is_array($vrs))
		{
			$this->vrs = $vrs;
		}
		$this->updateIteratorParams();
	}

	public function __get($name)
	{
		/* 
			Здесь хак. Мы не можем просто вернуть $this->vrs[$name], потому что эта команда определит переменную $name в $this->vrs.
			При foreach метод valid проверяет существование элемента такой конструкцией: $this->vrs[$this->current];
			Эта штука рекурсивно вызывает __get, который определит элемент, и получится бесконечный цикл.
		*/
		$tmp = $this->vrs;
		return $tmp[$name];		
	}

	public function __set($name,$value)
	{
		$this->vrs[$name] = $value;
		$this->updateIteratorParams();
	}

	public function offsetExists($name)
	{
		return isset($this->vrs[$name]);
	}

	public function offsetGet($name)
	{
		return isset($this->vrs[$name]) ? $this->vrs[$name] : null;
	}

	public function offsetUnset($name)
	{
		if (isset($this->vrs[$name]))
		{
			unset($this->vrs[$name]);
			if ($this->current == $name)
			{
				// current_unlink_flag нужен, чтобы не сделать лишний переход next в foreach
				$this->current_unlink_flag = true;
			}
			$this->updateIteratorParams();
		}
	}

	public function offsetSet($name,$value)
	{
		$this->vrs[$name] = $value;
		$this->updateIteratorParams();
	}

	public function rewind()
	{              
		reset($this->vrs);
		$this->updateIteratorParams();
	}  

	public function key()
	{
		$this->updateIteratorParams();
		return $this->current;
	}

	public function current()
	{
		$this->updateIteratorParams();
		return isset($this->vrs[$this->current]) ? $this->vrs[$this->current] : null;
		
	}

	public function next()
	{
		/* 
			Здесь хак. Предположим, что в цикле foreach мы делаем unset элемента массива. 
			И пусть именно на этом элементе сейчас внутренний указатель массива $this->vrs.
			При unset внутренний указатель массива автоматически сдвигается на следующий элемент.
			Поэтому его уже не нужно двигать здесь.
			Эта ситуация возникает только в offsetUnset, там создается флаг current_unlink_flag.
		*/
		if ($this->current_unlink_flag)
		{
			$this->current_unlink_flag = null;
			if (key($this->vrs) !== false)
			{
				$this->current = key($this->vrs);
				return $this->vrs[$this->current];
			}
		}
		else
		{
			if (next($this->vrs) !== false)
			{
				$this->current = key($this->vrs);
				return $this->vrs[$this->current];
			}
		}
		return false;
	}

	public function valid()
	{
		$this->updateIteratorParams();
		return isset($this->vrs[$this->current]) ? true : false;
	}

	protected function updateIteratorParams()
	{
		$tmp = $this->vrs;
		reset($tmp);
		$this->start = key($tmp);
		end($tmp);
		$this->end = key($tmp);
		unset($tmp);
		$this->current = key($this->vrs);
	}
}

			
