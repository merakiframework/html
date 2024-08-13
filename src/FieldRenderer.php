<?php
declare(strict_types=1);

namespace Meraki\Html;

use Meraki\Html\Element;
use Meraki\Html\Form\Field;

class FieldRenderer
{
	public function __construct()
	{
	}

	private static array $fieldSpecificAttributes = [
		Attribute\Hidden::class,	// global attribute...
		Attribute\Class_::class,
		Attribute\Style::class,
		Attribute\Data::class,
		Attribute\Label::class,
		Attribute\Id::class,
		Attribute\Hint::class,
	];

	public function supports(Element $element): bool
	{
		return $element instanceof Field;
	}

	public function render(Field $field): string
	{
		// split all attributes into specific field/input/label/etc. attributes
		$fieldAttrs = $field->attributes->subset(...self::$fieldSpecificAttributes);
		$inputAttrs = $field->attributes;
		$label = $fieldAttrs->removeAndReturn(Attribute\Label::class);
		$type = $inputAttrs->get(Attribute\Type::class);
		$idForInput = Attribute\Id::generateRandom('meraki-input-');
		$hint = $fieldAttrs->removeAndReturn(Attribute\Hint::class);

		$fieldAttrs->findOrCreate(Attribute\Class_::class, fn() => new Attribute\Class_())->add('field');

		$inputAttrs->remove(...self::$fieldSpecificAttributes);
		$inputAttrs->add($idForInput);
		$fieldAttrs->add(new Attribute\Data($type->name, $type->value));

		// actual rendering
		$str = '<' . $field->tagName . (string)$fieldAttrs . '>';
		$str .= $this->renderLabelElement($label, $idForInput);

		if ($hint !== null) {
			$str .= $this->renderHintElement($hint, $field);
		}

		// @todo: add support for rendering prefixes and suffixes

		$str .= $this->renderInputElement($inputAttrs, $field);
		$str .= $this->renderValidationMessagesElement($field);
		$str .= "</{$field->tagName}>";

		return $str;
	}

	private function renderLabelElement(Attribute\Label $labelAttr, Attribute\Id $idAttr): string
	{
		$label = new Element(
			'label',
			(new Attribute\Set(Attribute\For_::class))->add(new Attribute\For_($idAttr))
		);

		$label->setContent($labelAttr->value);

		return (new Renderer())->render($label);
	}

	/**
	 * The "hint" element is a button that when clicked, shows a tooltip with the hint text.
	 *
	 * This is accomplished using the "popover" api.
	 */
	private function renderHintElement(Attribute\Hint $hint, Field $field): string
	{
		$id = Attribute\Id::generateRandom('meraki-popover-');
		$button = new Element('button');

		$button->attributes->add(new Attribute('popovertarget', $id->value));
		$button->attributes->add(new Attribute\Class_('hint'));
		$button->attributes->add(new Attribute\Type('button'));
		$button->setContent('i');

		$popover = new Element('div');
		$popover->attributes->add(new Attribute('popover', ''));
		$popover->attributes->add(new Attribute\Class_('hint'));
		$popover->attributes->add($id);
		$popover->setContent($hint->value);

		return (new Renderer())->render($button) . (new Renderer())->render($popover);
	}

