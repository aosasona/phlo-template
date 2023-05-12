<?php

namespace Wytespace\Phlo\routes;

use Wytespace\Phlo\Api\Context;

function get(Context $ctx): array
{
  return ["message" => "I am alive :)"];
}
