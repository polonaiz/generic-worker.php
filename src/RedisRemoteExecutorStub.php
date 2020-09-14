<?php


namespace Executor;

use Clue\React\Redis\Factory;
use Opis\Closure\SerializableClosure;
use Recoil\Recoil;

class RedisRemoteExecutorStub
{
	private $config;

	public function __construct($config = [])
	{
		$this->config = $config;
	}

	public function initializeAsync()
	{
		$this->config['redisClient'] ??=
			yield (new Factory($this->config['eventLoop'] ??=
				/** @noinspection PhpUndefinedMethodInspection */ yield Recoil::eventLoop())
			)->createClient($this->config['redisTarget'] ??= 'localhost');
	}

	public function executeTaskAsync(ExecuteClosureTask $task, $option = [])
	{
		$redisClient = $this->config['redisClient'];

		$workerStatuses = [];
		{
			$result = yield $redisClient->hgetall(
				RedisRemoteExecutorScheme::makeWorkerStatusHashsetKey()
			);
			for ($idx = 0; $idx < count($result); $idx+=2)
			{
				$workerStatuses[$result[$idx]] = $result[$idx+1];
			}
		}

		// choose worker
		$workerStatus = $workerStatuses[0];

		// push task
		$returnQueue = \sprintf('task-%s-return', $taskId = \uniqid());
		yield $redisClient->del($returnQueue);
		yield $redisClient->lpush($workerTaskQueue, \json_encode([
			'type' => 'exec_php_closure',
			'closure' => self::serializeClosure($task->closure),
			'parameter' => $task->param,
			'returnQueue' => $returnQueue
		]));

		// wait result
		$popped = null;
		while ($popped === null)
		{
			$popped = yield $redisClient->brpop($returnQueue, $timeout = 1);
		}

		// return result
		return \json_decode($popped[1]);
	}

	private static function serializeClosure($closure)
	{
		$wrapper = new SerializableClosure($closure);
		$wrapper->serialize();
		return \serialize($wrapper);
	}


}