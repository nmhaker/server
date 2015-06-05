<?php

class Database
{
	private $errors = null;
	private $responses = null;

	private $connectionHandle = null;

	private $needToLogIn = false;

	private $mode = "DEPLOY";

	public function __construct()
	{
		//Connect do Database
		if($this->mode == "DEVELOPMENT")
			$this->connectionHandle = mysqli_connect("localhost", "*****", "*****", "******");
		else if($this->mode == "DEPLOY")
			$this->connectionHandle = mysqli_connect("localhost", "*****", "*****", "******");

		if(mysqli_connect_error() != NULL)
		{
			$this->errors[] = mysqli_connect_error();			
		}else
		{
			//echo 'Uspesno povezivanje sa bazom podataka';
		}
	}

	public function __destruct()
	{
		//Closing database connection
		mysqli_close($this->connectionHandle);
	}

	public function hasErrors()
	{
		if($this->errors != null)
			return true;
		else
			return false;
	}

	public function getErrors()

	{
		if($this->errors != null)
			return $this->errors;
		else
			return 'NO_ERRORS';
	}

	private function checkUsage($username)
	{
		$qry = "SELECT * FROM `korisnici`";
		$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";
		if(!$this->hasErrors()){
			$ima = false;
			while($row = mysqli_fetch_array($result))
				if($row['korisnicko_ime'] == $username)
					$ima = true;
			return $ima;
		}else
			return false;
	}


	public function registerUser()
	{
		if(!$this->hasErrors())
		{
			$ime = $_POST['ime'];
			$prezime = $_POST['prezime']; 	
			$korisnicko_ime = $_POST['korisnicko_ime'];
			$sifra = sha1($_POST['sifra']);
			$mobilni = $_POST['mobilni'];

			if(!$this->checkUsage($korisnicko_ime))
			{				
				$qry = "INSERT INTO `korisnici`(`korisnicko_ime`, `sifra`, `ime`, `prezime`, `mobilni`) VALUES ('$korisnicko_ime', '$sifra', '$ime', '$prezime', '$mobilni')";
				if(!mysqli_query($this->connectionHandle, $qry))
					$this->errors[] = "ERROR_101";
				else
					echo "RESPONSE_103";
			}else
			{
				$this->errors[] = "ERROR_102";
			}
		}
	}

	private function setStatusOnline($username)
	{
		$qry = "UPDATE `korisnici` SET `status` = 'online' WHERE `korisnicko_ime` = '$username'";
		$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";
		
		$this->setIP($username);
		$this->setPort($username);
	}

	private function LogedIn()
	{
		if(!$this->hasErrors())
		{
			$username = $_POST['korisnicko_ime'];

			$qry = "SELECT `status` FROM `korisnici` WHERE `korisnicko_ime` = '$username'";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";

			if(!$this->hasErrors())
			{
				$row = mysqli_fetch_array($result);
				if($row != NULL)
					if($row['status'] != 'offline')
					{
						return true;
					}
					else
					{
						$this->needToLogIn = true;
						return false;
					}
				else
				{
					$this->errors[] = "ERROR_103";
					return false;
				}
			}
			else
			{
				return false;
			}

		}else
		{
			return false;
		}
	}

	public function loginUser()
	{
		if((!$this->logedIn()) && ($this->needToLogIn == true))
		{
			$korisnicko_ime = $_POST['korisnicko_ime'];
			$sifra = sha1($_POST['sifra']);

			$qry = "SELECT `sifra` FROM `korisnici` WHERE `korisnicko_ime` = '$korisnicko_ime'";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";
			
			if(!$this->hasErrors())
			{
				$row = mysqli_fetch_array($result);
				if($row != FALSE)
				{
					if($row['sifra'] == $sifra)
					{
						$this->setStatusOnline($korisnicko_ime);
						echo "RESPONSE_104";
					}else
					{
						$this->errors[] = "ERROR_104";
					}
				}
				else
					$this->errors[] = "ERROR_105";
			}
		}else
		{
			$this->repairMessages($_POST['korisnicko_ime']);
			echo "RESPONSE_104" . "_FIXED_DB";
		}
	}
	
