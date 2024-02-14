<?php

use Phlo\Core\Context;


function get(Context $ctx): never
{
	$ctx->send([
		"status" => "ok",
		"message" => "This date is from a middleware: " . $ctx->get("date"),
		"data" => [
			"id" => $ctx->get("id"), // also the same as doing $ctx->getParam("id")
			"type" => "GET"
		]
	]);
}

function post(Context $ctx): never
{
	$ctx->send([
		"status" => "ok",
		"message" => "This date is from a middleware: " . $ctx->get("date"),
		"data" => [
			"id" => $ctx->get("id"), // also the same as doing $ctx->getParam("id")
			"type" => "POST",
			"body" => $ctx->body
		]
	]);
}

function any(Context $ctx): never
{
	$ctx->send([
		"status" => "ok",
		"message" => "This date is from a middleware: " . $ctx->get("date"),
		"data" => [
			"id" => $ctx->get("id"), // also the same as doing $ctx->getParam("id")
			"type" => "ANY",
			"body" => $ctx->body
		]
	]);
}

