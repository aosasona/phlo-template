<?php

namespace Wytespace\Phlo\App;


class Context
{
  public function body(): array
  {
    $json_input = file_get_contents("php://input");
    $body = array_merge($_POST, !!$json_input ? json_decode($json_input, true) : []);
    $body = array_map(
      function ($k, $v) {
        return [htmlspecialchars(trim($k)) => htmlspecialchars($v)];
      },
      $body
    );

    return $body;
  }

  public function query(): array
  {
    return array_map(
      function ($k, $v) {
        return [htmlspecialchars(trim($k)) => htmlspecialchars($v)];
      },
      $_GET
    );
  }
}
