<?php

namespace Wytespace\Phlo\Core;


class Context {
	private string $prefix;
	private array $body;
	private array $query;
	private array $params;
	private array $files;
	private array $cookies;
	private array $headers;
	private array $session;


	public function __construct() {
		$this->prefix = $this->extractPrefix($_SERVER['REQUEST_URI']);
		$this->body = $this->parseBody();
		$this->query = $this->parseQuery();
	}

	public function __get(string $name) {
		return $this->$name;
	}

	/**
	 * @param  string  $name
	 * @param  mixed   $value
	 *
	 * @return void
	 */
	public function __set(string $name, mixed $value) {
		$this->$name = $value;
	}

	private function extractPrefix(string $uri): string {
		$uri = explode('?', $uri)[0] ?? [""];
		$uri = explode('/', $uri) ?? [""];
		$uri = array_filter($uri, function ($v) {
			return !!$v;
		});
		return $uri[0] ?? '';
	}

	private function parseBody(): array {
		$json_input = file_get_contents("php://input");
		$body = array_merge($_POST, !!$json_input ? json_decode($json_input, true) : []);
		return array_map(
			function ($k, $v) {
				return [htmlspecialchars(trim($k)) => htmlspecialchars($v)];
			},
			$body
		);
	}

	private function parseQuery(): array {
		return array_map(
			function ($k, $v) {
				return [htmlspecialchars(trim($k)) => htmlspecialchars($v)];
			}, $_GET
		);
	}


}
