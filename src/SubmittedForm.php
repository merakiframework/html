<?php
declare(strict_types=1);

namespace Meraki\Html\Form;

use Meraki\Html\Form\Schema;

final class SubmittedForm
{
	public function __construct(private \stdClass $data)
	{

	}

	public function toSchema(): Schema
	{

	}

	public function hasErrors(): bool
	{
		return $this->countErrors() > 1;
	}

	public function countErrors(): int
	{
		$count = 0;

		foreach ($this->data->fields as $field) {
			if (isset($field->errors) && is_countable($field->errors)) {
				$count += count($field->errors);
			}
		}

		return $count;
	}

	public function offsetExists($offset): bool
	{
		return isset($this->data[$offset]);
	}

	public function offsetGet($offset): mixed
	{
		return $this->data[$offset];
	}

	public function offsetSet($offset, $value): void
	{
		$this->data[$offset] = $value;
	}

	public function offsetUnset($offset): void
	{
		unset($this->data[$offset]);
	}

	public function toArray(): array
	{
		return $this->data;
	}
}
