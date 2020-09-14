<?php


namespace Executor;

use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use Recoil\Recoil;

class RedisRemoteExecutorServer
{
	private array $config;
	private Client $redisClient;
	private array $status;

	public function __construct($config = [])
	{
		$this->config = $config;
	}

	/**
	 * @return \Generator
	 */
	public function initializeAsync()
	{
		$this->redisClient = $this->config['redisClient'] ??
			yield (new Factory($this->config['eventLoop'] ??=
				/** @noinspection PhpUndefinedMethodInspection */ yield Recoil::eventLoop())
			)->createClient($this->config['redisTarget'] ??= 'localhost');

		$now = new \DateTime();
		$this->status =
			[
				'workerId' =>
					$this->config['workerId'],
				'workerControlRequestQueue' =>
					RedisRemoteExecutorScheme::makeControlRequestQueueKey($this->config['workerId']),
				'workerTaskRequestQueue' =>
					RedisRemoteExecutorScheme::makeTaskRequestQueueKey($this->config['workerId']),
				'workerStartTime' =>
					$now->getTimestamp(),
				'workerStartTimeHuman' =>
					$now->format('Y-m-d H:i:s.u T'),
				'workerStatusUpdateTime' =>
					$now->getTimestamp(),
				'workerStatusUpdateTimeHuman' =>
					$now->format('Y-m-d H:i:s.u T'),
			];
	}

	/**
	 * @return \Generator
	 * @throws \Throwable
	 */
	public function runAsync()
	{
		yield Recoil::all([
			$this->loopHandleRequestAsync(),
		]);
	}

	private function loopHandleRequestAsync()
	{
		while (true)
		{
			//
			$popped = yield $this->redisClient->brPop(
				$this->status['workerControlRequestQueue'],
				$this->status['workerTaskRequestQueue'],
				$maxWaitSec = 1
			);

			$now = new \DateTime();
			$this->status['workerStatusUpdateTime'] = $now->getTimestamp();
			$this->status['workerStatusUpdateTimeHuman'] = $now->format('Y-m-d H:i:s.u T');
			yield $this->redisClient->hset(
				RedisRemoteExecutorScheme::makeWorkerStatusHashsetKey(),
				$this->status['workerId'],
				\json_encode($this->status)
			);

			if ($popped === null) // timed out
			{
				continue;
			}

			//
			[$queue, $serializedRequest] = $popped;
			$request = \json_decode($serializedRequest, true);
			$responseQueue = $request['responseQueue'];

			try
			{
				$requestType = $request['type'];
				switch ($requestType)
				{
					case 'executeClosure':
						//
						$closure = $request['closure'];
						$parameter = $request['parameter'];
						//
						$return = \call_user_func_array(\unserialize($closure), $parameter);
						//
						yield $this->redisClient->lpush($responseQueue, \json_encode([
							'return' => $return
						]));
						break;

					default:
						throw new \Exception("unsupported request type");
				}
			}
			catch (\Throwable $t)
			{
				$exception = \serialize($t);
				yield $this->redisClient->lpush($responseQueue, \json_encode([
					'exception' => $exception
				]));
			}
		}
	}
}