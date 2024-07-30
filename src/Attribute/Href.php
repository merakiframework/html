<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Href extends Attribute
{
	public function __construct($value)
	{
		parent::__construct('href', $value);
	}
}
