<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Hint extends Attribute
{
	public function __construct($value)
	{
		parent::__construct('hint', $value);
	}
}
