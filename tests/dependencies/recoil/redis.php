<?php declare(strict_types=1);

require __DIR__ . '/../../../vendor/autoload.php';

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
		$factory = new \Clue\React\Redis\Factory(yield Recoil::eventLoop());
		$client = yield $factory->createClient('localhost');

		// parallel execution
		yield Recoil::all([
			function () use ($client, $adder)
				{
					// client side - submit task
					yield $client->set('task1', $task = \json_encode(['closure' => serializeClosure($adder), 'param' => [1, 2]]));

					// worker side - execute task
					$task = \json_decode(yield $client->get('task1'), true);
					$result = \call_user_func_array(\unserialize($task['closure']), $task['param']);
					yield $client->set('task1_result', \json_encode($result));

					// client side - get task result
					$result = yield $client->get('task1_result');
					echo 'adder result: ' . $result . PHP_EOL;
				},
			function () use ($client, $subtractor)
				{
					// client side - submit task
					yield $client->set('task2', $task = \json_encode(['closure' => serializeClosure($subtractor), 'param' => [3, 1]]));

					// worker side - execute task
					$task = \json_decode(yield $client->get('task2'), true);
					$result = \call_user_func_array(\unserialize($task['closure']), $task['param']);
					yield $client->set('task2_result', \json_encode($result));

					// client side - get task result
					$result = yield $client->get('task2_result');
					echo 'subtractor result: ' . $result . PHP_EOL;
				}
		]);
	});
