<?php
/**
 * @author SMG smg38@yandex.ru
 * @version 1.0 2.2.22
 */
if(empty($argv))
{
	die("ERROR: Please run this script using the `php run.php`-command in your shell.");
}
echo "Initiating...\n";
set_time_limit(0);
error_reporting(E_ALL);
require "src/EmailServer.class.php";

$config=json_decode(file_get_contents("config.json"),true);
if($config==null)
{
	if(function_exists("json_last_error_msg"))
	{
		die("ERROR: Couldn't load config.json - ".json_last_error_msg()."\n");
	} else
	{
		die("ERROR: Couldn't load config.json - JSON error #".json_last_error()."\n");
	}
}

require "src/EmailAddr.class.php";
if(empty($config["users"]))
{
	die("ERROR: `users` is not defined in config.json\n");
}
if(empty($config["motd"]))
{
	$config["motd"] = "SMTP server";
}
if(empty($config["bind"]))
{
	$config["bind"] = "0.0.0.0";
}
if(empty($config["port"]))
{
	$config["port"] = "25";
}
if(empty($config["hostname"]))
{
	$config["hostname"] = "localhost";
}
if(empty($config["size"]))
{
	$config["size"] = 10240000;
}
$loglevel = false;
if($config["loglevel"]=="verbose")
{
	$loglevel = true;
}
if(empty($config["sender_blacklist"]))
{
	$config["sender_blacklist"] = ["wildcard@localhost"];
}
$addrs = [];
$blacklist=[];
foreach($config["users"] as $name => $data)
{
	$arr = explode("@", $name);
	if(count($arr) == 2)
	{
		$addr = new EmailAddr($name, $data);
		array_push($addrs, $addr);
	} else
	{
		echo "Skipped invalid user {$name}\n";
	}
}
foreach($config["sender_blacklist"] as $name)
{
	$name = preg_replace("/^\*@/","wildcard@",$name);
	$name = preg_replace("/@\*$/","@wildcard",$name);
	array_push($blacklist, $name);
}
unset($config["users"]);
echo "Loaded ".count($addrs)." addresses from config.\n";

$sSocket = socket_create(AF_INET, SOCK_STREAM, 0) or die();
socket_set_option($sSocket, SOL_SOCKET, SO_REUSEADDR, 1) or die();
try{
socket_bind($sSocket, $config["bind"], $config["port"]) or die();
} catch (Exception $e) {
	echo "You must start this with admin rights";
    die();
}
socket_listen($sSocket) or die();
echo "SMTP server. Listening on <".$config["bind"].":".$config["port"].">...\n";

$connections = [];
while(true)
{
	$sReader = [$sSocket];
	foreach($connections as $x => $c)
	{
		if($c->closed)
		{
			unset($connections[$x]);
			$c->log("~ disconnected");
			continue;
		}
		array_push($sReader, $c->socket);
	}
	$null = null;
	$num_changed_sockets = socket_select($sReader, $null, $null, null);
	if($num_changed_sockets === false)
	{
		echo "ERROR: ".socket_strerror(socket_last_error())."\n";
		exit;
	}
	if($num_changed_sockets > 0)
	{
		if(in_array($sSocket, $sReader))
		{
			$c = new Server(socket_accept($sSocket));
			array_push($connections, $c);
			if($loglevel) {$c->verbose=true;}
			$c->log("");
			$c->log("~ connected");
			$c->send("220 ".$config["motd"]);
		} else
		{
			foreach($connections as $c)
			{
				if(in_array($c->socket, $sReader))
				{
					$data = socket_read($c->socket, 1);
					if($data or $data=='0')
					{
						if($data != "")
						{
							if($data == "\n")
							{
								$c->line = trim($c->line);
								if($c->line != "")
								{
									$c->log(".> ".$c->line)->handle()->line="";
								}
							} else if($data != "\r")
							{
								$c->line .= $data;
							}
						}
					} else
					{
						$c->disconnect();
					}
				}
			}
		}
	}
}
?>
