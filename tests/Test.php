<?php

namespace Executor;

use React\EventLoop\Factory;
use Recoil\React\ReactKernel;
use PHPUnit\Framework\TestCase;
use Recoil\Recoil;

class Test extends TestCase
{
	public function testLoop()
	{
		$output = [];

		$loop = Factory::create();
		$kernel = ReactKernel::create($loop);
		$kernel->execute(function () use (&$output)
		{
			yield; // 1st cycle
			yield; // 2st cycle
			$output[] = 'coroutine1';
			// 3rd cycle
		});
		$kernel->execute(function () use (&$output, $kernel)
		{
			yield; // 1st cycle
			$output[] = 'coroutine2';
			// 2st cycle
		});
		$kernel->run(); // blocked until all coroutine executed

		$this->assertEquals(\trim(<<<JSON
        [
            "coroutine2",
            "coroutine1"
        ]
        JSON
		), \json_encode($output, JSON_PRETTY_PRINT));
		$this->assertTrue(true);
	}

	public function testStrandSuspend()
	{
		$loop = Factory::create();
		$kernel = ReactKernel::create($loop);
		$kernel->execute(function ()
		{
			yield 3; // suspend strand 3 seconds
		});
		$begin = \time();
		$kernel->run();
		$end = \time();
		$this->assertEquals(3, $end - $begin);
	}

	public function testSimpleTask()
	{
		ReactKernel::start(function ()
		{
			//
			$redisTarget = \sprintf("redis://:%s@%s:%n",
				$redisAuthPass = \getenv('REDIS_AUTH_PASS'),
				'localhost',
				6379
			);

			//
			$server = new RedisRemoteExecutorServer([
				'workerId' => 'test-worker',
				'redisTarget' => $redisTarget
			]);
			$remoteExecutor = new RedisRemoteExecutorStub([
				'redisTarget' => $redisTarget
			]);

			yield Recoil::all(
				function () use ($server)
				{
					yield $server->initializeAsync();
					yield $server->runAsync();
					yield;
				},
				function () use ($server, $remoteExecutor)
				{
					yield $remoteExecutor->initializeAsync();
					$response = yield $remoteExecutor->executeTaskAsync(new ExecuteClosureTask([
						'closure' => fn(int $a, int $b): int => $a + $b,
						'parameter' => [1, 2],
					]));
					$this->assertEquals(3, $response['return']);
					$response = yield $remoteExecutor->executeTaskAsync(new ExecuteClosureTask([
						'closure' => fn(int $a, int $b): int => $a * $b,
						'parameter' => [9, 7],
					]));
					$this->assertEquals(63, $response['return']);
					$server->stop();
				}
			);
		});
	}
}
