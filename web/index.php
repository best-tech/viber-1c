<?php
require('../vendor/autoload.php');

header('Content-Type: application/json');

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






//return;
	// отправляем запрос


?>