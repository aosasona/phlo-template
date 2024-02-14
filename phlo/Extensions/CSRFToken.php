<?php

declare(strict_types=1);

namespace Phlo\Extensions;

use Phlo\Extensions\CSRFTokenException;

class CSRFToken
{
  const DEFAULT_FIELD_NAME = "__csrf_token";
  /**
   * @description Generate a CSRF token
   */
  public static function generate(): string
  {
    return bin2hex(random_bytes(32));
  }

  /**
   * @description Set a CSRF token in the session - this function internally calls the generate() method and sets the token in the session. If you want to skip the session and just get the token, use the generate() method
   * @throws CSRFTokenException
   */
  public static function set(string $field_name = self::DEFAULT_FIELD_NAME): string
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      throw new CSRFTokenException("Session is not active");
    }

    if (empty($field_name)) {
      throw new CSRFTokenException("Field name is empty. Hint: leave it empty to use the default value 'csrf_token'");
    }

    $token = $_SESSION[$field_name] = self::generate();

    return $token;
  }

  /**
   * @description Generate a CSRF token and return it as an hidden input field, this is useful for forms
   * @throws CSRFTokenException
   */
  public static function input(string $field_name = self::DEFAULT_FIELD_NAME): string
  {
    if (empty($field_name)) {
      throw new CSRFTokenException("Field name is empty. Hint: leave it empty to use the default value 'csrf_token'");
    }

    $token = self::set($field_name);
    return "<input type=\"hidden\" name=\"{$field_name}\" value=\"{$token}\">";
  }

  /**
   * @description Validate a CSRF token against the one in the session
   * @throws CSRFTokenException
   */
  public static function validate(string $token, $field_name = self::DEFAULT_FIELD_NAME, $cleanup = true): bool
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      throw new CSRFTokenException("Session is not active");
    }

    if (empty($token)) {
      throw new CSRFTokenException("CSRF token is empty");
    }

    if (empty($field_name)) {
      throw new CSRFTokenException("Field name is empty. Hint: leave it empty to use the default value 'csrf_token'");
    }

    if (!isset($_SESSION[$field_name])) {
      return false;
    }

    $is_valid = hash_equals($_SESSION[$field_name], $token);
    if ($cleanup) {
      unset($_SESSION[$field_name]);
    }

    return $is_valid;
  }
}
