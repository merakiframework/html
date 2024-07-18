<?php
declare(strict_types=1);

namespace Meraki\Html\Form;

final class Errors implements \ArrayAccess, \Countable, \IteratorAggregate
{
	/**
	 * @param array<string, array> $messages Each key is the field name, and
	 * 										 each value is an array of error
	 * 										 messages for that field.
	 */
	public function __construct(private array $messages)
	{

	}

	public function count(): int
	{
		$count = 0;

		foreach ($this->messages as $messages) {
			$count += count($messages);
		}

		return $count;
	}

	public function offsetExists($offset): bool
	{
		return isset($this->messages[$offset]);
	}

	public function offsetGet($offset): array
	{
		return $this->messages[$offset];
	}

	public function offsetSet($offset, $value): void
	{
		throw new \BadMethodCallException('FormErrors is immutable');
	}

	public function offsetUnset($offset): void
	{
		throw new \BadMethodCallException('FormErrors is immutable');
	}

	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->messages);
	}

	public function __get(string $name): array
	{
		return $this->messages[$name];
	}

	public function __isset(string $name): bool
	{
		return isset($this->messages[$name]);
	}

	public function __set(string $name, $value): void
	{
		throw new \BadMethodCallException('FormErrors is immutable');
	}

	public function __unset(string $name): void
	{
		throw new \BadMethodCallException('FormErrors is immutable');
	}
}
