<?php
declare(strict_types=1);

namespace Executor;

require __DIR__ . '/../vendor/autoload.php';

use Recoil\React\ReactKernel;
use Recoil\Recoil;

$beginProgram = \microtime(true);

/** @noinspection PhpUnhandledExceptionInspection */
ReactKernel::start(function ()
	{
		$executor = new RedisRemoteExecutorStub();
		yield $executor->initializeAsync();

		$beginTask = \microtime(true);
		$results = yield Recoil::all
		(
			$executor->executeTaskAsync(new ExecuteClosureTask([
				'closure' => fn(int $a, int $b): int => $a + $b,
				'parameter' => [1, 2],
			])),
			$executor->executeTaskAsync(new ExecuteClosureTask([
				'closure' => fn(int $a, int $b): int => $a - $b,
				'parameter' => [100, 5],
			])),
		);

		$beginTask = \microtime(true);
		$response = yield $executor->executeTaskAsync(new ExecuteClosureTask([
			'closure' => function () {
				\sleep(3);
				return 100;
			},
		]));
		echo \json_encode($response, JSON_PRETTY_PRINT) . PHP_EOL;
		echo \json_encode([
				'taskExecutionTime' => microtime(true) - $beginTask
			]) . PHP_EOL;

	});

echo \json_encode([
		'programExecutionTime' => microtime(true) - $beginProgram
	]) . PHP_EOL;