	private function renderInputElement(Attribute\Set $inputAttrs, Field $field): string
	{
		$inputWrapper = new Element('div', (new Attribute\Set(Attribute\Class_::class))->add(new Attribute\Class_('input')));
		$children = match ($field::class) {
			Field\Address::class => $this->renderAddressInput($inputAttrs, $field),
			Field\Boolean::class => $this->renderBooleanInput($inputAttrs, $field),
			// Field\Color::class => $this->renderColorInput($inputAttrs, $field),
			Field\CreditCard::class => $this->renderCreditCardInput($inputAttrs, $field),
			Field\Date::class => $this->renderDateInput($inputAttrs, $field),
			Field\DateTime::class => $this->renderDateTimeInput($inputAttrs, $field),
			Field\EmailAddress::class => $this->renderEmailAddressInput($inputAttrs, $field),
			Field\Enum::class => $this->renderEnumInput($inputAttrs, $field),
			// Field\File::class => $this->renderFileInput($inputAttrs, $field),
			Field\Link::class => $this->renderLinkInput($inputAttrs, $field),
			Field\Money::class => $this->renderMoneyInput($inputAttrs, $field),
			// Field\Month::class => $this->renderDateInput($inputAttrs, $field),
			Field\Name::class => $this->renderNameInput($inputAttrs, $field),
			Field\Number::class => $this->renderNumberInput($inputAttrs, $field),
			Field\Passphrase::class => $this->renderPassphraseInput($inputAttrs, $field),
			Field\Password::class => $this->renderPasswordInput($inputAttrs, $field),
			Field\PhoneNumber::class => $this->renderPhoneNumberInput($inputAttrs, $field),
			Field\Text::class => $this->renderTextInput($inputAttrs, $field),
			Field\Time::class => $this->renderTimeInput($inputAttrs, $field),
			Field\Url::class => $this->renderUrlInput($inputAttrs, $field),
			Field\Uuid::class => $this->renderUuidInput($inputAttrs, $field),
			// Field\Week::class => $this->renderWeekInput($inputAttrs, $field),
			default => throw new \InvalidArgumentException(
				'Cannot render input: Unknown field type "' . $field::class . '"'
			)
		};

		$inputWrapper->appendContent(...$children);

		return (new Renderer())->render($inputWrapper);
	}

	private function renderGroupInput(Attribute\Set $inputAttrs, Field\Group $field): array
	{
		//@todo: implement
	}

	private function renderWeekInput(Attribute\Set $inputAttrs, Field\Week $field): array
	{
		//@todo: implement
	}

	private function renderColorInput(Attribute\Set $inputAttrs, Field\Color $field): array
	{
		//@todo: implement
	}

	private function renderFileInput(Attribute\Set $inputAttrs, Field\File $field): array
	{
		//@todo: implement
	}

	private function renderMonthInput(Attribute\Set $inputAttrs, Field\Month $field): array
	{
		//@todo: implement
	}

	private function renderAddressInput(Attribute\Set $inputAttrs, Field\Address $field): array
	{
		// the address field, like the credit card field, is a composite field made up of
		// multiple input fields. By default, all fields are shown. Javascript is used to
		// hide fields based on field criteria/options. Otherwise all fields are shown.
	}

	private function renderCreditCardInput(Attribute\Set $inputAttrs, Field\CreditCard $field): array
	{
		// the credit card field is a composite field made up of multiple input fields. By default,
		// all fields are shown. Javascript is used to hide fields based on field criteria/options.
		// Otherwise all fields are shown.
	}

	private function renderBooleanInput(Attribute\Set $inputAttrs, Field\Boolean $field): array
	{
		$value = $inputAttrs->get(Attribute\Value::class);

		$inputAttrs->set(new Attribute\Type('checkbox'));

		// remove "value" attribute if it's a boolean
		if ($value !== null && is_bool($value->value)) {
			$inputAttrs->remove($value);
		}

		if ($field->isChecked() || $value->provided()) {
			$inputAttrs->add(new Attribute\Checked());
		}

		return [new Element('input', $inputAttrs)];
	}

	private function renderDateInput(Attribute\Set $inputAttrs, Field\Date $field): array
	{
		$input = new Element('input', $inputAttrs);

		// @todo: implement custom date picker

		return [$input];
	}

	private function renderDateTimeInput(Attribute\Set $inputAttrs, Field\DateTime $field): array
	{
		$inputAttrs->set(new Attribute\Type('datetime-local'));

		$input = new Element('input', $inputAttrs);

		// @todo: implement custom date-time picker

		return [$input];
	}

