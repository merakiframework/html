<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;
use Meraki\Html\Form\Field\Constraint;
use Meraki\Html\Attribute\Boolean;
use Meraki\Html\Form\Field;

final class Max extends Attribute implements Constraint
{
	public function __construct(int|string $value)
	{
		parent::__construct('max', (string)$value);
	}

	public static function of(int|string $value): self
	{
		return new self($value);
	}
}
