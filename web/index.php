<?php
require('../vendor/autoload.php');

$REQUEST_METHOD = "";
$countUserFiles = 0;
$countUserQuery = 0;
$text           = 0;
$lengthFile 	= 0;
$noDel 			= false;
$paid 			= "";

initialize();

if ($REQUEST_METHOD=='POST')
{
	
	header('Content-Type: application/json');
	
	if (isset($_SERVER['HTTP_FILENAME'])){
		
		$filename = $_SERVER['HTTP_FILENAME'];
		
		if ($filename){
			
			$authDate = auth(true);
			
			$baseText = (string) implode("", file('php://input'));
			
			$unicnameTime= ((string) time());
			
			$unicname= substr($authDate['paid'],10).$unicnameTime.'-'.$filename;
			
			$unicname =  substr($unicname,-180);
			
			$responce = insertOnefile($baseText,$authDate,$filename,$unicname);
			
			echo $unicname;
			
		}
	}
	else {
		
		CheckViberServer();
		
		$text = (string) implode("", file('php://input'));
		
		$responce = insertOne($text);
		
		if($responce) $text = $responce;
	}
	;
	
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
		$text = ReadData();
	}
	
	else{
		
		$text = getInfo();
		
	}
	
}


echo $text;


function initialize(){
	
	global $REQUEST_METHOD, $countUserFiles, $countUserQuery, $noDel, $lengthFile, $paid ;
	
	$REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];
	
	$debug = getenv('DEBUG');
	
	if ($debug){
		
		ini_set('display_errors',1);
		ini_set("error_reporting", E_ALL);
		$noDel = true;
	}
	
	$noDel = false;
	
	$countUserFiles = getenv('COUNT_USER_FILES');
	
	$countUserQuery = getenv('COUNT_USER_QUERY');
	
	$lengthFile 	= getenv('MAX_FILE_SIZE');
	
	$paid = isset($_SERVER['HTTP_PAID']) ? $_SERVER['HTTP_PAID'] : "";
	
}

function CheckViberServer(){
	
	global $paid;

	$isCorrect = true;
	$incorrectMessage = '';
	if (!isset($_SERVER['HTTP_X_FORWARDED_PORT'])){
		
		$isCorrect = false;
		$incorrectMessage = 'it is not https remote';
		
	}
	
	if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
		
		$isCorrect = false;
		$incorrectMessage = 'it is not viber ip remote';
		
	}
	else{
	
	if (!$_SERVER['HTTP_X_FORWARDED_PORT'] = "443"){
		
		$isCorrect = false;
		$incorrectMessage = 'it is not https 443';	
	}
	
		$vibersIP = '52.0.253.';
		$curIP = $_SERVER['HTTP_X_FORWARDED_FOR'];

		if (!substr($curIP,0,9)==$vibersIP){
			
			$isCorrect = false;
			$incorrectMessage = 'it is not viber ip remote';
			
		}
	}
	
	if ( !isset($_SERVER['QUERY_STRING'])  ||  substr($_SERVER['QUERY_STRING'],0,5) !== "paid=" ){
		
		$isCorrect = false;
		$incorrectMessage = 'no paid for save message'; 
	}
	else{

		$paid = substr($_SERVER['QUERY_STRING'],5);
	}

	if (!$isCorrect)
		{
		$DataBase = getConnectionDB();
		$collection = $DataBase->logs;
		$post = array('time'     => time(),'message' => $incorrectMessage,'server'     	=> $_SERVER	);
		$collection->insertOne($post);
		die($incorrectMessage);
	}
	else
	{
		// $DataBase = getConnectionDB();
		// $collection = $DataBase->logs;
		// $post = array('time'     => time(),'server'     	=> $_SERVER	);
		// $collection->insertOne($post);
	}
	
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

function ReadData()
{
	global $noDel,$paid;
	
	$arrResult = array();
	
	$DataBase = getConnectionDB();
	
	$collection = $DataBase->messages;
	
	$arrOrder = array('time' => 1);
	
	$arrSort = array("limit" => 100,"sort" => $arrOrder);
	
	$cursor = $collection->find(['paid' => $paid], $arrSort);
	
	foreach ($cursor as $document) {
		
		if (!isset($document['content'])) continue;
		
		$arrResult[] = json_decode($document['content']);
		
		if(!$noDel)	$collection->deleteOne(['_id' => $document['_id']]);
		
	}
	
	
	return json_encode($arrResult);
}
function insertOne($text)
{
	global $paid;
	
	if(!$text) return 'no incoming data';
	
	try {
		$jsObject = json_decode($text,true);
		
	}
	catch (Exception $e) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		return ' is reply no JSON format';
	}
	if (!$jsObject) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		return 'is reply no JSON format';
	}
	;
	$DataBase = getConnectionDB();
	
	$collection = $DataBase->messages;
	
	$post = array('time'     => time(),
												'event'     	=> $jsObject['event'],
												'paid'     		=> $paid,
												'timestamp'     => $jsObject['timestamp'],
												'message_token' => $jsObject['message_token'],
												'content'   	=> $text
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
	
	if (!$user){
		
		$user = $tempUser;
		$user['paid'] 	= $paid;
		
		$users->insertOne($user);
		
		$tempUser = $user;
	}
	else{
		
		foreach ($tempUser as $key => $value){
			$tempUser[$key]=$user[$key];
		}
	}
	
	if ($workWithFiles){
		
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
	$arrTemp['CountFiles']		=0;
	
	return $arrTemp;
}

function auth($WorkWithFile=false) {
	
	global $paid;
	
	if (!$paid) {
		header('WWW-Authenticate: Basic realm="viber-1c.herokuapp.com - need to login');
		header('HTTP/1.0 401 Unauthorized');
		echo "for login you need paid \n";
		die("Access forbidden");
	}
	
	$login 	= getenv('LOGIN');
	$pass 	= getenv('PASS');
	
	$currenntLogin 	= isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : "";
	$currenntPass 	= isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : "";
	
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
			die("to many post file for this paid ");
		}
		;
	}
	else{
		global $countUserQuery;
		if ($countUserQuery && $authDate['QueryCount']>$countUserQuery) {
			header(' 500 Internal Server Error', true, 500);
			die("to many queries to this login ");
		}
		;
	}
	;
	
}

function getFile($filename)
{
	
	$DataBase = getConnectionDB();
	
	$files = $DataBase->files;
	
	$document = $files->findOne(['unicname' =>$filename]);
	
	if (!$document){
		
		$imgData = file_get_contents("images/404.jpg");
		$document['content'] = $imgData;
		$document['encodet'] = false;
		$document['filename'] = 'file_not_found.jpg';
		
	}
	
	else{
		
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
	
	$post = array('time'     	=> time(),
												'unicname'   	=> $unicname,
												'filename'   	=> $filename,
												'paid'     	=> $authDate['paid'],
												'content'   	=> $baseText);
	
	$files->insertOne($post);
	
}

function getConnectionDB()
{
	$connectionString =  getenv('MONGODB_URI');
	$connectionString = str_replace("'","",$connectionString);
	$arr = array_reverse(explode('/', $connectionString));
	$dbName = $arr[0];
	
	if (!$dbName) {
		die('no db name');
	}
	;
	
	if (!$connectionString) {
		die('no connection string');
	}
	;
	$client = new MongoDB\Client($connectionString);
	$DataBase = $client->$dbName;
	
	if (!$DataBase) return die('no db connection');
	
	return $DataBase;
}

?>