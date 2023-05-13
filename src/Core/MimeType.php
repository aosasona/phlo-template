<?php

namespace Wytespace\Phlo\Core;

enum MimeType: string {
	case JSON = 'application/json';
	case STATIC = 'static';

	public function getMimeType(string $filename): string {
		return match ($this) {
			MimeType::JSON => "application/json",
			MimeType::STATIC => mime_content_type($filename) ?: "text/html"
		};
	}
}