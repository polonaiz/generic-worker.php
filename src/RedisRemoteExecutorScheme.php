<?php


namespace Executor;


class RedisRemoteExecutorScheme
{
	public static function makeWorkerStatusHashsetKey()
	{
		return "worker-status-hashset";
	}

	public static function makeControlRequestQueueKey($workerId)
	{
		return "worker-control-request-queue/{$workerId}";
	}

	public static function makeTaskRequestQueueKey($workerId)
	{
		return "worker-task-request-queue/{$workerId}";
	}
}