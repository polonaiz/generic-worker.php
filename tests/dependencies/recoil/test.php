<?php declare(strict_types=1);

require __DIR__ . '/../../../vendor/autoload.php';

use Recoil\ReferenceKernel\ReferenceKernel;
use Recoil\Recoil;

function delayedEchoAsync(string $message, float $delaySec)
{
	yield Recoil::sleep($delaySec);
	echo "delayedEcho: {$message}, {$delaySec}" . PHP_EOL;
}

ReferenceKernel::start(function ()
	{
		$coroutines =
			[
				function ()
					{
						yield delayedEchoAsync('nested', 1);
						yield delayedEchoAsync('nested2', 1);
					},
				delayedEchoAsync('top-tier', 2),
				delayedEchoAsync('top-tier', 3),
				delayedEchoAsync('top-tier', 4),
				delayedEchoAsync('top-tier', 5),
				delayedEchoAsync('top-tier', 6)
			];

		yield $coroutines;
	});
