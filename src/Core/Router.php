<?php

namespace Wytespace\Phlo\Core;


class Router {
	/**
	 * @var array<Rule> $rules
	 */
	private array $rules;

	public function __construct() {}

	public function addRule(Rule $rule): Router {
		$this->rules[] = $rule;
		return $this;
	}

	private function getRule(Context $ctx): Rule | null {
		$rule = array_filter($this->rules, function ($rule) use ($ctx) {
			return $rule->prefix === $ctx->prefix;
		});
		return $rule[0] ?? null;
	}

	public function serve(): void {
		$context = new Context();
		$rule = $this->getRule($context);
		if (!$rule) {
			http_response_code(404);
			die();
		}
		$rule->serve($context);
	}
}
