<?php


namespace Executor;


class ExecuteClosureTask
{
	public $closure;
	public $parameter;

	public function __construct($config = [])
	{
		$this->closure = $config['closure'];
		$this->parameter = $config['parameter'];
	}
}
