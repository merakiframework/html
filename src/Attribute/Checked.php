<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Checked extends Attribute implements Boolean
{
	public function __construct(bool $value = true)
	{
		parent::__construct('checked', $value);
	}
}
