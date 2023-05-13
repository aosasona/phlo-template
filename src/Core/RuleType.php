<?php

namespace Wytespace\Phlo\Core;

enum RuleType: string {
	case REDIRECT = 'redirect';    // this will simply redirect to the provided target
	case API = 'api';              // this will serve files and their method functions (get, post, all) as JSON REST endpoints
	case STATIC = 'static';        // this will serve exactly what is requested
}