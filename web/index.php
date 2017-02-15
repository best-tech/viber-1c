<?php
require('../vendor/autoload.php');

//header('Content-Type: application/json');

$connectionString =  getenv('MONGODB_URI');

if (!$connectionString) $connectionString ="mongodb://heroku_7jhm3vw7:usl2213vp3pdhlaj3h0a1jo11m@ds153179.mlab.com:53179/heroku_7jhm3vw7";

$arr = array_reverse(explode('/', $connectionString));

$dbName = $arr[0];

if (!$dbName) {echo 'no db name'; return;};

//$connectionString = str_last_replace("/".$dbName,"",$connectionString);

if (!$connectionString) {echo 'no connection string'; return;};

$client = new MongoDB\Client($connectionString);

$DataBase = $client->$dbName;
$collection = $DataBase->incoming;

$post = array(
         'title'     => 'Что такое MongoDB',
         'content'   => 'MongoDB это высокопроизводительная документо-ориентированная база данных...'
      );
	
    $collection->insertOne($post);

return;


$REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];

$arrayParam ='';

if ($REQUEST_METHOD=='POST')
{
	$text = implode("", file('php://input'));

	$responce = file_get_contents('http://4098.ru/viber-1c', 
	false, 
	stream_context_create(
		array(
			'http' => array(
				'method' => 'POST',
				'header' => 'Content-Type: application/json; ',
				'content' => $text
				)
			)
		)
	);
    
    echo $responce;
}
else
{
	$arrayParam = http_build_query($_GET);
    // if (isset($_GET['q'])) 
	// 	$queryAddr = '?q='.$_GET['q'];
	if ($arrayParam) $arrayParam = '?'.$arrayParam;
	$responce = file_get_contents('http://4098.ru/viber-1c'.$arrayParam);

	 echo $responce;
}



function str_last_replace($search, $replace, $subject){
    $pos = strrpos($subject, $search);
    if($pos !== false)    {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
}


//return;
	// отправляем запрос


?>