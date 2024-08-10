<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Method extends Attribute
{
	public function __construct(string $value)
	{
		$this->setName('method');
		$this->setValue($value);
	}

	public function isPost(): bool
	{
		return $this->value === 'POST';
	}

	public function isPut(): bool
	{
		return $this->value === 'PUT';
	}

	public function isDelete(): bool
	{
		return $this->value === 'DELETE';
	}

	public function isPatch(): bool
	{
		return $this->value === 'PATCH';
	}

	protected function setValue(mixed $value): void
	{
		$value = trim($value);

		if (mb_strlen($value) === 0) {
			throw new \InvalidArgumentException('The "method" attribute cannot be empty.');
		}

		parent::setValue(strtoupper($value));
	}
}
