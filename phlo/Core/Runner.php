<?php

declare(strict_types=1);

namespace Phlo\Core;

class Runner {
	private readonly string $ROOT_DIR;
	private Context $ctx;
	private Rule $rule;

	public function __construct(Context &$ctx, Rule &$rule) {
		$this->ctx = $ctx;
		$this->rule = $rule;
		$this->ROOT_DIR = dirname(__DIR__, 2);
	}

	public function run(): void {
		match ($this->rule->rule_type) {
			RuleType::API => $this->serveApi(),
			RuleType::STATIC => $this->serveStatic(),
			RuleType::REDIRECT => $this->serveRedirect(),
			RuleType::STICKY => $this->serveSticky(),
		};
	}

	private function serveApi(): void {
		$accepted_mime_types = $this->getMimeTypesAsString();
		$this->setCommonHeaders($accepted_mime_types);

		$resources = $this->getRequestResources();
		if (!$resources) {
			header("Content-Type: application/json");
			http_response_code(404);
			if (is_file("{$this->ROOT_DIR}/{$this->rule->target}/404.json")) {
				require_once "{$this->ROOT_DIR}/{$this->rule->target}/404.json";
			} else {
				echo "{\"ok\": false,\"message\": \"Cannot {$this->ctx->method} {$this->ctx->uri}\"}";
			}
			die();
		}

		$this->ctx->setParams($resources['params'] ?? []);

		$this->executeFolderScopedMiddleware($resources['dir'] ?? "");
		require_once "{$resources['dir']}/{$resources['file']}";
		$this->executeFileScopedMiddleware();
		$this->executeAPIMethodHandler();
		die();
	}


	private function serveStatic(): void {
		$accepted_mime_types = $this->getMimeTypesAsString();
		$this->setCommonHeaders($accepted_mime_types);

		$resources = $this->getRequestResources();
		if (!$resources) {
			http_response_code(404);
			$not_found_file = "{$this->ROOT_DIR}/{$this->rule->target}/404.html";
			if (is_file($not_found_file)) {
				require_once $not_found_file;
			}
			die();
		}

		$this->executeFolderScopedMiddleware($resources['dir'] ?? "");
		$file = "{$resources['dir']}/{$resources['file']}";
		$mime_type = self::getMimeTypeFromExtension($file);
		header("Content-Type: {$mime_type}");
		readfile($file);
		die();
	}


	private function serveRedirect(): void {
		http_response_code(301);
		header("Location: " . $this->rule->target);
		die();
	}

	private function serveSticky(): void {
		$accepted_mime_types = $this->getMimeTypesAsString();
		$this->setCommonHeaders($accepted_mime_types);

		$file = "{$this->ROOT_DIR}/{$this->rule->target}";

		if (!is_file($file)) {
			http_response_code(404);
			$not_found_file = "{$this->ROOT_DIR}/{$this->rule->target}/404.html";
			if (is_file($not_found_file)) {
				require_once $not_found_file;
			}
			die();
		}

		$mime_type = self::getMimeTypeFromExtension($file);
		header("Content-Type: {$mime_type}");
		readfile($file);
		die();
	}

