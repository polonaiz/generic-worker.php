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

class Executor
{
	private $config;
	private $redisClient;
	private $returnQueues;

	public static function createAsync($config = [])
	{
		$factory = new Factory(yield Recoil::eventLoop());
		$redisClient = yield $factory->createClient('localhost');
		$config += ['redisClient' => $redisClient];

		return new Executor($config);
	}

	public function __construct($config = [])
	{
		$this->config = $config;
	}

	public function submitAsync(Closure $closure, array $param)
	{
		$redisClient = $this->config['redisClient'];

		// discover worker
		$taskQueues = yield $redisClient->smembers('task-queues');
		$taskQueue = $taskQueues[0];

		$returnQueue = sprintf('task-%s-return', $taskId = \uniqid());
		yield $redisClient->del($returnQueue);
		yield $redisClient->lpush($taskQueue, \json_encode([
			'closure' => serializeClosure($closure),
			'param' => $param,
			'return' => $returnQueue
		]));
		$this->returnQueues[] = $returnQueue;

		return $taskId;
	}

	public function waitFirstAsync()
	{
		
	}
}

ReactKernel::start(function () use ($adder, $subtractor)
	{
		$executor = yield Executor::createAsync();

		yield Recoil::all([
			$executor->submitAsync($adder, [1,2])
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
