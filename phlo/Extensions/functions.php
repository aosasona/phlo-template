<?php

declare(strict_types=1);

namespace Phlo\Extensions;

use Phlo\Extensions\CSRFTokenException;

// This file contains a bunch of helper functions that are not part of the main Phlo API, but are useful for developers to use in their applications, mostly in views

/**
 * @description Alias for the input() method, except this one echoes the input field directly - a more convenient way to use the input() method in the CSRFToken class
 * @throws CSRFTokenException
 *
 * ## Example
 * ```php
 * use function Phlo\Extensions\csrf_token;
 * ```
 * ```html
 * <form method="post">
 * csrf_token();
 * <input type="text" name="username">
 * </form>
 * ```
 */
function csrf_token(string $field_name = CSRFToken::DEFAULT_FIELD_NAME): void
{
  echo CSRFToken::input($field_name);
}
