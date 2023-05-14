<?php

namespace Phlo\Core;

enum MimeType: string {
	case JSON = 'application/json';
	case FORM_DATA = 'multipart/form-data';
	case FORM_URLENCODED = 'application/x-www-form-urlencoded';
	case TEXT = 'text/plain';
	case HTML = 'text/html';
	case XML = 'application/xml';
	case ANY = '*/*';

	public static function fromString(string $mime_type): ?MimeType {
		return match ($mime_type) {
			'application/json' => self::JSON,
			'multipart/form-data' => self::FORM_DATA,
			'application/x-pages-form-urlencoded' => self::FORM_URLENCODED,
			'text/plain' => self::TEXT,
			'text/html' => self::HTML,
			'application/xml' => self::XML,
			'*/*' => self::ANY,
			default => null,
		};
	}
}