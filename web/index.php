<?php
require('../vendor/autoload.php');

header('Content-Type: application/json');

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

function ReadData($limit)
{

	$arrResult = array();

	$collection = getConnectionDB();
	if (!$collection) return;


	return json_encode($arrResult);
}
function insertOne($text)
{
	if(!$text) return 'no incoming data';

	try {
		$jsObject = json_decode($text);
		
	} catch (Exception $e) {
		return 'reply no JSON format';
	}
	if (!$jsObject) return 'reply no JSON format';
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