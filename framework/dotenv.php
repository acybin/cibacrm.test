<?php

namespace framework;

use Exception;

/**
 * Manages .env files
 *
 * Class dotenv
 * @package framework
 */
class dotenv
{
    /**
     * Load one .env file.
     *
     * @param string $file  file to load
     * @throws Exception    when a file does not exist or is not readable
     */
    private function load_from_file(string $file): void
    {
        if (!file_exists($file)) {
            throw new Exception(sprintf("Отстуствует файл конфигурации %s", $file));
        }

        $env_file = parse_ini_file($file);

        if (!$env_file) {
            throw new Exception(sprintf("Не удалось обработать файл конфигурации %s", $file));
        }

        array_walk($env_file, function ($value, $key) {
            $_ENV[$key] = $value;
        });
    }

    /**
     * Loads .env file and the corresponding env.$env files if they exist
     *
     * @param string $file  file to load
     * @return bool         when a file does not exist or is not readable
     */
    public function load(string $file): bool
    {
        try {
            $this->load_from_file($file);
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }

        if (!empty($_ENV['APP_ENV'])) {
            try {
                $this->load_from_file(dirname($file) . '/.env.' . $_ENV['APP_ENV']);
            } catch (Exception $e) {
                echo $e->getMessage();
                return false;
            }
        }

        return true;
    }

    /**
     * Return values of ENV elements from them keys
     *
     * @param array $vars   keys of ENV elements
     * @return array        values of ENV elements
     * @throws Exception    when one of values is empty
     */
    public static function get_vars(array $vars): array
    {
        $env_vars = [];
        foreach ($vars as $var) {

            if (empty($_ENV[$var])) {
                throw new Exception(sprintf('Не указан %s', $var));
            }

            $env_vars[] = $_ENV[$var];
        }
        return $env_vars;
    }
}

