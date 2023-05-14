<?php

namespace Phlo\Core;

enum RuleType: string {
	case REDIRECT = 'redirect';    // this will simply redirect to the provided target
	case API = 'api';              // this will serve files and their method functions (get, post, all) as JSON REST endpoints
	case STATIC = 'static';        // this will serve exactly what is requested
	case STICKY = 'sticky';    // this will serve the file pointed to by the target, regardless of what is requested, useful for serving index.html in SPA apps
}