	public function repairMessages($u)
	{
		if(!$this->hasErrors()){
		//Popravi korisnikove poruke za korisnika
		$qry = "UPDATE `poruke` SET `status` = 'neprimljeno' WHERE `od` = '$u'";
		$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";
		//Popravi poruke prijatelja za korisnika
		$qry = "UPDATE `poruke` SET `status2` = 'neprimljeno' WHERE `za` = '$u'";
		$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";
		}
	}

	public function getFriends()
	{
		if($this->logedIn())
		{
			$username = $_POST['korisnicko_ime'];
			$qry = " SELECT `korisnicko_ime` FROM `korisnici` WHERE `korisnicko_ime` != '$username'";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";

			if(!$this->hasErrors())
			{
				if(mysqli_num_rows($result) > 0)
				{
					echo "RESPONSE_108" . "\n";
					while($row = mysqli_fetch_array($result))
					{
						 echo $row['korisnicko_ime'] . "\n";
					}
				}else
					echo "RESPONSE_106";
			}
		}else
			$this->errors[] = "ERROR_107";
	}

	public function getMyFriends()
	{		
		if($this->logedIn())
		{
			$username = $_POST['korisnicko_ime'];
			$qry = " SELECT `prijatelj` FROM `prijatelji` WHERE `korisnik` = '$username'";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";

			if(!$this->hasErrors())
			{
				if(mysqli_num_rows($result) > 0)
				{
					echo "RESPONSE_108" . "\n";
					while($row = mysqli_fetch_array($result))
					{
						 echo $row['prijatelj'] . "\n";
					}
				}else
					echo "RESPONSE_107";
			}
		}else
			$this->errors[] = "ERROR_107";
	}

	public function checkForMessages()
	{
		if($this->logedIn())
		{
			$username = $_POST['korisnicko_ime'];
			$qry = "SELECT * FROM `poruke` WHERE (`za` = '$username' AND `status2` = 'neprimljeno') ORDER BY `id` ASC";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";

			if(!$this->hasErrors())
			{
				if(mysqli_num_rows($result) > 0 )
				{
					$count = 0;
					while($row = mysqli_fetch_array($result))
					{
						$count++;
					}
					echo "RESPONSE_101" . "\n";
					echo $count . "\n";
				}else
				 	echo "RESPONSE_100";
			}else
				$this->errors[] = "ERROR_107";
		}
	}

	public function getMessage()
	{
		if($this->logedIn())
		{
			$username = $_POST['korisnicko_ime'];
			$od = $_POST['od'];
			$qry = "SELECT * FROM `poruke` WHERE (`za` = '$username' AND `od` = '$od' AND `status2` = 'neprimljeno') OR (`za` = '$od' AND `od` = '$username' AND `status` = 'neprimljeno') ORDER BY `id` ASC";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = mysqli_error($this->connectionHandle);//$this->errors[] = "ERROR_101";

			if(!$this->hasErrors())
			{
				if(mysqli_num_rows($result) > 0)
				{
					if(mysqli_num_rows($result) > 1)
					{		
						echo "RESPONSE_113" . "RESPONSE_112" . "\n";
						$row = mysqli_fetch_array($result);
						echo $row['id'] . "\n";
						echo $row['za'] . "\n";
						echo $row['poruka'] . "\n";	
					}else
					{
						echo "RESPONSE_112" . "\n";
						$row = mysqli_fetch_array($result);
						echo $row['id'] . "\n";
						echo $row['za'] . "\n";
						echo $row['poruka'] . "\n";
					}
				}else
					echo "RESPONSE_100";
			}
		}else
			$this->errors[] = "ERROR_107";
	}

