<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;
use Meraki\Html\Element;
use Meraki\Html\Form\Field\Constraint;
use Meraki\Html\Attribute\Boolean;
use Meraki\Html\Form\Field;
use Meraki\Html\Form\Field\ValidationResult;

/**
 * The "required" attribute.
 *
 * The "required" attribute is a boolean attribute that
 * is used to indicate that a form field must be filled
 * out before submitting the form.
 */
final class Required extends Attribute implements Boolean, Constraint
{
	public function __construct()
	{
		parent::__construct('required', true);
	}
}
