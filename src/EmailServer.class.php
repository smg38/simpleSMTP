<?php
/**
 * @author https://github.com/timmyRS/PHPMailServ
 * @author SMG smg38@yandex.ru
 * @version 1.0 2.2.22
 */
class Server
{
	public $id;
	public $socket;
	public $line = "";
	public $email = "";
	public $rcpt_to = "";
	public $mail_from = "";
	public $action = "";
	public $closed = false;
	public $verbose = false;

	public function __construct($socket)
	{
		global $connections;
		$id = 0;
		while(true)
		{
			$id++;
			$free = true;
			foreach($connections as $c)
			{
				if($c->id == $id)
				{
					$free = false;
					break;
				}
			}
			if($free)
			{
				break;
			}
		}
		$this->id = $id;
		$this->socket = $socket;
	}

	public function handle()
	{
		global $config;
		global $blacklist;
		$cmd = strtoupper(substr($this->line, 0, 4));
		#echo "--->".$this->line." [".$this->action."]\n";
		switch(substr($this->action, 0, 4))
		{
			case "AUTH":
			$name = trim(substr($this->action, 4));
			#echo "+++AUTH name=[".$name."]\n";
			if($name == "")
			{
				$mail = base64_decode($this->line);
				$arr = explode("@", $mail);
				#echo "+++mail=[".$mail."] arr=[".$arr[0]."]\n";
				if(count($arr) == 2)
				{
					$addr = EmailAddr::find($mail);
					#echo "+++addr=".$addr->name."\n";
					if($addr == null || !$addr->data["auth"])
					{
						$addr = EmailAddr::find($arr[0]."@wildcard");
					}
					if($addr == null)
					{
						$this->send("550 <{$mail}> is unknown");
					} else
					{
						if($addr->data["auth"])
						{
							$this->action = "AUTH".$addr->name;
							$this->send("334 UGFzc3dvcmQ6");		//Password:
						} else
						{
							print_r($addr->data);
							$this->send("503 User doesn't allow authentication");
						}
					}
				} else
				{
					$this->send("501 Bad address syntax");
				}
			} else
			{
				$addr = EmailAddr::find($name);
				#echo "line=".$this->line." auth=".$addr->data["auth"]."\n";
				if($this->line == $addr->data["auth"])
				{
					$this->email = $name;
					$this->action = "";
					$this->send("235 Authenticated as <{$name}>");
				} else
				{
					$this->send("503 Wrong password");
				}
			}
			break;

			case "DATA":
			if($this->line == ".")
			{
				EmailAddr::find($this->rcpt_to)->storeEmail("Email by <".$this->mail_from."> on ".date("r")."\n\n".substr($this->action, 4));
				$this->action = "";
				$this->rcpt_to = "";
				$this->mail_from = "";
				$this->send("250 OK");
			} else
			{
				$this->action .= $this->line."\n";
			}
			break;

			default:
			switch($cmd)
			{
				default:
				$this->send("502 Command <{$cmd}> not implemented");
				break;

				case "STAR":
				if(substr($this->line, 0, 8) == "STARTTLS")
				{
					$this->send("502 This thing has been written in PHP, so... no.");
				}
				break;

				case "QUIT":
				$this->disconnect();
				break;

				case "HELO":
				$arg = strtolower(trim(substr($this->line, 4)));
				if(in_array("wildcard@".$arg, $blacklist )) #$config["sender_blacklist"]))
				{
					$this->disconnect("552 You can go right away");
				} else
				{
					$this->send("250 ".$config["hostname"]);
				}
				break;

				case "EHLO":
				$arg = strtolower(trim(substr($this->line, 4)));
				if(in_array("wildcard@".$arg, $blacklist )) #$config["sender_blacklist"]))
				{
					$this->disconnect("552 You can go right away");
				} else
				{
					$this->send("250-".$config["hostname"]."\n250-SIZE ".$config["size"]."\n250-VRFY\n250 AUTH LOGIN");
				}
				break;

				case "MAIL":
				$arg = strtoupper(trim(substr($this->line, 4, 6)));
				if($arg == "FROM:")
				{
					$arg = strtolower(trim(substr($this->line, 10)));
					$arr = explode(" ", $arg);
					if(!empty($arr[1]))
					{
						if(substr($arr[1], 0, 5) == "SIZE=")
						{
							if(intval(substr($arr[1], 5)) > $config["size"])
							{
								$this->disconnect("552 Message size exceeds size limit");
								return;
							}
						}
					}
					$mail = $arr[0];
					if(substr($mail, 0, 1) == "<" && substr($mail, -1) == ">")
					{
						$mail = substr($mail, 1, -1);
						$arr = explode("@", $mail);
						if(count($arr) == 2)
						{
							if(in_array($mail, $blacklist )) #$config["sender_blacklist"]))
							{
								$this->disconnect("552 You can go right away");
							} else
							{
								$authed = false;
								if(EmailAddr::find($mail) != null)
								{
									if($this->email == $mail)
									{
										$authed = true;
									}
								}
								else if(EmailAddr::find("wildcard@".$arr[0]) == null)
								{
									$authed = true;
								}
								if($authed)
								{
									$this->mail_from = $mail;
									$this->send("250 OK");
								} else
								{
									$this->send("501 You need to authenticate yourself to send an email from this account");
								}
							}
						} else
						{
							$this->send("501 Bad address FROM syntax");
						}
					} else
					{
						$this->send("501 Syntax: MAIL FROM:<address>");
					}
				} else
				{
					$this->send("501 Syntax: MAIL FROM:<address>.");
				}
				break;

				case "RCPT":
				if($this->mail_from == "")
				{
					$this->disconnect("221 What kind of bad thing are you?");
				} else
				{
					$arg = strtoupper(trim(substr($this->line, 4, 4)));
					if($arg == "TO:")
					{
						$mail = strtolower(trim(substr($this->line, 8)));
						#echo "Mail=".$mail."\n";
						if(substr($mail, 0, 1) == "<" && substr($mail, -1) == ">")
						{
							$mail = substr($mail, 1, -1);
							$arr = explode("@", $mail);
							if(count($arr) == 2)
							{
								$addr = null;
								if(EmailAddr::find($mail) != null)
								{
									$addr = EmailAddr::find($mail);
								}
								else if(EmailAddr::find($arr[0]."@wildcard") != null)
								{
									$addr = EmailAddr::find($arr[0]."@wildcard");
								}
								else if(EmailAddr::find("wildcard@".$arr[1]) == null)
								{
									$addr = EmailAddr::find("wildcard@".$arr[1]);
								}
								else if(EmailAddr::find("wildcard@wildcard") == null)
								{
									$addr = EmailAddr::find("wildcard@wildcard");
								} else
								{
									$this->send("454 The receiver is in another castle");
								}
								#echo "Res_to_mail=".$addr->name;
								if($addr != null)
								{
									$this->rcpt_to = $addr->name;
									$this->send("250 OK");
								}
							} else
							{
								$this->send("501 Bad address RCPT syntax");
							}
						} else
						{
							$this->send("501 Syntax: RCPT TO:<address>");
						}
					} else
					{
						$this->send("501 Syntax: RCPT TO:<address>.");
					}
				}
				break;

				case "DATA":
				if($this->mail_from == "" || $this->rcpt_to == "")
				{
					$this->disconnect("221 What kind of bad thing are you?");
				} else
				{
					$this->action = "DATA";
					$this->send("354 End data with <CR><LF>.<CR><LF>");
				}
				break;

				case "AUTH":
				$arg = strtoupper(trim(substr($this->line, 4)));
				if($arg=="")
				{
					$this->send("501 Syntax: AUTH method");
				} else
				{
					if($arg == "LOGIN")
					{
						$this->action = "AUTH";
						$this->send("334 VXNlcm5hbWU6");
						#echo "++>".$this->action."\n";
					} else
					{
						$this->send("503 Authentication method {$arg} is not supported");
					}
				}
				break;

				case "RSET":
				$this->mail_from = "";
				$this->rcpt_to = "";

				case "HELP":
				case "NOOP":
				$this->send("250 Ok");
				break;

				case "EXPN":
				case "VRFY":
				$mail = strtolower(trim(substr($this->line, 4)));
				if($mail=="")
				{
					$this->send("501 Syntax: VRFY address");
				} else
				{
					$arr = explode("@", $mail);
					if(count($arr) == 2)
					{
						if(EmailAddr::find($mail) == null && EmailAddr::find("wildcard@".$arr[1]) == null && EmailAddr::find($arr[0]."@wildcard") == null && EmailAddr::find("wildcard@wildcard") == null)
						{
							$this->send("550 <{$mail}> is unknown");
						} else
						{
							$this->send("250 ".$mail);
						}
					} else
					{
						$this->send("501 Bad address VRFY syntax");
					}
				}
				break;
			}
			break;
		}
		return $this;
	}

	public function send($sMessage)
	{
		foreach(explode("\n", $sMessage) as $line)
		{
			$this->log("<. ".$line);
			@socket_write($this->socket, $line."\r\n");
		}
	}

	public function log($msg)
	{
		if($this->verbose){echo "#".$this->id." ".$msg."\n";}
		
		return $this;
	}

	public function disconnect($msg = "221 Bye")
	{
		$this->send($msg);
		socket_shutdown($this->socket);
		socket_close($this->socket);
		$this->closed = true;
	}
}
?>