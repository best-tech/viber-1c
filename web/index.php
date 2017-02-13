<?php
//require('../vendor/autoload.php');

header('Content-Type: application/json');

$REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];

$arrayParam ='';

if ($REQUEST_METHOD=='POST')
{
	$text = implode("", file('php://input'));
}
else
{
	$arrayParam = http_build_query($_GET['q']);
    // if (isset($_GET['q'])) 
	// 	$queryAddr = '?q='.$_GET['q'];
}

if ($arrayParam) $arrayParam = '?'.$arrayParam;




//return;
	// отправляем запрос
	$responce = file_get_contents('http://4098.ru/viber-1c'.$arrayParam, 
		false, 
		stream_context_create(
			array(
				'http' => array(
					'method' => $REQUEST_METHOD,
					'header' => 'Content-Type: application/json; ',
					'content' => $text
				)
			)
		)
	);
    
    var_export($responce);

?>