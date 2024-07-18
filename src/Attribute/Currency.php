<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

/**
 * The "currency" attribute.
 *
 * This attribute is not a constraint, but is used to specify the currency
 * that the "money" form field takes in.
 */
final class Currency extends Attribute
{
	public function __construct(string $value)
	{
		parent::__construct('currency', $value);
	}

	public static function aud(): self
	{
		return new self('AUD');
	}
}
