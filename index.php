<?php

declare(strict_types=1);
require_once __DIR__ . '/vendor/autoload.php';

use Wytespace\Phlo\Config;
use Wytespace\Phlo\Core\{Router, Rule, RuleType};


$loaded_config = Config::load(__DIR__);
if (!$loaded_config) {
	die('Failed to load config');
}
$router = new Router();

$api_rule = Rule::new("api")->setRuleType(RuleType::API)->setTarget("api");
$www_rule = Rule::new("")->setRuleType(RuleType::STATIC)->setTarget("www");
$public_rule = Rule::new("public")->setRuleType(RuleType::STATIC)->setTarget("public");


$router->addRule($api_rule)->addRule($www_rule)->addRule($public_rule);
$router->serve();
