<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

/**
 * The "prefix" attribute is a custom attribute that can be
 * used to specify a prefix for the value of the input element.
 *
 * The prefix is prepended to the value of the input element when submitted.
 *
 * For example, if the prefix is set to "https://" and the user enters "example.com" into the input element,
 * the value submitted will be "https://example.com".
 */
final class Prefix extends Attribute
{
	public function __construct(string $value)
	{
		$this->setName('prefix');
		$this->setValue($value);
	}

	public static function of(string $prefix): self
	{
		return new self($prefix);
	}
}
