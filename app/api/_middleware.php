
<?php

use Phlo\Extensions\CSRFToken;

// Just a simple example of something else you would do in a middleware if you wanted to, for example, start a session, validate a CSRF token, etc.
function _global_init($ctx): void
{
  session_start();

  try {
    $csrf_token = $ctx->bodyOr(CSRFToken::DEFAULT_FIELD_NAME, "");

    if (empty($_SESSION[CSRFToken::DEFAULT_FIELD_NAME])) {
      throw new Exception("CSRF tokens are only valid for one request, you may have already submitted the form and need to refresh the page", 400);
    }

    if (empty($csrf_token)) {
      throw new Exception("CSRF token is empty", 400);
    }

    if (!CSRFToken::validate($csrf_token)) {
      throw new Exception("Invalid CSRF token", 400);
    }

    $ctx->set("csrf_token", $csrf_token);
  } catch (Exception $e) {
    $ctx->set("error", $e->getMessage());
  }
}
