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

	/**
	 * @param ExecuteClosureTask $task
	 * @param array $option
	 * @return \Generator|mixed
	 * @throws \Exception
	 */
	public function executeTaskAsync(ExecuteClosureTask $task, $option = [])
	{
		$redisClient = $this->config['redisClient'];

		$workerStatuses = [];
		$remainTry = 10;
		while(--$remainTry > 0)
		{
			$result = yield $redisClient->hgetall(
				RedisRemoteExecutorScheme::makeWorkerStatusHashsetKey()
			);
			for ($idx = 0; $idx < count($result); $idx+=2)
			{
				$workerStatus = \json_decode($result[$idx+1],true);
				if($workerStatus['workerStatusUpdateTime'] + 60 < \time())
				{
					// exclude not active worker
					continue;
				}
				$workerStatuses[] = $workerStatus;
			}
			if(count($workerStatuses) > 0)
            {
                break;
            }
            yield 1;
		}
		if(count($workerStatuses) === 0)
		{
			throw new \Exception("worker not available");
		}
		$workerStatus = $workerStatuses[0];

		// push task
		$responseQueue = \sprintf('task-%s-response', $taskId = \uniqid());
		yield $redisClient->del($responseQueue);
		yield $redisClient->lpush($workerStatus['workerTaskRequestQueue'], \json_encode([
			'type' => 'executeClosure',
			'closure' => self::serializeClosure($task->closure),
			'parameter' => $task->parameter,
			'responseQueue' => $responseQueue
		]));

		// wait result
		$popped = null;
		while ($popped === null)
		{
			$popped = yield $redisClient->brpop($responseQueue, $timeout = 1);
		}

		// return result
		return \json_decode($popped[1], true);
	}

	private static function serializeClosure($closure)
	{
		$wrapper = new SerializableClosure($closure);
		$wrapper->serialize();
		return \serialize($wrapper);
	}


}