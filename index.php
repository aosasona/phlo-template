<?php

declare(strict_types=1);
require_once __DIR__ . '/vendor/autoload.php';

use Phlo\Config;
use Phlo\Core\{MimeType, Router, Rule, RuleType};


try {
	$loaded_config = Config::load(__DIR__);
	if (!$loaded_config) {
		die('Failed to load config');
	}

	Router::new()
		->addRule(Rule::new("api")->setRuleType(RuleType::API)->setAcceptedMimeTypes([MimeType::JSON, MimeType::FORM_URLENCODED])->setTarget("app/api"))
		->addRule(Rule::new("public")->setRuleType(RuleType::STATIC)->setAcceptedMimeTypes([MimeType::ANY])->setTarget("public"))
		->addRule(Rule::new("sticky")->setRuleType(RuleType::STICKY)->setTarget("app/pages/index.html"))
		->addRule(Rule::new("")->setRuleType(RuleType::STATIC)->setAcceptedMimeTypes([MimeType::ANY])->setTarget("app/pages"))
		->serve();

} catch (Exception $e) {
	// you don't want to show this Exception message in production
	echo $e->getMessage();
}