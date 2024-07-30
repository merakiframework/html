<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Target extends Attribute
{
	public function __construct(string $value)
	{
		parent::__construct('target', $value);
	}

	public static function self(): self
	{
		return new self('_self');
	}

	public static function blank(): self
	{
		return new self('_blank');
	}

	public static function parent(): self
	{
		return new self('_parent');
	}

	public static function top(): self
	{
		return new self('_top');
	}

	public function isTargetingCurrentWindow(): bool
	{
		return $this->value === '_self';
	}

	public function isTargetingCurrentTab(): bool
	{
		return $this->isTargetingCurrentWindow();
	}

	public function isTargetingNewWindow(): bool
	{
		return $this->value === '_blank';
	}

	public function isTargetingNewTab(): bool
	{
		return $this->isTargetingNewWindow();
	}
}
