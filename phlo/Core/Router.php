<?php

declare(strict_types=1);

namespace Phlo\Core;


class Router
{
	/**
	 * @var array<Rule> $rules
	 */
	private array $rules;
	private array $registered_prefixes;

	public function __construct()
	{
	}

	public static function new(): Router
	{
		return new Router();
	}

	public function addRule(Rule $rule): Router
	{
		$this->rules[] = $rule;
		$this->registered_prefixes[] = $rule->prefix;
		return $this;
	}

	private function getRule(Context $ctx): Rule | null
	{
		foreach ($this->rules as $rule) {
			if ($rule->prefix === $ctx->prefix) {
				return $rule;
			}
		}
		return null;
	}

	public function serve(): never
	{
		$context = new Context();
		if (!in_array($context->prefix, $this->registered_prefixes)) {
			$context->prefix = "";
		}
		$rule = $this->getRule($context);
		if (!$rule) {
			http_response_code(404);
			exit;
		}
		$rule->serve($context);
	}
}
