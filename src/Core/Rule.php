<?php

namespace Wytespace\Phlo\Core;

class Rule {
	private string $prefix;
	private array $accepted_mime_types = [MimeType::JSON];
	private RuleType $rule_type;
	private string $target;

	public function __get(string $name) {
		return $this->$name;
	}

	/**
	 * @param  string  $prefix  The prefix of the URL to match against
	 */
	public function __construct(string $prefix) {
		$this->prefix = $prefix;
	}

	public static function new(string $name): Rule {
		return new Rule($name);
	}

	public function serve(Context &$ctx): void {
		if ($this->rule_type === RuleType::API) {
			$this->serveApi($ctx);
		} elseif ($this->rule_type === RuleType::STATIC) {
			$this->serveStatic($ctx);
		}
	}

	public function serveApi(Context &$ctx): void {}

	public function serveStatic(Context &$ctx): void {}

	/**
	 * @param  array<MimeType>  $allowed_types
	 */
	public function setAcceptedMimeType(array $allowed_types = [MimeType::JSON]): Rule {
		$this->accepted_mime_types = $allowed_types;
		return $this;
	}

	public function setRuleType(RuleType $rule_type): Rule {
		$this->rule_type = $rule_type;
		return $this;
	}

	public function setTarget(string $target): Rule {
		$this->target = $target;
		return $this;
	}
}
