<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Clue\React\Redis\Factory;
use Recoil\React\ReactKernel;
use Recoil\Recoil;
use Opis\Closure\SerializableClosure;

$adder = function ($a, $b)
	{
		return $a + $b;
	};

$subtractor = function ($a, $b)
	{
		return $a - $b;
	};

function serializeClosure($closure)
{
	$wrapper = new SerializableClosure($closure);
	$wrapper->serialize();
	return \serialize($wrapper);
}

ReactKernel::start(function () use ($adder, $subtractor)
	{
		$factory = new Factory(yield Recoil::eventLoop());
		$redisClient = yield $factory->createClient('localhost');

		$returnQueues = [];
		yield Recoil::all([
			function () use ($redisClient, $adder, &$returnQueues)
				{
					$returnQueue = 'task1-return';
					yield $redisClient->del($returnQueue);
					yield $redisClient->lpush('task-queue', \json_encode([
						'closure' => serializeClosure($adder),
						'param' => [1, 2],
						'return' => $returnQueue
					]));
					$returnQueues[] = $returnQueue;
				},
			function () use ($redisClient, $subtractor, &$returnQueues)
				{
					$returnQueue = 'task2-return';
					yield $redisClient->del($returnQueue);
					yield $redisClient->lpush('task-queue', \json_encode([
						'closure' => serializeClosure($subtractor),
						'param' => [3, 1],
						'return' => $returnQueue
					]));
					$returnQueues[] = $returnQueue;
				}
		]);

		yield Recoil::all([
			function () use ($redisClient, &$returnQueues)
				{
					while (count($returnQueues) > 0)
					{
						echo \json_encode($returnQueues) . PHP_EOL;
						$popped = yield $redisClient->brpop(...\array_merge($returnQueues, [3]));
						echo \json_encode($popped) . PHP_EOL;

						$returnQueues = \array_diff($returnQueues, [$popped[0]]);
					}
				}
		]);
	});
