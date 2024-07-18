<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;
use Meraki\Html\Form\Field\Constraint;

/**
 * A "precision" attribute.
 *
 * The "precision" attribute is used to specify/validate the number of
 * decimal places to allow in a form field.
 */
final class Precision extends Attribute implements Constraint
{
	public function __construct(int|string $places)
	{
		if (is_string($places) && !ctype_digit($places)) {
			throw new \InvalidArgumentException('The value can only contain an integer or integer string.');
		}

		parent::__construct('precision', (int)$places);
	}

	public static function of(int|string $places): self
	{
		return new self($places);
	}
}
