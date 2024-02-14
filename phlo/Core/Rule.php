<?php

declare(strict_types=1);

namespace Phlo\Core;


class Rule
{

	public string $prefix;
	public array $accepted_mime_types = [MimeType::JSON];
	public RuleType $rule_type;
	public string $target;

	public function __get(string $name)
	{
		return $this->$name;
	}

	/**
	 * @param  string  $prefix  The prefix of the URL to match against
	 */
	public function __construct(string $prefix)
	{
		$this->prefix = $prefix;
	}

	public static function new(string $name): Rule
	{
		return new Rule($name);
	}

	public function serve(Context &$ctx): void
	{
		$runner = new Runner($ctx, $this);
		$runner->run();
	}


	/**
	 * @param  array<MimeType>  $allowed_types
	 */
	public function setAcceptedMimeTypes(array $allowed_types = [MimeType::JSON]): Rule
	{
		$this->accepted_mime_types = $allowed_types;
		return $this;
	}

	public function setRuleType(RuleType $rule_type): Rule
	{
		if ($rule_type === null) {
			throw new \Exception("RuleType cannot be null");
		}

		$this->rule_type = $rule_type;
		return $this;
	}

	public function setTarget(string $target): Rule
	{
		$this->target = $target;
		return $this;
	}
}
