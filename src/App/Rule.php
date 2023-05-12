<?php

namespace Wytespace\Phlo\App;


enum MimeType: string
{
  case JSON = 'application/json';
  case STATIC = 'static';

  public function getMimeType(string $filename): string
  {
    return match ($this) {
      MimeType::JSON => "application/json",
      MimeType::STATIC => mime_content_type($filename) ?: "text/html"
    };
  }
}

enum RuleType: string
{
  case REDIRECT = 'redirect'; // this will simply redirect to the provided target
  case API = 'api'; // this will serve files and their method functions (get, post, all) as JSON REST endpoints 
  case STATIC = 'static'; // this will serve exactly what is requested
}

class Rule
{
  public string $name;
  private array $accepted_mime_types;
  private RuleType $rule_type;
  private string $target;

  public function __construct(string $name)
  {
    $this->name = $name;
  }

  /*
    * @param array<MimeType> $allowed_types 
    */
  public function setAcceptedMimeType(array $allowed_types = ["application/json"]): Rule
  {
    $this->accepted_mime_types = $allowed_types;
    return $this;
  }

  public function setRuleType(RuleType $rule_type): Rule
  {
    $this->rule_type = $rule_type;
    return $this;
  }

  public function setTarget(string $target): Rule
  {
    $this->target = $target;
    return $this;
  }
}
