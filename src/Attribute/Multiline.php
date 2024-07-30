<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;
use Meraki\Html\Form\Field\Constraint;
use Meraki\Html\Attribute\Boolean;
use Meraki\Html\Form\Field;

final class Multiline extends Attribute implements Boolean, Constraint
{
	public function __construct(bool $value = true)
	{
		parent::__construct('multiline', $value);
	}
}
