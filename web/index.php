<?php
require('../vendor/autoload.php');

echo getInfo();
die();
header('Content-Type: application/json');

$debug = true;

if ($debug)
{
	ini_set('display_errors',1);
	ini_set("error_reporting", E_ALL);
};

$REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];

$limit = 100;
$noDel = false;

if (isset($_GET['limit'])&&is_int((int) $_GET['limit'])) $limit=(int) $_GET['limit'];


if ($REQUEST_METHOD=='POST')
{
	$text = (string) implode("", file('php://input'));

	$responce = insertOne($text);

	if($responce) $text = $responce;

}
else
{
	
    if (isset($_GET['q'])) 
	{
		$text = $_GET['q'];
		$responce = insertOne($text);

		if($responce) $text = $responce;
		
	}	
	else
	{
		if (isset($_GET['nodel'])) $noDel = $_GET['nodel'];
		$text = ReadData($limit,$noDel);		

	}

}

	echo $text;

function getInfo(){
	
	$res= "
	<!DOCTYPE html>
    <html lang='ru'>
    <head>
        <meta charset='UTF-8'>
        <title>Viber Буфер 1С</title>
    </head>
    <body>
		<script src='https://gist.github.com/1Cmobile/57ad9ccfa9405a4d028296d4d6e9694d.js'></script>
    </body>
    </html>	
	";

	return $res;
}

function ReadData($limit=10,$noDel)
{

	$arrResult = array();

	$collection = getConnectionDB();
	if (!$collection) return;


	$arrOrder = array('time' => 1);

	$arrSort = array("limit" => $limit,"sort" => $arrOrder);

	$cursor = $collection->find([], ['limit' => $limit, 'sort' => ['time' => 1]]);

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

function getConnectionDB()
{

	$connectionString =  getenv('MONGODB_URI');

	if (!$connectionString) $connectionString ="mongodb://heroku_7jhm3vw7:usl2213vp3pdhlaj3h0a1jo11m@ds153179.mlab.com:53179/heroku_7jhm3vw7";

	$arr = array_reverse(explode('/', $connectionString));

	$dbName = $arr[0];

	if (!$dbName) {echo 'no db name'; return;};
	if (!$connectionString) {echo 'no connection string'; return;};

	$client = new MongoDB\Client($connectionString);

	$DataBase = $client->$dbName;

	$collection = $DataBase->incoming;
	
	return $collection;
}

$REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];

if ($REQUEST_METHOD=='POST')
{
	$authDate = auth();
	$countUserQuery = getenv('COUNT_USER_QUERY');

	if ($authDate['login']=='test'&&$authDate['QueryCount']>$countUserQuery) {
		header(' 500 Internal Server Error', true, 500);
		die("to many queries for test login ");};

	if ((isset($authDate['QueryPerMonth'])&&isset($authDate['QueryMonth']))&&$authDate['QueryPerMonth']>0 && $authDate['QueryMonth']<$authDate['QueryPerMonth']) {
		header(' 500 Internal Server Error', true, 500);
		die("to many queries per month ");};

	if (isset($_SERVER['HTTP_FILENAME']))
	{
		$filename = $_SERVER['HTTP_FILENAME'];	
	}
	elseif (isset(getallheaders()['filename'])) {
		$filename	= getallheaders()['filename'];
	}
	else {
		header(' 500 Internal Server Error', true, 500);
		die("need to filename on headers");
	};


	$baseText = (string) implode("", file('php://input'));

	$unicnameTime= ((string) time());
	$unicname= substr($authDate['paid'],10).$unicnameTime.'-'.$filename;

	$unicname =  substr($unicname,-180);

	$responce = insertOnefile($baseText,$authDate,$filename,$unicname);

	echo $unicname;
}

else
{
	if (isset($_GET['service'])) {
		service();
		echo 'service';
		return;
	}
	
	if (!isset($_GET['filename'])) die('need to &filename');

	$file = getFile($_GET['filename']);

	// header('Content-Type: application/x-unknown');
	header("Content-Disposition: attachment; filename=".$file['filename']); 
	echo base64_decode($file['content']);

}

function service(){

	$DataBase = getConnectionDB();

	$files = $DataBase->files;

	$curTime = time()-1800;

	$deleteFilter = ['time' =>['$lt'=>$curTime]];
	
	$cursor = $files->deleteMany($deleteFilter);
	
	
}

function getUserAccount($paid,$login,$pass){

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
	
	if(!$tempUser['login']==$login||!$tempUser['pass']==$pass) { die("Access forbidden");};

	$tempUser['QueryCount'] = $tempUser['QueryCount']+1;

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
	$arrTemp['QueryPerMonth'] 	=0;
	$arrTemp['QueryMonth'] 		=0;

	return $arrTemp;
}

function auth() {

	$paid = getPAID();

  if (!isset($_SERVER['PHP_AUTH_USER'])||!isset($_SERVER['PHP_AUTH_PW'])) {
	 header('WWW-Authenticate: Basic realm="viber-1c.herokuapp.com - need to login');
     header('HTTP/1.0 401 Unauthorized');
     echo "Вы должны ввести корректный логин и пароль для получения доступа к ресурсу \n";
     die("Access forbidden");
   } 
   else {
    
	$login 	= $_SERVER['PHP_AUTH_USER'];
	$pass 	= $_SERVER['PHP_AUTH_PW']; 

	$UserAccount = getUserAccount($paid,$login,$pass);

	return $UserAccount;
   };

}
  
function getFile($filename)
{

	$DataBase = getConnectionDB();

	$files = $DataBase->files;

	$document = $files->findOne(['unicname' =>$filename]);

	if (!$document)
	{	
		$files = $DataBase->settings;
		$document = $files->findOne(['key' =>"notfound"]);

	}
	
	$arrResult['content'] = $document['content'];
	$arrResult['filename'] = $document['filename'];

	return $arrResult;

}

function insertOnefile($baseText,$authDate,$filename,$unicname)
{
	if(!$baseText) die('no incoming data');
	
	$lengthFile = getenv('MAX_FILE_SIZE');

	if (!$lengthFile) $lengthFile = 30200;

	if (strlen($baseText)>$lengthFile) die('file is too large');

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
	$arr = array_reverse(explode('/', $connectionString));
	$dbName = $arr[0];

	if (!$dbName) {die('no db name');};

	if (!$connectionString) {die('no connection string');};
	$client = new MongoDB\Client($connectionString);
	$DataBase = $client->$dbName;
	return $DataBase;
}

function getPAID(){

	if (isset($_SERVER['HTTP_PAID']))
	{
		$paid = $_SERVER['HTTP_PAID'];	
	}
	elseif (isset(getallheaders()['paid'])) {
		$paid	= getallheaders()['paid'];
	}
	else {
		header(' 500 Internal Server Error', true, 500);
		die("need to id pablic account");
	};

	$paid = str_replace("pa:","",$paid);
	return $paid;
}
?>
?>