<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Factory
{
	private static $mappings = [
		'accesskey' => Accesskey::class,
		'action' => Action::class,
		'algorithm' => Algorithm::class,				// custom attribute
		'autocapitalize' => Autocapitalize::class,
		'autocomplete' => Autocomplete::class,
		'autofocus' => Autofocus::class,
		'availability' => Availability::class,			// custom attribute

		'checked' => Checked::class,
		'class' => Class_::class,
		'content' => Content::class,
		'contenteditable' => Contenteditable::class,
		'currency' => Currency::class,					// custom attribute

		'data' => Data::class,
		'disabled' => Disabled::class,

		'entropy' => Entropy::class,					// custom attribute

		'first-day-of-week' => FirstDayOfWeek::class,	// custom attribute
		'for' => For_::class,

		'hidden' => Hidden::class,
		'hint' => Hint::class,							// custom attribute
		'href' => Href::class,

		'id' => Id::class,

		'label' => Label::class,						// custom attribute

		'mask' => Mask::class,							// custom attribute
		'max' => Max::class,
		'method' => Method::class,
		'min' => Min::class,
		'multiline' => Multiline::class,				// custom attribute

		'name' => Name::class,
		'novalidate' => Novalidate::class,

		'options' => Options::class,					// custom attribute

		'pattern' => Pattern::class,
		'placeholder' => Placeholder::class,
		'policy' => Policy::class,						// custom attribute
		'precision' => Precision::class,				// custom attribute

		'readonly' => Readonly_::class,
		'required' => Required::class,
		'role' => Role::class,

		'selected' => Selected::class,
		'step' => Step::class,
		'style' => Style::class,

		'tabindex' => Tabindex::class,
		'target' => Target::class,
		'title' => Title::class,
		'type' => Type::class,

		'value' => Value::class,
		'version' => Version::class,					// custom attribute
	];

	public function create(string $name, mixed $value): Attribute
	{
		if (isset(self::$mappings[$name])) {
			$className = static::$mappings[$name];

			return new $className($value);
		}

		throw new \InvalidArgumentException('Could not create attribute for "' . $name . '".');
	}
}
