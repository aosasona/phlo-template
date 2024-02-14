<?php

use Phlo\Core\Context;


function post(Context $ctx): void
{
  try {
    if ($ctx->get('error')) {
      throw new Exception($ctx->get('error'), 400);
    }

    $name = $ctx->bodyOr('name', '');

    if (empty($name)) {
      throw new Exception("Name and email are required", 400);
    }

    $csrf_token = $ctx->get('csrf_token');
    $ctx->json(['name' => $name, "csrf_token" => $csrf_token]);
  } catch (Exception $e) {
    $code = $e->getCode() > 0 ? $e->getCode() : 500;
    $ctx->status($code);
    $ctx->json(['error' => $e->getMessage(), 'code' => $code]);
    return;
  }
}
