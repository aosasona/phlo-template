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
			$this->handleAPIRuleNotFound();
		}

		$this->ctx->setParams($resources['params'] ?? []);
		$file = "{$resources['dir']}/{$resources['file']}";
		if (!is_file($file)) {
			$this->handleAPIRuleNotFound();
		}

		$this->executeFolderScopedMiddleware($resources['dir'] ?? "");
		require_once $file;
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
				header("Content-Type: text/html; charset=utf-8");
				readfile($not_found_file);
			}
			die();
		}

		$this->executeFolderScopedMiddleware($resources['dir'] ?? "");
		$file = "{$resources['dir']}/{$resources['file']}";
		$mime_type = self::getMimeTypeFromPath($file);
		header("Content-Type: $mime_type");

		// make the ctx available to the file
		$ctx = $this->ctx;
		
		require $file;
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
				header("Content-Type: text/html; charset=utf-8");
				readfile($not_found_file);
			}
			die();
		}

		$mime_type = self::getMimeTypeFromPath($file);
		header("Content-Type: {$mime_type}");
		readfile($file);
		die();
	}

	private function getRequestResources(): array | null {
		$start_time = microtime(true);
		$resource_dir = "{$this->ROOT_DIR}/{$this->rule->target}";
		$resource_file = null;
		$params = [];

		foreach ($this->ctx->path_parts as $idx => $resource) {
			// check if the folder exists
			if (is_dir("{$resource_dir}/{$resource}")) {
				$resource_dir .= "/{$resource}";
				continue;
			}

			// check if the folder contains a PHP file with the name of the resource requested and stop there
			if (is_file("{$resource_dir}/{$resource}.php")) {
				$resource_file = "{$resource}.php";
				break;
			}

			// check if the folder contains an HTML file with the name of the resource requested and stop there
			if (is_file("{$resource_dir}/{$resource}.html")) {
				$resource_file = "{$resource}.html";
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
					if ($idx === count($this->ctx->path_parts) - 1) {
						break;
					}
					continue;
				}
				if (str_starts_with($file, "[") && str_ends_with($file, "]")) {
					$resource_dir .= "/{$file}";
					$key = str_replace(["[", "]"], "", $file);
					$params[$key] = $resource;

					// make sure this is the last iteration before breaking to prevent running an handler that doesn't match the request
					if ($idx === count($this->ctx->path_parts) - 1) {
						break;
					}
				}
			}
		}

		// for static resources, if the path is "" (empty string), check if index.html exists
		if (count($this->ctx->path_parts) == 0 && is_file("{$resource_dir}/index.html")) {
			$resource_file = "index.html";
		}

		// make sure it is an exact match by comparing the number of path parts in the request with the number of path parts in the rule (excluding the route prefix)
		// while these could have been all chained together, they are separated into individual variables for readability
		$rule_root_parts_count = count(explode("/", trim("{$this->ROOT_DIR}/{$this->rule->target}", "/")));
		$abs_resource_file_parts_count = count(explode("/", trim("{$resource_dir}/" . ($resource_file ?? ""), "/")));
		$matched_resource_count = $abs_resource_file_parts_count - $rule_root_parts_count;
		$required_match = count($this->ctx->path_parts) - count(explode("/", $this->rule->prefix ?? ""));
		$is_invalid_match = count($this->ctx->path_parts) !== 0 && $this->rule->rule_type === RuleType::API && $matched_resource_count !== $required_match;

		if (!$resource_file || $is_invalid_match) {
			return null;
		}

		return [
			"dir" => $resource_dir,
			"file" => $resource_file,
			"params" => $params,
			"time_taken" => microtime(true) - $start_time,
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

	public static function getMimeTypeFromPath(string $filepath): string {
		$extension = pathinfo($filepath, PATHINFO_EXTENSION);
		// mime_content_type fails on some systems, so we do a manual lookup first and fallback to mime_content_type
		return match ($extension) {
			"php" => "",
			"js" => "application/javascript",
			"css" => "text/css",
			"html" => "text/html",
			"json" => "application/json",
			"jpg", "jpeg" => "image/jpeg",
			"png" => "image/png",
			"gif" => "image/gif",
			"svg" => "image/svg+xml",
			"ico" => "image/x-icon",
			"txt" => "text/plain",
			"pdf" => "application/pdf",
			"zip" => "application/zip",
			"rar" => "application/x-rar-compressed",
			"tar" => "application/x-tar",
			"gz", "tar.gz" => "application/gzip",
			"mp3" => "audio/mpeg",
			"mp4" => "video/mp4",
			"webm" => "video/webm",
			"ogg" => "audio/ogg",
			"wav" => "audio/wav",
			"webp" => "image/webp",
			"bmp" => "image/bmp",
			"csv" => "text/csv",
			"xml" => "application/xml",
			"xls" => "application/vnd.ms-excel",
			"xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
			"doc" => "application/msword",
			"docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
			"ppt" => "application/vnd.ms-powerpoint",
			"pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation",
			"odt" => "application/vnd.oasis.opendocument.text",
			"rtf" => "application/rtf",
			"7z" => "application/x-7z-compressed",
			"tar.xz" => "application/x-xz",
			default => mime_content_type($filepath)
		};
	}

	private function showDebugInfo() {
		/**
		 * @var int    $matched_resource_count
		 * @var int    $required_match
		 * @var string $resource_dir
		 * @var string $resource_file
		 * @var string $rule_prefix
		 * @var string $absolute_path_with_rule_prefix
		 * @var array  $path_parts
		 * @var array  $absolute_path
		 *
		 */
		header("Content-Type: application/json");
		echo json_encode([
			"matched_resource_count" => $matched_resource_count,
			"required_match" => $required_match,
			"resource_dir" => $resource_dir,
			"resource_file" => $resource_file,
			"rule_prefix" => $this->rule->prefix ?? "",
			"absolute_path_with_rule_prefix" => "{$this->ROOT_DIR}/{$this->rule->prefix}",
			"path_parts" => $this->ctx->path_parts,
			"absolute_path" => explode("/", trim("{$this->ROOT_DIR}/{$this->rule->target}", "/"))
		]);
		exit;
	}

	/**
	 * @return void
	 */
	private function handleAPIRuleNotFound(): void {
		header("Content-Type: application/json");
		http_response_code(404);
		if (is_file("{$this->ROOT_DIR}/{$this->rule->target}/404.json")) {
			require_once "{$this->ROOT_DIR}/{$this->rule->target}/404.json";
		} else {
			echo "{\"ok\": false,\"message\": \"Cannot {$this->ctx->method} {$this->ctx->uri}\"}";
		}
		die();
	}
}