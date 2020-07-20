<?php

require __DIR__ . "/../../../vendor/autoload.php";

echo '=== begin' . PHP_EOL;

$deferred = new \React\Promise\Deferred();
$deferred->promise()
	->then(function ($data)	{ echo $data . PHP_EOL; return $data . ' world';})
	->then(function ($data) { echo $data . PHP_EOL; });
$deferred->resolve('hello');

echo '=== end' . PHP_EOL;
