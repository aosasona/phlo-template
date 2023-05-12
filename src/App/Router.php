<?php

namespace Wytespace\Phlo\App;


class Router
{
  private array $rules;
  public  function __construct()
  {
  }

  public function addRule(Rule $rule): Router
  {
    $this->rules[] = $rule;
    return $this;
  }
}
