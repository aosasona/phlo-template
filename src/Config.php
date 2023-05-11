<?php

namespace Wytespace\Phlo;

use Exception;

class Config
{
    public static function load(string $dir): bool
    {
        try {
            $file_env = self::_loadFromFile($dir);
            $sy_env = self::_loadFromSystem();

            $env = array_merge($file_env, $sy_env);
            $_ENV = $env;
            return true;
        } catch (Exception $_) {
            return false;
        }
    }

    public static function get(string $key, string $default = null): string | null
    {
        return $_ENV[$key] ?? $default;
    }

    private static function _loadFromSystem(): array
    {
        return $_ENV;
    }

    private static function _loadFromFile(string $dir): array
    {
        $env_file = $dir . '/.env';
        if (!file_exists($env_file)) {
            return [];
        }
        $env_file = fopen($env_file, 'r');
        $env = [];
        while (!feof($env_file)) {
            $line = fgets($env_file);
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            $env[] = $line;
        }
        $env = array_filter(
            $env,
            function ($item) {
                return strpos($item, '#') !== 0;
            }
        );
        $env = array_map(
            function ($item) {
                $parts = explode('=', $item);
                $key = $parts[0] ?? "";
                $key = trim($key);
                $value = trim($parts[1] ?? "");
                return [$key => $value];
            },
            $env
        );

        return $env;
    }
}
