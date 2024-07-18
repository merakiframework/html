<?php
declare(strict_types=1);

namespace Meraki\Html;

use Meraki\Html\Attribute;
use Meraki\Html\Form\Field;

final class Factory
{
	public function create(string $tagName, array $attributes)
	{
		return new Element($tagName, $this->createAttributeSet($attributes));
	}

	public function createTextField(string $name, string $label, array $attributes = []): Field\Text
	{
		return new Field\Text(
			new Attribute\Name($name),
			new Attribute\Label($label),
			...array_map($this->createAttribute(...), array_keys($attributes), $attributes),
		);
	}

	private function createAttributeSet(array $attributes)
	{
		$attributeSet = new Attribute\Set();

		foreach ($attributes as $attrName => $attrValue) {
			$attribute = $this->createAttribute($attrName, $attrValue);

			if ($attribute !== null) {
				$attributeSet->add($attribute);
			}
		}

		return $attributeSet;
	}

	private function createAttribute(string $name, mixed $value): ?Attribute
	{
		$name = strtolower($name);

		if ($name === 'type') {
			return new Attribute\Type($value);
		}

		if ($name === 'min') {
			return new Attribute\Min($value);
		}

		if ($name === 'max') {
			return new Attribute\Max($value);
		}

		if ($name === 'step') {
			return new Attribute\Step($value);
		}

		if ($name === 'first-day-of-week') {
			return new Attribute\FirstDayOfWeek($value);
		}

		if ($name === 'name') {
			return new Attribute\Name($value);
		}

		if ($name === 'class') {
			if (is_string($value)) {
				$value = explode(' ', $value);
			}

			return new Attribute\Class_(...$value);
		}

		if ($name === 'id') {
			return new Attribute\Id($value);
		}

		if ($name === 'value') {
			return new Attribute\Value($value);
		}

		if ($name === 'for') {
			return new Attribute\For_($value);
		}

		if ($name === 'href') {
			return new Attribute\Href($value);
		}

		if ($name === 'src') {
			return new Attribute\Src($value);
		}

		if ($name === 'alt') {
			return new Attribute\Alt($value);
		}

		if ($name === 'style') {
			if (!is_array($value) || array_is_list($value)) {
				throw new \InvalidArgumentException('The value for the "style" attribute must be an associative array.');
			}

			return new Attribute\Style($value);
		}

		return null;
	}
}
