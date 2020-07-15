<?php

// https://docs.opis.io/closure/3.x/serialize.html

require __DIR__ . "/../../../vendor/autoload.php";


use Opis\Closure\SerializableClosure;

// Recursive factorial closure
$factorial = function ($n) use (&$factorial)
{
	return $n <= 1 ? 1 : $factorial($n - 1) * $n;
};

// Wrap the closure
$wrapper = new SerializableClosure($factorial);
// Now it can be serialized
$serialized = serialize($wrapper);

// Unserialize the closure
$wrapper = unserialize($serialized);

// You can directly invoke the wrapper...
echo $wrapper(5) . PHP_EOL; //> 120

// Or, the recommended way, extract the closure object
$closure = $wrapper->getClosure();

echo $closure(5) . PHP_EOL; //> 120