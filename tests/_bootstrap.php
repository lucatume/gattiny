<?php
// This is global bootstrap for autoloading

use Codeception\Util\Autoload;

Autoload::addNamespace('gattiny\TestDrivers',__DIR__ . '/_support/Drivers');
