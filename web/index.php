<?php
require('../vendor/autoload.php');

$REQUEST_METHOD = "";
$countUserFiles = 0;
$countUserQuery = 0;
$text           = 0;
$lengthFile 	= 0;
$noDel 			= false;
$paid 			= false;

initialize();

if ($REQUEST_METHOD=='POST')
{

	header('Content-Type: application/json');

	if (isset($_SERVER['HTTP_FILENAME']))
	{
		$filename = $_SERVER['HTTP_FILENAME'];	
	}
	else {
		CheckViberServer();
	};

	if ($filename){
		
		$authDate = auth(true);

		$baseText = (string) implode("", file('php://input'));

		$unicnameTime= ((string) time());

		$unicname= substr($authDate['paid'],10).$unicnameTime.'-'.$filename;

		$unicname =  substr($unicname,-180);

		$responce = insertOnefile($baseText,$authDate,$filename,$unicname);

		echo $unicname;

	}
	else{
		
		$text = (string) implode("", file('php://input'));

		$responce = insertOne($text);

		if($responce) $text = $responce;
		
	};
	



}
elseif ($REQUEST_METHOD=='GET')
{
	
	if (isset($_GET['service'])) {
		service();
		header('Content-Type: application/json');
		echo 'service';
		return;
	}
	elseif (isset($_GET['filename'])){

		$file = getFile($_GET['filename']);

		header("Content-Disposition: attachment; filename=".$file['filename']); 
		header('Content-Type: image/jpg');
		if ($file['encodet']){
			$text = base64_decode($file['content']);
		}
		else{
			$text = $file['content'];
		}
				
	 }
	elseif( $paid ){
		
		$authDate = auth();
		
		header('Content-Type: application/json');
		$text = ReadMessages();
	}

	else{

		$text = getInfo(); 

	}
	
	
};

echo $text;


function initialize(){

	global $REQUEST_METHOD, $countUserFiles, $countUserQuery, $noDel, $lengthFile, $paid ;

	$REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];
		
	$debug = getenv('DEBUG');
	if ($debug)
	{
		ini_set('display_errors',1);
		ini_set("error_reporting", E_ALL);
		$noDel = true;
	};

	$noDel = false;
	
	$countUserFiles = getenv('COUNT_USER_FILES');
	
	$countUserQuery = getenv('COUNT_USER_QUERY');
	
	$lengthFile 	= getenv('MAX_FILE_SIZE');

	$paid = isset($_SERVER['HTTP_PAID']) ? $_SERVER['HTTP_PAID'] : "";

}

function CheckViberServer(){

	die('it is not Viber man');
}
function getInfo(){
	
	$res= "
	<!DOCTYPE html>
    <html lang='ru'>
    <head>
        <meta charset='UTF-8'>
        <title>Viber Буфер 1С</title>
    </head>
    <body>
		<script src='https://gist.github.com/best-tech/57ad9ccfa9405a4d028296d4d6e9694d.js'></script>
    </body>
    </html>	
	";

	return $res;
}

function ReadMessages()
{
	global $noDel;

	$arrResult = array();

	$DataBase = getConnectionDB();
	
	$collection = $DataBase->messages;

	$arrOrder = array('time' => 1);

	$arrSort = array("limit" => 100,"sort" => $arrOrder);

	$cursor = $collection->find([], $arrSort);

	foreach ($cursor as $document) {
		
		if (!isset($document['content'])) continue;
		
		$arrResult[] = json_decode($document['content']);
		
		if(!$noDel)	$collection->deleteOne(['_id' => $document['_id']]);

	}

	 
	return json_encode($arrResult);
}
function insertOne($text)
{
	if(!$text) return 'no incoming data';

	try {
		$jsObject = json_decode($text,true);
		
	} catch (Exception $e) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		return 'reply no JSON format';
	}
	if (!$jsObject) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		return 'reply no JSON format';
	};
	$collection = getConnectionDB();
	if (!$collection) return 'no db connection';
	
	$post = array(
         'time'     => time(),
		 'event'     => $jsObject['event'],
		 'timestamp'     => $jsObject['timestamp'],
		 'message_token'     => $jsObject['message_token'],
         'content'   => $text
      );
	
    $collection->insertOne($post);

}

function service(){

	$DataBase = getConnectionDB();

	$files = $DataBase->files;

	$curTime = time()-1800;

	$deleteFilter = ['time' =>['$lt'=>$curTime]];
	
	$cursor = $files->deleteMany($deleteFilter);
	
	
}

