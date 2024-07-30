<?php
declare(strict_types=1);

namespace Meraki\Html;

final class DatePickerElement extends Element implements CustomElement
{
	public function __construct()
	{
		parent::__construct('time-picker');
	}
}
