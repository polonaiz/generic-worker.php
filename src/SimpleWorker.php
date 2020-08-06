<?php declare(strict_types=1);

namespace Executor;

use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use Phalcon\Cop\Parser;
use Recoil\Recoil;

class SimpleWorker
{
	/**
	 * @throws \Throwable
	 */
	public static function mainAsync()
	{
		log(['type' => 'started']);

		//
		set_error_handler(function ($errno, $errstr, $errfile, $errline)
			{
				throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
			});

		// shared data
		$status = [];

		// bootstrap
		global $argv;
		$cliParser = new Parser();
		$cliParams = $cliParser->parse($argv);
		$workerId = $cliParams[0] ?? \uniqid();
		echo "workerId={$workerId}" . PHP_EOL;

		//
		$taskQueueKey = "task-queue-{$workerId}";
		$controlQueueKey = "control-queue-{$workerId}";
		$statusKey = "status-{$workerId}";

		//
		$redisFactory = new Factory(yield Recoil::eventLoop());
		/** @var Client $redisClient */
		$redisHost = 'localhost';
		$redisClient = yield $redisFactory->createClient($redisHost);

		yield $redisClient->sadd('worker_id_set', $workerId);
		$status =
			[
				'workerId' => $workerId,
				'workerHost' => \gethostname(),
				'workerPid' => \getmypid(),
				'workerRuntimeConf' => null,
				'workerStart' => (new \DateTime())->format('Y-m-d H:i:s.u'),
				'workerControlQueue' => "$controlQueueKey",
				'workerTaskQueue' => "$taskQueueKey",
			];
		yield $redisClient->set(
			$workerStatusKey = "worker_status_$workerId",
			\json_encode($status)
		);


		// execution loops
		yield Recoil::all([
			self::loopUpdateStatusAsync($status),
			self::loopExecuteTaskAsync($status, $redisClient, $controlQueueKey, $taskQueueKey),
		]);
	}

	private static function loopUpdateStatusAsync(
		&$status
	)
	{
		while (true)
		{
			//
			$workerStatus['statusUpdate'] = (new \DateTime())->format('Y-m-d H:i:s.u');

			//
			log([
				'type' => 'updateStatusAsync',
				'status' => $status,
			]);

			//
			yield Recoil::sleep(3);
		}
	}

	private static function loopExecuteTaskAsync(
		&$status,
		$redisClient, $controlRequestQueue, $taskRequestQueue
	)
	{
		while (true)
		{
			//
			$popped = yield $redisClient->brPop($controlRequestQueue, $taskRequestQueue, $maxWaitSec = 5);
			log(['type' => 'popped', 'task' => $popped]);
			if ($popped === null)
				continue;

			//
			[$queue, $serRequest] = $popped;
			$request = \json_decode($serRequest, true);
			$requestType = $request['type'];
			if ($requestType === 'exec_php_closure')
			{
				$closure = $request['closure'];
				$param = $request['param'];
				$result = \call_user_func_array(\unserialize($closure), $param);
				log(['type' => 'execPhpClosure', 'result' => $request]);

				//
				yield $redisClient->lpush($request['returnQueue'], \json_encode($result));
			}
			else
			{
				log(['type' => 'unsupported request type', 'requestType' => $requestType]);

				//
				$result = null;
				try
				{
					throw new \Exception("unsupported request type");
				}
				catch (\Throwable $t)
				{
					$result = \serialize($t);
				}

				//
				yield $redisClient->lpush($request['returnQueue'], \json_encode($result));
			}
		}
	}
}

function log(array $data)
{
	echo \json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;

}