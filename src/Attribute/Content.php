<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Content extends Attribute
{
	public function __construct(string $value)
	{
		parent::__construct('content', $value);
	}
}
