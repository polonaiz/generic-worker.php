<?php

/**
 * This example shows how coroutines can return values to the caller.
 */

declare(strict_types = 1);

require __DIR__ . '/../../../vendor/autoload.php';

use Recoil\ReferenceKernel\ReferenceKernel;

function asyncMultiply($a, $b)
{
	yield; // force PHP to parse this function as a generator
	sleep(1);
	return $a * $b;
	echo 'This code is never reached.';
}

ReferenceKernel::start(function () {
	$result = yield asyncMultiply(2, 3);
	echo '2 * 3 is ' . $result . PHP_EOL;
});
