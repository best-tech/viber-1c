<?php
require('../vendor/autoload.php');

header('Content-Type: application/json');

ini_set('display_errors',1);
ini_set("error_reporting", E_ALL);

$REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];

$limit = 100;

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

		$text = ReadData($limit);		

	}

}

	echo $text;

function ReadData($limit=10)
{

	$arrResult = array();

	$collection = getConnectionDB();
	if (!$collection) return;

	$cursor = $collection->find([], ['limit' => $limit, 'sort' => ['time' => 1]]);

	foreach ($cursor as $document) {
		
		if (!isset($cursor->content)) continue;

		$arrResult[] = json_decode($cursor->content);

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

?>