	private function getRequestResources(): array | null {
		/*
		 * - check if the folder exists
		 * - check if the folder contains a file with the name of the resource
		 * - if not, check for an index.php in that folder
		 * - if not, check for a file/folder with the format [param].php where `param` is the name of the param the user is trying to access in the url eg. /user/123
		 */


		$resource_dir = "{$this->ROOT_DIR}/{$this->rule->target}";
		$resource_file = null;
		$params = [];

		foreach ($this->ctx->path_parts as $resource) {
			// check if the folder exists
			if (is_dir("{$resource_dir}/{$resource}")) {
				$resource_dir .= "/{$resource}";
				continue;
			}

			// check if the folder contains a file with the name of the resource requested and stop there
			if (is_file("{$resource_dir}/{$resource}.php")) {
				$resource_file = "{$resource}.php";
				break;
			}

			// check for an index.php in that folder
			if (is_file("{$resource_dir}/index.php")) {
				$resource_file = "index.php";
				break;
			}

			// for static resources, check if the file exists
			if (is_file("{$resource_dir}/{$resource}")) {
				$resource_file = $resource;
				break;
			}

			// go through every file and folder in the folder and check if it matches the format [param].php where param could be anything
			$files = scandir($resource_dir);
			foreach ($files as $file) {
				if (str_starts_with($file, "[") && str_ends_with($file, "].php")) {
					$resource_file = $file;
					$key = str_replace(["[", "]"], "", str_replace(".php", "", $file));
					$params[$key] = $resource;

					// make sure this is the last iteration before breaking
					if ($resource === end($this->ctx->path_parts)) {
						break;
					}
					continue;
				}
				if (str_starts_with($file, "[") && str_ends_with($file, "]")) {
					$resource_dir .= "/{$file}";
					$key = str_replace(["[", "]"], "", $file);
					$params[$key] = $resource;

					// make sure this is the last iteration before breaking to prevent running an handler that doesn't match the request
					if ($resource === end($this->ctx->path_parts)) {
						break;
					}
				}
			}
		}

		// for static resources, if the path is "" (empty string), check if index.html exists
		if (count($this->ctx->path_parts) == 0 && is_file("{$resource_dir}/index.html")) {
			$resource_file = "index.html";
		}

		if (!$resource_file) {
			return null;
		}

		return [
			"dir" => $resource_dir,
			"file" => $resource_file,
			"params" => $params,
		];
	}

	private function getMimeTypesAsString(): array {
		$accepted_mime_types = array_map(fn($mime_type) => $mime_type->value, $this->rule->accepted_mime_types ?? [MimeType::JSON]);
		return array_unique($accepted_mime_types);
	}

	private function setCommonHeaders(array $accepted_mime_types): void {
		header_remove("X-Powered-By");
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Methods: GET, POST");
		header("Access-Control-Allow-Headers: *");
		header("Accept: " . implode(",", $accepted_mime_types));
	}

	private function validateRequestMimeType(array $accepted_mime_types): void {
		// fix this later
		$mime_type = $this->ctx->headers['content-type'] ?? "";
		if ((!in_array($mime_type, $accepted_mime_types) && !in_array(MimeType::ANY->value, $accepted_mime_types))) {
			http_response_code(415);
			die();
		}
	}


	private function executeFolderScopedMiddleware(string $target_folder): void {
		$middleware_file = "{$target_folder}/_middleware.php";
		if (is_file($middleware_file)) {
			require_once $middleware_file;
			if (function_exists("_global_init")) {
				_global_init($this->ctx);
			}
		}
	}

	private function executeFileScopedMiddleware(): void {
		if (!function_exists("_init")) {
			return;
		}
		_init($this->ctx);
	}

	private function executeAPIMethodHandler(): void {
		define("GET", "get");
		define("POST", "post");
		define("PUT", "put");
		define("DELETE", "delete");
		define("PATCH", "patch");

		$method = match ($_SERVER['REQUEST_METHOD']) {
			"POST" => POST,
			"GET" => GET,
			"PUT" => PUT,
			"DELETE" => DELETE,
			"PATCH" => PATCH,
			default => null,
		};

		if (!$method) {
			$this->ctx->status(405)->send([
				"ok" => false,
				"message" => "method not allowed",
				"code" => 405,
			]);
		}

		if (function_exists($method)) {
			$method($this->ctx);
			return;
		}

		if (function_exists("any")) {
			any($this->ctx);
			return;
		}


		$this->ctx->status(405)->send([
			"ok" => false,
			"message" => "method not allowed",
			"code" => 405,
		]);
	}

	public static function getMimeTypeFromExtension(string $filename): string {
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		return match ($ext) {
			"html" => "text/html",
			"css" => "text/css",
			"js" => "text/javascript",
			"json" => "application/json",
			"png" => "image/png",
			"jpg", "jpeg" => "image/jpeg",
			"gif" => "image/gif",
			"svg" => "image/svg+xml",
			"ico" => "image/x-icon",
			"mp4" => "video/mp4",
			"mp3" => "audio/mpeg",
			"wav" => "audio/wav",
			"pdf" => "application/pdf",
			"zip" => "application/zip",
			"txt" => "text/plain",
			default => "application/octet-stream",
		};
	}
}