	public function updateStatusPoruke()
	{
		if($this->logedIn())
		{
			$username = $_POST['korisnicko_ime'];

			if(isset($_POST['idPoruke']) or !empty($_POST['idPoruke']))
				$id = $_POST['idPoruke'];
			else
				$this->errors[] = "ERROR_3";
			if(isset($_POST['status']) && !empty($_POST['status']))
			{
				$status = $_POST['status'];
				$tip = "status";
			}
			else if(isset($_POST['status2']) && !empty($_POST['status2']))
			{
				$status = $_POST['status2'];
				$tip = "status2";
			}
			$qry = "UPDATE `poruke` SET `$tip` = '$status'  WHERE `id` = '$id'";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";

			if(!$this->hasErrors())
			{
				if($tip == "status")
				{
					echo "S";
					if($status == "primljeno")
						echo "RESPONSE_110"; 
					else if($status == "neprimljeno")
						echo "RESPONSE_111";
				}else if($tip == "status2")
				{
					echo "S2";
					if($status == "primljeno")
						echo "RESPONSE_110";
					else if($status == "neprimljeno")
						echo "RESPONSE_111";
				}			
			}
		}else
			$this->errors[] = "ERROR_107";
	}

	public function sendMessage()
	{
		if($this->logedIn())
		{
			$username = $_POST['korisnicko_ime'];
			$za = $_POST['primalac'];
			$poruka = $_POST['poruka'];

			$qry = "INSERT INTO `poruke`(`poruka`, `od`, `za`) VALUES('$poruka', '$username', '$za')";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";
			if(!$this->hasErrors())
				echo "RESPONSE_105";
		}
	}

	public function logOutUser()
	{
		if($this->logedIn())
		{
			$username = $_POST['korisnicko_ime'];
			$qry = "UPDATE `korisnici` SET `status`='offline' WHERE `korisnicko_ime`= '$username'";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";
			if(!$this->hasErrors())
				echo "RESPONSE_102";
		}
	}	

	
	//Korisceno u pocetku, da prepravlja greske programa, sada koristim repairMessages()
	public function fixDatabase()
	{
		if(!$this->hasErrors())
		{
			$qry = "UPDATE `korisnici` SET `status` = 'offline'";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = mysqli_error($this->connectionHandle);
			$qry = "UPDATE `poruke` SET `status`='neprimljeno',`status2`='neprimljeno'";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = mysqli_error($this->connectionHandle);
			
			if(!$this->hasErrors())
				echo "RESPONSE_114";
		}
	}
	
	public function checkUserAvailability(){
		if(!$this->hasErrors()){
			$qry = "SELECT * FROM `korisnici`";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";
			if(!$this->hasErrors())
				while($row = mysqli_fetch_array($result))
					if($row['korisnicko_ime'] == $_POST['ime_prijatelja'])
						echo "RESPONSE_115";
					else
						echo "RESPONSE_116";
		}	
	}
	
	public function addNewFriend(){
		if(!$this->hasErrors()){
			$u = $_POST['korisnicko_ime'];
			$i_p = $_POST['ime_prijatelja'];
			if($this->checkUsage($i_p))
				if(!$this->checkFriendsListForSpecificFriend($u, $i_p)){
					$qry = "INSERT INTO `prijatelji`(`korisnik`, `prijatelj`) VALUES('$u', '$i_p')";
					$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";
					if(!$this->hasErrors())
						echo "RESPONSE_117";
				}else{
					echo "RESPONSE_118";
				}
			else
				echo "RESPONSE_119";
			
		}
	}
	
	public function checkFriendsListForSpecificFriend($user, $friend){
		if(!$this->hasErrors()){
			$qry = "SELECT * FROM `prijatelji` WHERE `korisnik` = '$user'";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";
			$ima = false;
			if(mysqli_num_rows($result) > 0){
				while($row = mysqli_fetch_array($result))
					if($row['prijatelj'] == $friend){
						echo $row['prijatelj'];
						$ima = true;
					}
				return $ima;
			}else{
				echo "RESPONSE_122";
				return false;
			}
		}else{
			return false;
		}
	}
	
