<?php

declare(strict_types=1);

namespace G4\Api\App;

/**
 * http://www.php-fig.org/psr/psr-4/
 *
 * Allowing multiple base directories for a single namespace prefix.
 * $loader = new \G4\Api\App\AutoloadPSR4;
 * $loader->register();
 * $loader->addNamespace('Foo\Bar', '/path/to/packages/foo-bar/folder');
 *
 * @template TPrefix of string
 */
class AutoloadPSR4
{
    /**
     * An associative array where the key is a namespace prefix and the value
     * is an array of base directories for classes in that namespace.
     *
     * @var array<string, list<string>>
     */
    protected array $prefixes = [];

    /** Register loader with SPL autoloader stack. */
    public function register(): static
    {
        spl_autoload_register($this->loadClass(...));
        return $this;
    }

    /**
     * Adds a base directory for a namespace prefix.
     *
     * @param string $prefix The namespace prefix.
     * @param string $base_dir A base directory for class files in the namespace.
     * @param bool $prepend If true, prepend the base directory to the stack instead of appending it.
     * @mago-ignore lint:no-boolean-flag-parameter
     */
    public function addNamespace(string $prefix, string $base_dir, bool $prepend = false): static
    {
        $prefix = trim($prefix, '\\') . '\\';
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';

        if (!array_key_exists($prefix, $this->prefixes)) {
            $this->prefixes[$prefix] = [];
        }

        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $base_dir);
            return $this;
        }

        $this->prefixes[$prefix][] = $base_dir;
        return $this;
    }

    /**
     * Loads the class file for a given class name.
     *
     * @return string|false The mapped file name on success, or false on failure.
     */
    public function loadClass(string $class): string|false
    {
        $prefix = $class;

        while (false !== ($pos = strrpos($prefix, '\\'))) {
            $prefix = substr($class, 0, $pos + 1);
            $relative_class = substr($class, $pos + 1);

            $mapped_file = $this->loadMappedFile($prefix, $relative_class);
            if ($mapped_file !== false) {
                return $mapped_file;
            }

            $prefix = rtrim($prefix, '\\');
        }

        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @return string|false
     */
    protected function loadMappedFile(string $prefix, string $relative_class): string|false
    {
        if (!array_key_exists($prefix, $this->prefixes)) {
            return false;
        }

        foreach ($this->prefixes[$prefix] as $base_dir) {
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

            if ($this->requireFile($file)) {
                return $file;
            }
        }

        return false;
    }

    protected function requireFile(string $file): bool
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }

        return false;
    }
}