	private function renderEmailAddressInput(Attribute\Set $inputAttrs, Field\EmailAddress $field): array
	{
		$inputAttrs->set(new Attribute\Type('email'));

		return [new Element('input', $inputAttrs)];
	}

	private function renderEnumInput(Attribute\Set $inputAttrs, Field\Enum $field): array
	{
		$options = $inputAttrs->get(Attribute\Options::class);

		// remove unsupported attributes
		$inputAttrs->remove(Attribute\Type::class, Attribute\Options::class);

		$input = new Element('select', $inputAttrs);

		foreach ($options as $valueName => $valueLabel) {
			$option = new Element('option', new Attribute\Set(Attribute\Value::class, Attribute\Selected::class));

			$option->attributes->add(new Attribute\Value($valueName));
			$option->setContent($valueLabel);

			$value = $field->attributes->get(Attribute\Value::class);

			// set default "selected"
			if ($value->value === $valueName) {
				$option->attributes->add(new Attribute\Selected());
			}

			$input->appendContent($option);
		}

		return [$input];
	}

	private function renderMoneyInput(Attribute\Set $inputAttrs, Field\Money $field): array
	{
		$value = $inputAttrs->get(Attribute\Value::class);
		$precision = $inputAttrs->removeAndReturn(Attribute\Precision::class);

		$inputAttrs->set(new Attribute\Type('number'));

		$input = new Element('input', $inputAttrs);

		// set default value
		if (!$value->provided()) {
			if ($field->attributes->contains(Attribute\Min::class)) {
				$value = $field->attributes->get(Attribute\Min::class)->value;
			} else {
				$value = '0.' . str_repeat('0', $precision->value);
			}

			$input->attributes->set(new Attribute\Value($value));
		}

		// set default step constraint making sure it matches the precision
		if (!$field->attributes->contains(Attribute\Step::class)) {
			$step = '0.' . str_repeat('0', $precision->value - 1) . '1';
			$input->attributes->add(new Attribute\Step($step));
		}

		$symbol = new Element('span', (new Attribute\Set(Attribute\Class_::class))->add(new Attribute\Class_('currency-symbol')));
		$symbol->setContent($this->getCurrencySymbol($field->attributes->get(Attribute\Currency::class)->value));

		return [$symbol, $input];
	}

	private function getCurrencySymbol(string $currency): string
	{
		return match ($currency) {
			'GBP' => '£',
			'EUR' => '€',
			'AUD', 'USD' => '$',
			default => '$'
		};
	}

	private function renderNameInput(Attribute\Set $inputAttrs, Field\Name $field): array
	{
		$inputAttrs->set(new Attribute\Type('text'));

		return [new Element('input', $inputAttrs)];
	}

	private function renderNumberInput(Attribute\Set $inputAttrs, Field\Number $field): array
	{
		$inputAttrs->set(new Attribute\Type('number'));

		return [new Element('input', $inputAttrs)];
	}

	private function renderPassphraseInput(Attribute\Set $inputAttrs, Field\Passphrase $field): array
	{
		$inputAttrs->set(new Attribute\Type('password'));

		$input = new Element('input', $inputAttrs);

		// @todo implement password strength meter

		return [$input];
	}

	private function renderPasswordInput(Attribute\Set $inputAttrs, Field\Password $field): array
	{
		$inputAttrs->set(new Attribute\Type('password'));

		$input = new Element('input', $inputAttrs);

		// @todo: implement a "policy match checker element" (lists the password policies next to input,
		// then gives ticks/crosses based on if policy was met or not), rather than error messages.

		return [$input];
	}

	private function renderPhoneNumberInput(Attribute\Set $inputAttrs, Field\PhoneNumber $field): array
	{
		$inputAttrs->set(new Attribute\Type('tel'));

		return [new Element('input', $inputAttrs)];
	}

