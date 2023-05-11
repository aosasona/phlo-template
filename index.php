<?php

declare(strict_types=1);
require_once __DIR__ . '/vendor/autoload.php';

use Wytespace\Phlo\Config;


$loaded_config = Config::load(__DIR__);
if (!$loaded_config) {
  die('Failed to load config');
}
