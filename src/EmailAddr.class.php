<?php
/**
 * @author https://github.com/timmyRS/PHPMailServ
 * @author SMG smg38@yandex.ru
 * @version 1.0 2.2.22
 */
class EmailAddr
{
	public $name;
	public $data;

	public function __construct($name, $data)
	{
		$name = preg_replace("/^\*@/","wildcard@",$name);
		$name = preg_replace("/@\*$/","@wildcard",$name);
		$this->name = $name;
		$this->data = $data;
		if(empty($data["folder"]))
		{
			$folder = $name;
		} else
		{
			$folder = $data["folder"];
		}
		// if(empty($data["dontend"]))
		// {
		// 	$this->data["dontend"] = false;
		// }
		if(empty($data["auth"]))
		{
			$this->data["auth"] = false;
		}
		$folder = str_replace("@", "__", strtolower($folder));
		$chars = range("a","z");
		for($i=0;$i<10;$i++)
		{
			array_push($chars,$i);
		}
		array_push($chars,"-");
		$arr = str_split($folder);
		$folder = "";
		foreach($arr as $char)
		{
			if(in_array($char, $chars))
			{
				$folder.=$char;
			} else
			{
				$folder.="_";
			}
		}
		$this->data["folder"] = $folder;
	}

	public function storeEmail($data)
	{
		$add = "";
		if(!file_exists($this->data["folder"]))
		{
			mkdir($this->data["folder"]);
		}
		while(file_exists($this->data["folder"]."/".time().$add.".eml"))
		{
			if($add == "")
			{
				$add = "-0";
			} else
			{
				$add = ($add * -1 + 1);
			}
		}
		file_put_contents($this->data["folder"]."/".time().$add.".eml",$data);
	}

	public static function find($mail)
	{
		global $addrs;
		$mail_01 = explode("@", $mail);
		foreach($addrs as $addr)
		{
			$mail_bd = explode("@", $addr->name);
			if(($addr->name == $mail) or
			($mail_01[0] == $mail_bd[0] ) and ('wildcard' == $mail_bd[1] ) or
			($mail_01[1] == $mail_bd[1] ) and ('wildcard' == $mail_bd[0] ) or
			('wildcard' == $mail_bd[1] ) and ('wildcard' == $mail_bd[0] )
			) { return $addr; }
		}
		return null;
	}
}
?>