<?php

use parallel\Runtime;

$runtime1 = new Runtime(); // represented os thread
$runtime2 = new Runtime(); // os thread

$future = $runtime1->run(function(){
	for ($i = 0; $i < 500; $i++)
		echo "*";

	return "easy";
});
$future2 = $runtime2->run(function(){
	for ($i = 0; $i < 500; $i++)
		echo "+";

	return "easy";
});

for ($i = 0; $i < 500; $i++) {
	echo ".";
}

printf("\nUsing \\parallel\\Runtime is %s\n", $future->value());
printf("\nUsing \\parallel\\Runtime is %s\n", $future2->value());
