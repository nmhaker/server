<?php

require('Commander.php');

class ChatApp
{
	//Properties
	private static $count = 0;

	private $errors = null;

	//System properties
	private $commander;

	//Methods
	//Constructor
	public function __construct()
	{
		if(!$this->checkAlreadyInstanced())
		{
			$this->initializeApplication();
		}else
		{
			$this->errors[] = "ERROR_1";
		}
	}
	//Destructor
	public function __destruct()
	{
		$this->showErrors();
	}

	public function initializeApplication()
	{
		$this->setStartValues();
	}

	public function checkAlreadyInstanced()
	{
		if($this->getCount() == 1)
			return true;
		else
			return false;
	}

	public function getCount()
	{
		return self::$count;
	}

	public function showErrors()
	{

		if($this->commander->hasErrors())
			foreach($this->commander->getErrors() as $e)
				echo $e;

		if($this->errors != null)
		{
			foreach($this->errors as $e)
				echo $e;
		}


	}

	public function setStartValues()
	{
		self::$count += 1;

		//Initialize system classes
		$this->commander = new Commander();
	}
}

?>