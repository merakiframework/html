<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

/**
 * The "suffix" attribute is a custom attribute that can be
 * used to specify a suffix for the value of the input element.
 *
 * The suffix is appended to the value of the input element when submitted.
 *
 * For example, if the suffix is set to "@example.com" and the user
 * enters "myname" into the input element, then the value submitted will be
 * "myname@example.com".
 */
final class Suffix extends Attribute
{
	public function __construct(string $value)
	{
		$this->setName('suffix');
		$this->setValue($value);
	}

	public static function of(string $suffix): self
	{
		return new self($suffix);
	}
}