	private function renderTextInput(Attribute\Set $inputAttrs, Field\Text $field): array
	{
		// textarea input for multiline...
		if ($field->attributes->contains(Attribute\Multiline::class)) {
			$value = $inputAttrs->get(Attribute\Value::class);

			$inputAttrs->remove(Attribute\Type::class, Attribute\Value::class);

			$input = new Element('textarea', $inputAttrs);

			if ($value->provided()) {
				$input->setContent($value->value);
			}
		} else {
			$input = new Element('input', $inputAttrs);
		}

		return [$input];
	}

	private function renderTimeInput(Attribute\Set $inputAttrs, Field\Time $field): array
	{
		$input = new Element('input', $inputAttrs);

		// @todo: implement custom time picker

		return [$input];
	}

	private function renderUrlInput(Attribute\Set $inputAttrs, Field\Url $field): array
	{
		$inputAttrs->set(new Attribute\Type('url'));

		return [new Element('input', $inputAttrs)];
	}

	private function renderUuidInput(Attribute\Set $inputAttrs, Field\Uuid $field): array
	{
		$version = $field->attributes->findOrCreate(Attribute\Version::class, fn() => Attribute\Version::any());
		$pattern = $this->getPatternForVersion($version->value);

		$inputAttrs->allow(Attribute\Pattern::class);	// allow pattern attribute for rendering
		$inputAttrs->add(new Attribute\Pattern($pattern));
		$inputAttrs->set(new Attribute\Type('text'));

		$input = new Element('input', $inputAttrs);

		$input->attributes->remove(Attribute\Version::class);	// not a valid HTML attribute

		return [$input];
	}

	private function renderLinkInput(Attribute\Set $inputAttrs, Field\Link $field): array
	{
		$subset = $inputAttrs->subset(Attribute\Name::class, Attribute\Value::class);
		$input = new Element(
			'input',
			$subset->set(new Attribute\Type('hidden'))
		);

		$link = new Element(
			'a',
			(new Attribute\Set())
				->add(new Attribute\Href($field->target))
				->add(Attribute\Target::blank())
		);

		$link->setContent(...$field->content);

		if ($field->isDisabled()) {
			/** @var Attribute\Style $style */
			$style = $link->attributes->findOrCreate(Attribute\Style::class, fn() => new Attribute\Style());
			$style->set('pointer-events', 'none');

			$link->attributes->set(
				$style,
				new Attribute\Disabled(),		// doesn't really do anything, just a hook for CSS
				Attribute\Tabindex::of('-1'),	// prevent tabbing to link
			);
		}

		return [$input, $link];
	}

	private function getPatternForVersion(int|string $version): string
	{
		return match ($version) {
			1, '1' => '^[0-9a-f]{8}-[0-9a-f]{4}-1[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
			2, '2' => '^[0-9a-f]{8}-[0-9a-f]{4}-2[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
			3, '3' => '^[0-9a-f]{8}-[0-9a-f]{4}-3[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
			4, '4' => '^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
			5, '5' => '^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
			6, '6' => '^[0-9a-f]{8}-[0-9a-f]{4}-6[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
			7, '7' => '^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
			8, '8' => '^[0-9a-f]{8}-[0-9a-f]{4}-8[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
			'any' => '^[0-9a-f]{8}-[0-9a-f]{4}-\d[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
			default => throw new \InvalidArgumentException('Unknown UUID version: ' . $version)
		};
	}

	private function renderValidationMessagesElement(Field $field): string
	{
		$messages = new Element(
			'ul',
			(new Attribute\Set(Attribute\Class_::class))->add(new Attribute\Class_('validation-messages'))
		);

		if (!$field->inputGiven) {
			return (new Renderer())->render($messages);
		}

		foreach ($field->errors as $error) {
			$attrs = (new Attribute\Set(Attribute\Class_::class))->add(new Attribute\Class_('error'));
			$messages->appendContent((new Element('li', $attrs))->setContent($error));
		}

		return (new Renderer())->render($messages);
	}
}
