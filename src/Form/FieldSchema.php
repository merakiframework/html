<?php
declare(strict_types=1);

namespace Meraki\Html\Form;

abstract class FieldSchema implements \ArrayAccess
{
	private array $cache = [];
	abstract protected function data(): array;

	protected function cache(): array
	{
		if (empty($this->cache)) {
			$this->cache = $this->normalise($this->data());
		}

		return $this->cache;
	}

	public function weekStartsOn(int $day): self
	{
		$type = $this->cache()['type'];

		if (in_array($type, [FormSchemaFieldType::date, FormSchemaFieldType::datetime])) {
			$cache = $this->cache();
			$cache['firstDayOfWeek'] = $day;
			$this->cache = $cache;
		}

		return $this;
	}

	public function withValue(mixed $value): self
	{
		if (is_bool($value)) {
			$value = $value ? 'on' : 'off';
		}

		$cache = $this->cache();
		$cache['value'] = (string) $value;
		$this->cache = $cache;

		return $this;
	}

	public function name(string $name): self
	{
		$cache = $this->cache();
		$cache['name'] = $name;
		$this->cache = $cache;

		return $this;
	}

	public function disable(): self
	{
		$cache = $this->cache();
		$cache['disabled'] = true;
		// $cache['constraints']['required'] = false;
		$this->cache = $cache;

		return $this;
	}

	private function normalise(array $data): array
	{
		if (isset($data['oneOf'])) {
			foreach ($data['oneOf'] as &$item) {
				$item = new \ArrayObject($item, \ArrayObject::ARRAY_AS_PROPS);
			}
		}

		if (isset($data['constraints'])) {
			$data['constraints'] = new \ArrayObject($data['constraints'], \ArrayObject::ARRAY_AS_PROPS);
		}

		return $data;
	}

	public function &offsetGet(mixed $offset): mixed
	{
		return $this->cache()[$offset];
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		$cache = $this->cache();
		// throw new \RuntimeException('Field schema is immutable and cannot be modified.');
		$cache[$offset] = $value;

		$this->cache = $cache;
	}

	public function offsetExists(mixed $offset): bool
	{
		return isset($this->cache()[$offset]);
	}

	public function offsetUnset(mixed $offset): void
	{
		// throw new \RuntimeException('Field schema is immutable and cannot be modified.');
		if (isset($this->cache()[$offset])) {
			unset($this->cache()[$offset]);
		}
	}

	public function __isset($name): bool
	{
		return isset($this->cache()[$name]);
	}

	public function &__get($name): mixed
	{
		return $this->cache()[$name];
	}

	public function __set($name, $value): void
	{
		$cache = $this->cache();
		// throw new \RuntimeException('Field schema is immutable and cannot be modified.');
		$cache[$name] = $value;

		$this->cache = $cache;
	}

	public function __unset($name): void
	{
		// throw new \RuntimeException('Field schema is immutable and cannot be modified.');
		if (isset($this->cache()[$name])) {
			unset($this->cache()[$name]);
		}
	}
}
