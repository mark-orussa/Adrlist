<?php
class Adrlist_MessageCenter{
	/**
	 * Manage messages in the Message Center.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	*/
	
	//Properties.
	private $_body;
	
	public function __construct(){
	}
	
	public function newMessage($senderUserId,$recipientUserId,$subject,$body,$adminNote = false){
		/**
		 * Create a new message.
		 *
		 * As this is the start of a new message thread, there is no threadMessageId.
		 *
		 * @author	Mark O'Russa	<mark@markproaudio.com>
		 * @param	int		$senderUserId 		The userId of the sender.
		 * @param	int		$recipientUserId	The userId of the recipient.
		 * @param	varchar	$subject			A short description of the message.
		 * @param	text	$body				The message.
		 * @param	text	$adminNote			A message intended to help admins with the message. This is not visible to non-adm ins.
		 *
		 * @return	boolean	Returns a success message, otherwise throws a customException.
		*/
		global $debug, $message, $Dbc;
		
		$sendMessageError = 'We encountered a technical problem and were unable to send the message. Please try again in a few moments.<br>
<br>
If the problem persists please <a href="' . LINKSUPPORT . '">contact support</a>.';
		try{
			$senderUserId = intThis($senderUserId);
			$recipientUserId = intThis($recipientUserId);
			$body = empty($body) ? $this->_body : $body;
			if(empty($senderUserId)){
				$senderUserId = 1;
				$recipientUserId = 1;
				$subject = 'error trying to create message';
				$adminNote = 'The $senderUserId was empty while trying to create a new message. View debug information in the admin notes.' . $debug->output();
				//throw new Adrlist_CustomException($sendMessageError,'$senderUserId is empty.');
			}elseif(empty($recipientUserId)){
				$recipientUserId = 1;
				$subject = 'error trying to create message';
				$adminNote = 'The $recipientUserId was empty while trying to create a new message. View debug information in the admin notes.' . $debug->output();
			}elseif(strlen($subject) > 255){
				throw new Adrlist_CustomException('Please enter a subject shorter than 255 characters.','$subject too long. The character limit is 255.');
			}elseif(empty($body)){
				throw new Adrlist_CustomException('Please enter a message.','$body is empty.');
			}else{
				//Add a new message.
				$newMessageStmt = $Dbc->prepare("INSERT INTO
	messageCenter
SET
	senderUserId = ?,
	recipientUserId = ?,
	sentDatetime = ?,
	subject = ?,
	message = ?,
	adminNote = ?");
				$newMessageParams = array($senderUserId,$recipientUserId,DATETIME,$subject,$body,$adminNote);
				$newMessageStmt->execute($newMessageParams);
				return 'Your message was sent.';
			}
		}catch(Adrlist_CustomException $e){
			$myFile = __DIR__ . '../CustomLogs/MessageCenter' . __LINE__ . '.txt';
			$fh = fopen($myFile, 'w');
			fwrite($fh, $debug->output());
		}catch(PDOException $e){
			$debug->add('<pre>' . $e . '</pre>');
			$myFile = __DIR__ . '../CustomLogs/MessageCenter' . __LINE__ . '.txt';
			$fh = fopen($myFile, 'w');
			fwrite($fh, $debug->output());
			error(__LINE__,$sendMessageError,'<pre>' . $e . '</pre>');
		}
	}
	
	public function setBody($body){
		$this->_body = $body;
	}
}
