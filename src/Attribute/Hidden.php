<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Hidden extends Attribute implements Boolean
{
	public function __construct(bool $value = true)
	{
		parent::__construct('hidden', $value);
	}
}
