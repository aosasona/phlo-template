<?php

declare(strict_types=1);

namespace Phlo\Core;

use Exception;
use Phlo\Config;


class Context
{
	// user defined
	private int $response_code = 200;
	private string $response_content_type = "application/json";
	// usually for JSON, XML etc responses
	public array $ctx_data = []; // for user defined data


	// internally generated
	public string $uri;
	public string $method;
	public string $prefix;
	public array $body;
	public array $query;
	public array $params;
	public array $files;
	public array $cookies;
	public array $headers;
	public array $session;
	public array $path_parts;


	public function __construct()
	{
		try {
			$this->parse();
		} catch (Exception) {
			http_response_code(500);
			exit;
		}
	}

	private function parse(): void
	{
		$this->uri = $_SERVER['REQUEST_URI'] ?? '/';
		$this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		$this->prefix = $this->extractPrefix();
		$this->path_parts = $this->extractPathParts();
		$this->body = $this->parseBody();
		$this->query = self::sanitizeRecursively($_GET ?? []);
		$this->headers = $this->parseHeaders();
		$this->files = $_FILES;
		$this->cookies = self::sanitizeRecursively($_COOKIE ?? []);
		$this->session = self::sanitizeRecursively($_SESSION ?? []);
	}

	public function getEnv(string $key, $default): string
	{
		return Config::get($key, $default);
	}

	public function setParams(array $params = []): self
	{
		$this->params = self::sanitizeRecursively($params);
		return $this;
	}

	public function getParams(): array
	{
		return $this->params;
	}

	public function getParam(string $key, $default = ""): string
	{
		return $this->params[$key] ?? $default;
	}

	private function extractPathParts(): array
	{
		$path = parse_url(htmlspecialchars($_SERVER['REQUEST_URI']), PHP_URL_PATH) ?? '';
		$path_parts = explode('/', $path) ?? [""];
		return array_values(array_filter($path_parts, function ($v) {
			return !!$v;
		}));
	}

	private function extractPrefix(): string
	{
		$path_parts = $this->extractPathParts();
		$prefix = $path_parts[0] ?? '';
		if (str_contains($prefix, '.')) {
			$prefix = '';
		}
		return $prefix;
	}

	private function parseBody(): array
	{
		$json_input = file_get_contents("php://input");
		$body = json_decode($json_input, true) ?? [];
		$body = array_merge($body, $_POST ?? []);
		return self::sanitizeRecursively($body);
	}

	private function parseHeaders(): array
	{
		$headers = (getallheaders() ?? []);
		$headers = array_merge($headers, $_SERVER ?? []);
		foreach ($headers as $key => $value) {
			$header = strtolower(str_replace('HTTP_', '', $key));
			$header = str_replace('_', '-', $header);
			unset($headers[$key]);
			$headers[$header] = $value;
		}
		return self::sanitizeRecursively($headers);
	}

	private static function sanitizeRecursively(array $data): array
	{
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$data[$key] = self::sanitizeRecursively($value);
			} else {
				$data[$key] = gettype($value) === 'string' ? htmlspecialchars($value) : $value;
			}
		}
		return $data;
	}



	// user realm

	/**
	 * @param  string  $key  eg. "user.id" will set ["user"]["id"]
	 * @param  mixed   $value
	 *
	 * @return Context
	 */
	public function set(string $key, mixed $value): Context
	{
		$keys = explode('.', $key);
		$accessor = &$this->ctx_data;
		foreach ($keys as $k) {
			$accessor = &$accessor[$k];
		}
		$accessor = $value;

		return $this;
	}

	public function get(string $key): mixed
	{
		$keys = explode('.', $key);
		$accessor = &$this->ctx_data;
		foreach ($keys as $k) {
			$accessor = &$accessor[$k];
		}
		return $accessor ?? $this->getParam($key);
	}

	public function status(int $code): Context
	{
		$this->response_code = $code;
		return $this;
	}

	public function headers(array $headers): Context
	{
		foreach ($headers as $key => $value) {
			header("{$key}: {$value}");
		}
		return $this;
	}

	public function header(string $key, string $value): Context
	{
		header("{$key}: {$value}");
		return $this;
	}

	public function bodyOr(string $key, string $default = ""): string
	{
		return $this->body[$key] ?? $default;
	}

	public function contentType(string $mime_type = "application/json"): Context
	{
		$this->response_content_type = $mime_type;
		return $this;
	}

	/**
	 * @description Send a JSON response to the client
	 */
	public function json(array $data): never
	{
		$this->contentType("application/json")->send($data);
	}

	/**
	 * @description Send a response to the client, optionally with a different MIME type. If the data is an array, it will be converted to JSON and the MIME type will be set to "application/json"
	 */
	public function send(string | array $data, $override_mime_type = null): never
	{
		$mime_type = match (true) {
			!empty($override_mime_type) => $override_mime_type,
			is_string($data) => "text/html",
			default => "application/json",
		};

		if ($this->response_content_type) {
			$mime_type = $this->response_content_type;
		}

		if (is_array($data) || is_object($data)) {
			$data = json_encode($data);
		}

		$response_data = $data;
		http_response_code($this->response_code);
		header("Content-Type: {$mime_type}");
		echo $response_data;
		exit;
	}

	/**
	 * @throws Exception
	 */
	public function sendFile(string $path): never
	{
		if (!file_exists($path)) {
			throw new Exception("File not found");
		}

		$mime_type = Runner::getMimeTypeFromPath($path);
		if ($this->response_content_type) {
			$mime_type = $this->response_content_type;
		}

		http_response_code($this->response_code);
		header("Content-Type: {$mime_type}");
		readfile($path);

		exit;
	}

	/**
	 * @description Redirect to a different page or URL, optionally with a 301 or 302 status code (permanent or temporary) - default is 301
	 */
	public function redirect(string $path, $permanent = true): never
	{
		header("Location: {$path}", true, $permanent ? 301 : 302);
		exit;
	}
}
