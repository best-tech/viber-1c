<?php
//require('../vendor/autoload.php');

header('Content-Type: application/json');

$REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];

if ($REQUEST_METHOD=='POST')
{
$text = implode("", file('php://input'));
}
else
{
    if (isset($_GET['q']))
    $text = $_GET['q'];
    else  $text ='{"no data":"false"}';
}

echo $text;
//echo $text;
// json_decode($text);
//var_export($text);

return;
	// отправляем запрос
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
    
    var_export($responce);

?>