	public function setIP($username){
		$ip = $this->getIP();
		$qry = "UPDATE `korisnici` SET `ip_addr` = '$ip' WHERE `korisnicko_ime` = '$username'";
		$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";
	}
	
	private function getIP(){
		foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key)
		{
			if (array_key_exists($key, $_SERVER) === true)
			{
				foreach (array_map('trim', explode(',', $_SERVER[$key])) as $ip)
				{
					if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false)
					{
						return $ip;
					}
				}
			}
		}
	}
	
	private function getPort(){
		return $_SERVER['REMOTE_PORT'];
	}
	
	public function setPort($username){
		$port = $this->getPort();
		$qry = "UPDATE `korisnici` SET `port` = '$port' WHERE `korisnicko_ime` = '$username'";
		$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = "ERROR_101";
	}
	
	public function sendCoordinates(){
		if($this->logedIn()){
			$ki = $_POST['korisnicko_ime'];
			$data = $_POST['data'];
			$primaoc = $_POST['primaoc'];
			
			$qry = "INSERT INTO `koordinate`(`korisnicko_ime`, `data`, `primaoc`) VALUES('$ki', '$data', '$primaoc')";
			//$sql_statement_safe = mysql_real_escape_string($qry);
			mysqli_query($this->connectionHandle, $qry) or $this->errors[] = mysqli_error($this->connectionHandle);

			if(!$this->hasErrors())
				echo "RESPONSE_120";
			else
				echo "ERROR_101";
		}
	}
	
	public function downloadCoordinates(){
		if($this->logedIn()){
			$ki = $_POST['korisnicko_ime'];
			$posiljaoc = $_POST['posiljaoc'];
			
			$qry = "SELECT * FROM `koordinate` WHERE `korisnicko_ime` = '$posiljaoc' AND `primaoc` = '$ki'";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = mysqli_error($this->connectionHandle);
			
			if(mysqli_num_rows($result) > 0){
				echo "RESPONSE_121";
				while($row = mysqli_fetch_array($result)){
					if(!$this->hasErrors()){
						echo $row['data'];
						$id = $row['id'];
						$qry = "DELETE FROM `koordinate` WHERE `id` = '$id'";
						$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = mysqli_error($this->connectionHandle);
						if($result == FALSE)
							echo "ERROR_101";
					}
					break;
				}
			}else{
				echo "RESPONSE_100";
			}
		}
	}
	
	public function removeFriend()
	{
		if($this->logedIn()){
			$ki = $_POST['korisnicko_ime'];
			$ime_prijatelja = $_POST['ime_prijatelja'];
			
			$qry = "DELETE FROM `prijatelji` WHERE `korisnik` = '$ki' AND `prijatelj` = '$ime_prijatelja' ";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = mysqli_error($this->connectionHandle);
			
			if($result == FALSE)
				echo "ERROR_101";
			else
				echo "RESPONSE_123";
		}
	}
	
	public function clearConversation()
	{
		if($this->logedIn()){
			$ki = $_POST['korisnicko_ime'];
			$pr = $_POST['ime_prijatelja'];
			
			$qry = "DELETE FROM `poruke` WHERE `od` = '$ki' OR `za` = '$pr' ";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = mysqli_error($this->connectionHandle);
			
			$qry = "DELETE FROM `poruke` WHERE `od` = '$pr' OR `za` = '$ki' ";
			$result = mysqli_query($this->connectionHandle, $qry) or $this->errors[] = mysqli_error($this->connectionHandle);
			
			if($result == FALSE)
				echo "ERROR_101";
			else	
				echo "RESPONSE_124";
		}
	}

}
?>