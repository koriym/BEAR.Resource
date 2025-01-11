<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	// identifier: missingType.generics
	'message' => '#^Method BEAR\\\\Resource\\\\EmbedInterceptor\\:\\:getArgsByInvocation\\(\\) has parameter \\$invocation with generic interface Ray\\\\Aop\\\\MethodInvocation but does not specify its types\\: T$#',
	'count' => 1,
	'path' => __DIR__ . '/src/EmbedInterceptor.php',
];
$ignoreErrors[] = [
	// identifier: missingType.generics
	'message' => '#^Method BEAR\\\\Resource\\\\Interceptor\\\\JsonSchemaInterceptor\\:\\:getNamedArguments\\(\\) has parameter \\$invocation with generic interface Ray\\\\Aop\\\\MethodInvocation but does not specify its types\\: T$#',
	'count' => 1,
	'path' => __DIR__ . '/src/JsonSchema/Interceptor/JsonSchemaInterceptor.php',
];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method BEAR\\\\Resource\\\\OptionsMethods\\:\\:getJsonSchema\\(\\) should return array\\<string, mixed\\> but returns array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/OptionsMethods.php',
];
$ignoreErrors[] = [
	// identifier: argument.type
	'message' => '#^Parameter \\#2 \\$actualJson of method PHPUnit\\\\Framework\\\\Assert\\:\\:assertJsonStringEqualsJsonString\\(\\) expects string, string\\|null given\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/tests/OptionsTest.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
