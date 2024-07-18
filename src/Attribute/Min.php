<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;
use Meraki\Html\Form\Field\Constraint;
use Meraki\Html\Form\Field;

/**
 * The "min" attribute.
 *
 * The "min" attribute is used to specify the minimum number of characters
 * that a form field must contain.
 */
final class Min extends Attribute implements Constraint
{
	public function __construct(int|string $value)
	{
		parent::__construct('min', (string)$value);
	}

	public static function of(int|string $value): self
	{
		return new self($value);
	}
}
