<?php

require __DIR__ . "/../vendor/autoload.php";

//
$childProcessIds = [];
$predis = new \Predis\Client();
$predis->flushall();
unset($predis);

while (count($childProcessIds) < $workerCount = 100)
{
	$pid = \pcntl_fork();
	echo "forked {$pid}" . PHP_EOL;
	if ($pid === -1)
	{
		die("cannot fork");
	}
	elseif ($pid === 0)
	{
		// sub process control
		echo "child: " . getmypid() . ": start" . PHP_EOL;

		// register worker
		$predis = new \Predis\Client();
		$predis->sadd("job-queues", [$jobQueueId = sprintf('jobQueue-%02d', \getmypid())]);
		$predis->sadd("control-queues", [$controlQueueId = sprintf('control-queue-%02d', \getmypid())]);
		while(true)
		{
			$popped = $predis->brpop([$controlQueueId, $jobQueueId], 5);
			if($popped === null)
			{
				continue;
			}
			echo "child: " . getmypid() . ": popped=" . \json_encode($popped) . PHP_EOL;

			if($popped[0] === $controlQueueId)
			{
				if($popped[1] === "stop")
				{
					die;
				}
			}
			elseif($popped[0] == $jobQueueId)
			{
				$job = \json_decode($popped[1], true);
				$sum = $job[0] + $job[1];
				echo "child: " . getmypid() . ": sum=" . $sum . PHP_EOL;
				usleep(rand(1000, 1000000));
				$predis->lpush($job[2], $sum);
			}
			else
			{
				throw new Exception("??");
			}
		}

		// cannot reach here!
	}
	else
	{
		// main process control
		$childProcessIds[] = $pid;
		echo "parent: childProcessId={$pid}" . PHP_EOL;
		echo "parent: pidCount=" . count($childProcessIds) . PHP_EOL;
	}
}

// wait worker start
$predis = new \Predis\Client();
while (true)
{
	$jobQueues = $predis->smembers("job-queues");
	$controlQueues = $predis->smembers("control-queues");
	echo \json_encode([$jobQueues, $controlQueues]) . PHP_EOL;
	if (count($jobQueues) === $workerCount && count($controlQueues) === $workerCount)
	{
		break;
	}
	sleep(1);
}

// submit job
$returnQueues = [];
foreach ($jobQueues as $jobQueue)
{
	$returnQueues[] = $returnQueue = $jobQueue . '-return';
	$predis->lpush($jobQueue, [
		\json_encode([\rand(0, 1000), \rand(0, 1000), $returnQueue])
	]);
}
$returns = [];
while(true)
{
	$returns[] = $return = $predis->brpop($returnQueues, 5);
	echo "parent: " . \json_encode($return) . PHP_EOL;
	if(count($returns) === count($returnQueues))
	{
		break;
	}
}

// cleanup
foreach ($controlQueues as $controlQueue)
{
	$predis->lpush($controlQueue, [
		"stop"
	]);
}
echo \json_encode($jobQueues) . PHP_EOL;
\pcntl_wait($status);
\var_dump($status);