<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Autocapitalize extends Attribute
{
	public function __construct(string $value)
	{
		$this->setName('autocapitalize');
		$this->setValue($value);
	}

	public static function off(): self
	{
		return new self('off');
	}

	public static function none(): self
	{
		return new self('none');
	}

	public static function on(): self
	{
		return new self('on');
	}

	public static function sentences(): self
	{
		return new self('sentences');
	}

	public static function words(): self
	{
		return new self('words');
	}

	public static function characters(): self
	{
		return new self('characters');
	}
}
