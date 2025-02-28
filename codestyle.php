<?php

declare(strict_types=1);

use blumilk\Codestyle\Config;
use blumilk\Codestyle\Configuration\Defaults\LaravelPaths;

$paths = new LaravelPaths();
$config = new Config(
    paths: $paths->add("codestyle.php"),
);

return $config->config();
