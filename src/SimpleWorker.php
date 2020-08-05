<?php

namespace Executor;

use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use Phalcon\Cop\Parser;
use Recoil\Recoil;

class SimpleWorker
{
	public static function mainAsync()
	{
		echo 'started' . PHP_EOL;

		//
		global $argv;
		$cliParser = new Parser();
		$cliParams = $cliParser->parse($argv);
		$workerId = $cliParams[0];

		//
		$taskQueueKey = "task-queue-{$workerId}";
		$controlQueueKey = "control-queue-{$workerId}";
		$statusKey = "status-{$workerId}";

		//
		$redisFactory = new Factory(yield Recoil::eventLoop());
		/** @var Client $redisClient */
		$redisClient = yield $redisFactory->createClient('localhost');

		//
		$taskQueueRegistryKey = 'task-queues';
		$controlQueueRegistryKey = 'control-queues';
		yield $redisClient->sadd($taskQueueRegistryKey, $taskQueueKey);
		yield $redisClient->sadd($controlQueueRegistryKey, $controlQueueKey);

		//
		while (true)
		{
			$popped = yield $redisClient->brPop($controlQueueKey, $taskQueueKey, 60);
			echo "popped: " . \json_encode($popped) . PHP_EOL;
			if ($popped === null)
				continue;

			[$queue, $serTask] = $popped;
			$task = \json_decode($serTask, true);
			$result = \call_user_func_array(\unserialize($task['closure']), $task['param']);
			echo "result: " . \json_encode($result) . PHP_EOL;
			echo "return: " . $task['return'] . PHP_EOL;

			yield $redisClient->lpush($task['return'], \json_encode($result));
		}
	}
}


