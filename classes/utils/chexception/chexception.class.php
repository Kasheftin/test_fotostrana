<?php

class CHException extends Exception
{
	protected $type = null;

	public function getType()
	{
		return $this->type;
	}

	public function __construct($message=null,$type=null,$code=0,Exception $previous = null)
	{
		$this->type = $type;
		parent::__construct($message,$code,$previous);
	}
}