function getUserAccount($paid,$workWithFiles=false){

	$DataBase = getConnectionDB();
	
	$users = $DataBase->users;

	$user = $users->findOne(['paid' => $paid]);

	$tempUser = getTemplateUser();
	// пользователь не найден
	if (!$user){	
		$user = $tempUser;
		$user['paid'] 	= $paid;
		$user['login'] 	= 'test';
		$user['pass'] 	= 'test';
		
		$users->insertOne($user);
		$tempUser = $user;
	}
	else
	{
		foreach ($tempUser as $key => $value)
		{
			$tempUser[$key]=$user[$key];	
		}
	}
	
	//if(!$tempUser['login']==$login||!$tempUser['pass']==$pass) { die("Access forbidden");};
	if ($workWithFiles)
	{
		$tempUser['CountFiles'] = $tempUser['CountFiles']+1;
	}
	else{

		$tempUser['QueryCount'] = $tempUser['QueryCount']+1;
	}

	$user = $users->updateOne(['paid' => $paid],['$set' => $tempUser]);

	return $tempUser;
}

function getTemplateUser()
{
	$arrTemp = array();
	$arrTemp['paid'] 			='';
	$arrTemp['QueryCount'] 		=0;
	$arrTemp['login'] 			='';
	$arrTemp['pass'] 			='';
	$arrTemp['CountFiles']		=0;

	return $arrTemp;
}

function auth($WorkWithFile=false) {

	global $paid;

	if (!$paid) {
		header('WWW-Authenticate: Basic realm="viber-1c.herokuapp.com - need to login');
		header('HTTP/1.0 401 Unauthorized');
		die("not user id params");
	};

	$login 	= getenv('LOGIN');
	$pass 	= getenv('PASS');

	$currenntLogin 	= "" || isset($_SERVER['PHP_AUTH_USER']);
	$currenntPass 	= "" || isset($_SERVER['PHP_AUTH_PW']);

	$UserAccount = getUserAccount($paid,$WorkWithFile);

	if (($login||$pass) && !isset($_SERVER['PHP_AUTH_USER'])||!isset($_SERVER['PHP_AUTH_PW'])) {
	 	header('WWW-Authenticate: Basic realm="viber-1c.herokuapp.com - need to login');
     	header('HTTP/1.0 401 Unauthorized');
     	echo "Вы должны ввести корректный логин и пароль для получения доступа к ресурсу \n";
     	die("Access forbidden");
   	} 

	if (!$login=$currenntLogin || !$currenntPass=$pass) {
			header('WWW-Authenticate: Basic realm="viber-1c.herokuapp.com - need to login');
			header('HTTP/1.0 401 Unauthorized');
			echo "Не верный логин или пароль \n";
			die("Access forbidden");
		} 

	if ($WorkWithFile){
		global $countUserFiles;
		if ($countUserFiles && $authDate['CountFiles']>$countUserFiles) {
			header(' 500 Internal Server Error', true, 500);
			die("to many post file for this paid ");};
	}
	else{
		global $countUserQuery;
		if ($countUserQuery && $authDate['QueryCount']>$countUserQuery) {
			header(' 500 Internal Server Error', true, 500);
			die("to many queries to this login ");};
	};

}
  
function getFile($filename)
{

	$DataBase = getConnectionDB();

	$files = $DataBase->files;

	$document = $files->findOne(['unicname' =>$filename]);
	if (!$document)
	{	
		
		$imgData = file_get_contents("images/404.jpg");
		$document['content'] = $imgData;
		$document['encodet'] = false;
		$document['filename'] = 'file_not_found.jpg';

	}
	else
	{
		$document['encodet'] = true;
	}
	$arrResult['content'] = $document['content'];
	$arrResult['filename'] = $document['filename'];
	$arrResult['encodet'] = $document['encodet'];

	return $arrResult;

}

function insertOnefile($baseText,$authDate,$filename,$unicname)
{
	if(!$baseText) die('no incoming data');
	
	global $lengthFile;

	if ($lengthFile>0 && strlen($baseText)>$lengthFile) die('file is too large');

	$DataBase = getConnectionDB();
	$files = $DataBase->files;
	
	$post = array(
         'time'     	=> time(),
		 'unicname'   	=> $unicname,
		 'filename'   	=> $filename,
		 'paid'     	=> $authDate['paid'],
         'content'   	=> $baseText
      );

    $files->insertOne($post);

}

function getConnectionDB()
{
	$connectionString =  getenv('MONGODB_URI');
	$connectionString = str_replace("'","",$connectionString);
  	$arr = array_reverse(explode('/', $connectionString));
	$dbName = $arr[0];

	if (!$dbName) {die('no db name');};

	if (!$connectionString) {die('no connection string');};
	$client = new MongoDB\Client($connectionString);
	$DataBase = $client->$dbName;

	if (!$DataBase) die('no db connection');

	return $DataBase;
}

?>