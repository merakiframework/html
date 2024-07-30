<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Placeholder extends Attribute implements Boolean
{
	public function __construct(string $value)
	{
		parent::__construct('placeholder', $value);
	}
}
