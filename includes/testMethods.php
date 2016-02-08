<?php require_once('ssl.php');
$debug->newFile('test/testMethods.php');
$success = false;
if(!empty($_POST['mode'])){
	define('MODE', $_POST['mode']);
	$debug->add('MODE: ' . MODE);
	if(MODE == 'testDb'){
		testDb();
	}
}else{
	define('MODE', '');
	$debug->add('$_POST[\'mode\'] is empty.');
}

function testDb(){
	global $Dbc, $debug, $message, $success;
	if(!empty($_POST['email']) && emailValidate($_POST['email']) && !empty($_POST['firstName']) && !empty($_POST['lastName']) && !empty($_POST['password']) &&  passwordValidate($_POST['password'])){
		destroySession();
		$email = trim($_POST['email']);
		$pass = sha1(trim($_POST['password']));
		$firstName = trim($_POST['firstName']);
		$lastName = trim($_POST['lastName']);
		$rememberMeCode = sha1($email);
		$Dbc->beginTransaction();
		try{
			$stmt = $Dbc->prepare("SELECT getUserIdByEmail(?) AS 'userId'");
			$stmt .= $stmt->execute(array($email));
			while($row = $stmt->fetch()){
				$debug->add('$row[\'userId\']: ' . $row['userId']);
				$debug->printArray($row, '$row');
				if(empty($row['userId'])){//There are no users with the email address, so continue.
					pdoError(__LINE__, $stmt, 1);
					$stmt = $Dbc->prepare("INSERT INTO
	users
SET
	primaryEmail = ?,
	userPassword = ?,
	firstName = ?,
	lastName = ?,
	joinDate = ?");
					if($stmt->execute(array($email,$pass,$firstName,$lastName,DATETIME))){
						$debug->add('last id: ' . $Dbc->lastInsertId());
					}else{
						pdoError(__LINE__, $stmt);
					}
				}else{
					$message .= 'That email address is already associated with an account. Please enter a different email address.<br>';
				}
			}
		}catch(PDOException $e){//Rollback occurs automatically if an exception is thrown.
				error(__LINE__,'','<pre>' . $e . '</pre>');
				pdoError(__LINE__);
		}
	}elseif(empty($_POST['email'])){
		$debug->add('email is empty on line ' . __LINE__ . '');
		$message .= 'Please enter an email address.';
	}elseif(!emailValidate($_POST['email'])){
		$message .= 'Please enter a valid email address.';
		$debug->add('Email address is not valid.');		
	}
	elseif(empty($_POST['firstName'])){
		$debug->add('first name is empty on line ' . __LINE__ . '.');
		$message .= 'Please enter a First Name.';
	}elseif(empty($_POST['lastName'])){
		$debug->add('last name is empty on line ' . __LINE__ . '.');
		$message .= 'Please enter a Last Name.';
	}elseif(empty($_POST['password'])){
		$debug->add('password is empty on line ' . __LINE__ . '.');
		$message .= 'Please enter a password.';
	}else{
		$debug->add('Something is missing.');
	}
	returnData();
}