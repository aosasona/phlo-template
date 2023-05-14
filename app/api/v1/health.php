<?php

use Phlo\Core\Context;


function get(Context &$ctx): void {
	$ctx->send([
		"status" => "ok",
		"message" => "I am alive!",
	]);
}