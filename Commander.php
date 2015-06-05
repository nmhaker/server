<?php

//define('__ROOT__', dirname(dirname(__FILE__)));


require_once('Database.php');
	
class Commander
{

	//Properties
	private $action = null;

	private $db = null;

	private $errors = null;

	//Methods
	public function __construct()
	{
		if(!$this->checkAccess())
		{
			echo 'ERROR_ACCES_DENIED';
		}else
		{
			if(!$this->setupCommander())
				$this->errors[] = "ERROR_2";
			else
			{
				if(!$this->setPostValues())
					$this->errors[] = "ERROR_3";
				else{
					$this->handlePostValues();
				}
			}
		}
	}

	public function __destruct()
	{
		
	}

	public function checkAccess()
	{
		if(isset($_POST['key']))
			if($_POST['key'] == 'SIFRA')
				return true;
			else
				return false;
		else
			return false;
	}

	public function setupCommander()
	{
		$this->db = new Database();
		if($this->db->hasErrors())
			return false;
		else
			return true;
	}

	public function setPostValues()
	{
		if(isset($_POST['action']))
		{
			$this->action = $_POST['action'];
			return true;
		}
		else
			return false;		
	}

	public function handlePostValues()
	{
		switch($this->action)
		{
			case 1: 
				$this->db->registerUser();
				break;
			case 2:
				$this->db->loginUser();
				break;
			case 3:
				$this->db->checkForMessages();
				break;
			case 4:
				$this->db->sendMessage();
				break;
			case 5: 
				$this->db->logOutUser();
				break;
			case 6:
				$this->db->getFriends();
				break;
			case 7:
				$this->db->getMyFriends();
				break;
			case 8:
				$this->db->updateStatusPoruke();
				break;
			case 9:
				$this->db->getMessage();
				break;
			case 10:
				$this->db->fixDatabase();
				break;
			case 11:
				$this->db->checkUserAvailability();
				break;
			case 12:
				$this->db->addNewFriend();
				break;
			case 13:
				$this->db->sendCoordinates();
				break;
			case 14:
				$this->db->downloadCoordinates();
				break;
			case 15:
				$this->db->removeFriend();
				break;
			case 16:
				$this->db->clearConversation();
			default:
				$this->errors[] = "ERROR_4";
		}
	}

	public function hasErrors()
	{
		if($this->db != null)
			if($this->db->hasErrors())
				foreach($this->db->getErrors() as $e)
					$this->errors[] = $e;

		if($this->errors != null)
			return true;
		else
			return false;
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public function getActionValues()
	{
		if($this->action != null && $this->value != null)
		{
			$array["action"] = $this->action;
			$array["value"] = $this->value;

			return $array;
		}else
		{
			return null;
		}
		
	}
}

?>