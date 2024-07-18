<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;
use Meraki\Html\Form\Field\Constraint;

final class Pattern extends Attribute implements Constraint
{
	public function __construct(string $regex)
	{
		if ($regex === '') {
			throw new \InvalidArgumentException('The regular expression pattern cannot be an empty string.');
		}

		parent::__construct('pattern', $regex);
	}

	public function __toString(): string
	{
		// @todo: escape pattern and convert from pcre2 to javascript regex
		return $this->name . '="' . $this->convertToJavaScriptRegex($this->value) . '"';
	}

	private function convertToJavaScriptRegex(string $phpRegex): string
	{
		// remove delimiters, accounting for modifiers
		$jsRegex = preg_replace('/^\/(.*)\/[a-zA-Z]*$/', '$1', $phpRegex);

		// remove beginning and end of string anchors
		$jsRegex = preg_replace('/^\\^|\\$$/', '', $jsRegex);

		return $jsRegex;
	}
}
