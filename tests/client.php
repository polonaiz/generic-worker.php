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

		$results = yield Recoil::all
		(
			$executor->executeTaskAsync(new ExecuteClosureTask([
				'closure' => fn(int $a, int $b): int => $a + $b,
				'param' => [1, 2],
				'maxdop' => ['key' => '116d6f27', 'max' => 2]
			])),
			$executor->executeTaskAsync(new ExecuteClosureTask([
				'closure' => fn(int $a, int $b): int => $a - $b,
				'param' => [100, 5],
				'maxdop' => ['key' => '116d6f27', 'max' => 2]
			]))
		);
		echo \json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
	});

echo \json_encode([
		'programExecutionTime' => microtime(true) - $beginProgram
	]) . PHP_EOL;


