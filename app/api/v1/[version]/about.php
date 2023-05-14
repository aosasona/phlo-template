<?php

use Phlo\Core\Context;


function _init(Context &$ctx): void {
	$ctx->set("app.name", "Phlo")
		->set("app.author", "Ayodeji");
}

function get(Context &$ctx): void {
	$ctx->send([
		"ok" => true,
		"data" => [
			"name" => $ctx->get("app.name"),
			"version" => $ctx->getParam("version"),
			"author" => $ctx->get("app.author"),
			"date" => "This date is from a middleware: " . $ctx->get("date") . " - " . $ctx->get("setter"),
		],
	]);
}
