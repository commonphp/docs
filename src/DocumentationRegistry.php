<?php

declare(strict_types=1);

namespace CommonPHP\Docs;

use ArrayIterator;
use CommonPHP\Docs\Exceptions\DocsException;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<string, Documentation>
 */
final class DocumentationRegistry implements Countable, IteratorAggregate
{
    /**
     * @var array<string, Documentation>
     */
    private array $documentation = [];

    private ?string $defaultName = null;

    /**
     * @param iterable<string, Documentation> $documentation
     */
    public function __construct(iterable $documentation = [])
    {
        foreach ($documentation as $name => $docs) {
            if (!$docs instanceof Documentation) {
                throw new DocsException('Documentation registry values must be Documentation instances.');
            }

            if (!is_string($name)) {
                throw new DocsException('Documentation registry names must be strings.');
            }

            $this->set($name, $docs);
        }
    }

    public static function single(string $name, Documentation $documentation, bool $default = true): self
    {
        return (new self())->set($name, $documentation, $default);
    }

    public function register(string $name, Documentation $documentation, bool $default = false): self
    {
        $name = self::normalizeName($name);

        if ($this->has($name)) {
            throw new DocsException('Documentation registry name "' . $name . '" is already registered.');
        }

        return $this->set($name, $documentation, $default);
    }

    public function set(string $name, Documentation $documentation, bool $default = false): self
    {
        $name = self::normalizeName($name);
        $this->documentation[$name] = $documentation;

        if ($this->defaultName === null || $default) {
            $this->defaultName = $name;
        }

        return $this;
    }

    public function remove(string $name): self
    {
        $name = self::normalizeName($name);
        unset($this->documentation[$name]);

        if ($this->defaultName === $name) {
            $this->defaultName = array_key_first($this->documentation);
        }

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->documentation[self::normalizeName($name)]);
    }

    public function get(?string $name = null): Documentation
    {
        $name = $this->resolveName($name);

        return $this->documentation[$name] ?? throw new DocsException('Documentation registry name "' . $name . '" was not found.');
    }

    public function setDefault(string $name): self
    {
        $name = self::normalizeName($name);

        if (!$this->has($name)) {
            throw new DocsException('Documentation registry name "' . $name . '" was not found.');
        }

        $this->defaultName = $name;

        return $this;
    }

    public function defaultName(): string
    {
        return $this->defaultName ?? throw new DocsException('No default documentation registry entry is configured.');
    }

    /**
     * @return array<string, Documentation>
     */
    public function all(): array
    {
        return $this->documentation;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->documentation);
    }

    public function clear(): self
    {
        $this->documentation = [];
        $this->defaultName = null;

        return $this;
    }

    /**
     * @return array{0: Documentation, 1: string, 2: string}
     */
    public function resolvePath(string $path): array
    {
        $path = FilesystemDocumentLoader::normalizeDocumentPath($path, true);

        if ($path === '') {
            $name = $this->defaultName();

            return [$this->get($name), '', $name];
        }

        $segments = explode('/', $path);
        $first = $segments[0] ?? '';

        if ($first !== '' && $this->has($first)) {
            array_shift($segments);
            $name = self::normalizeName($first);

            return [$this->get($name), implode('/', $segments), $name];
        }

        $name = $this->defaultName();

        return [$this->get($name), $path, $name];
    }

    public function count(): int
    {
        return count($this->documentation);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->documentation);
    }

    public static function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9_.-]+/', '-', $name) ?? '';
        $name = trim($name, '-_.');

        if ($name === '') {
            throw new DocsException('Documentation registry name cannot be empty.');
        }

        return $name;
    }

    private function resolveName(?string $name): string
    {
        return $name === null ? $this->defaultName() : self::normalizeName($name);
    }
}
