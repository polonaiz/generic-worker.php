<?php


namespace Executor;


class ExecuteClosureTask
{
	public $type;
	public $closure;
	public $param;

	public function __construct($config = [])
	{
		$this->type = 'exec_php_closure';
		$this->closure = $config['closure'];
		$this->param = $config['param'];
	}
}
