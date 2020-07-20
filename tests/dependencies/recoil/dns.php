<?php

require __DIR__ . '/../../../vendor/autoload.php';

use Recoil\React\ReactKernel;
use Recoil\Recoil;

function resolveDomainName(string $name, React\Dns\Resolver\Resolver $resolver)
{
	try {
		$ip = yield $resolver->resolve($name);
		echo 'Resolved "' . $name . '" to ' . $ip . PHP_EOL;
	} catch (Exception $e) {
		echo 'Failed to resolve "' . $name . '" - ' . $e->getMessage() . PHP_EOL;
	}
}

ReactKernel::start(function () {
	// Create a React DNS resolver ...
	$resolver = (new React\Dns\Resolver\Factory)->create(
		'8.8.8.8',
		yield Recoil::eventLoop()
	);

	// Concurrently resolve three domain names ...
	yield [
		resolveDomainName('recoil.io', $resolver),
		resolveDomainName('php.net', $resolver),
//		resolveDomainName('probably-wont-resolve', $resolver),
	];
});
