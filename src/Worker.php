<?php declare(strict_types=1);

namespace Executor;

use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use Phalcon\Cop\Parser;
use Recoil\Recoil;

class Worker
{
	/**
	 * @throws \Throwable
	 */
	public static function mainAsync()
	{
		//
		set_error_handler(function ($errno, $errstr, $errfile, $errline)
			{
				throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
			});
		date_default_timezone_set('Asia/Seoul');

		//
		global $argv;
		$cliParser = new Parser();
		$cliParams = $cliParser->parse($argv);
		$workerId = $cliParams['worker-id'];

		$server = new RedisRemoteExecutorServer(['workerId' => $workerId]);
		yield $server->initializeAsync();
		yield $server->runAsync();
	}
}
