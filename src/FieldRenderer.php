<?php
declare(strict_types=1);

namespace Meraki\Html;

use Meraki\Html\Element;
use Meraki\Html\Form\Field;
use Meraki\Html\Form\FieldType;

class FieldRenderer
{
	private const ISO_DAY_TO_SHORT_DAY_MAPPINGS = [
		1 => 'Mo',
		2 => 'Tu',
		3 => 'We',
		4 => 'Th',
		5 => 'Fr',
		6 => 'Sa',
		7 => 'Su',
	];

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
	];

	public function supports(Element $element): bool
	{
		return $element instanceof Field;
	}

	public function render(Field $field): string
	{
		$fieldAttrs = $field->attributes->subset(...self::$fieldSpecificAttributes);
		$inputAttrs = $field->attributes;
		$label = $fieldAttrs->get(Attribute\Label::class);
		$type = $inputAttrs->get(Attribute\Type::class);
		$idForInput = Attribute\Id::generateRandom('meraki-input-');

		// only add value attribute if there is a value
		if ($field->value !== null) {
			$inputAttrs->add(new Attribute\Value($field->value));
		}

		$fieldAttrs->findOrCreate(Attribute\Class_::class, fn() => new Attribute\Class_())->add('field');

		$inputAttrs->remove(...self::$fieldSpecificAttributes);
		$inputAttrs->add($idForInput);
		$fieldAttrs->add(new Attribute\Data($type->name, $type->value));
		$fieldAttrs->remove($label);

		// @bug: Adding any attributes to the field element from any of the render*
		// methods will not work. The attributes are rendered before the render* methods
		// are called.
		$str = '<' . $field->tagName . (string)$fieldAttrs . '>';
		$str .= $this->renderLabelElement($label, $idForInput);
		$str .= $this->renderInputElement($inputAttrs, $field);
		$str .= $this->renderValidationMessagesElement($field);
		$str .= '</' . $field->tagName . '>';

		return $str;
		var_dump($field->tagName);die;
		$fieldType = $field->attributes->get('type');

		$label = self::buildLabel($field);
		$input = self::buildInput($field);
		$messages = self::buildErrors($field);
		$el = new Element('div', ['class' => 'field']);

		// if ($label->getAttribute('for') === null && $input->getAttribute('id') !== null) {
		// 	$label->addAttribute('for', $input->getAttribute('id'));
		// } elseif ($label->getAttribute('for') !== null && $input->getAttribute('id') === null) {
		// 	$input->addAttribute('id', $label->getAttribute('for'));
		// } elseif ($label->getAttribute('for') === null && $input->getAttribute('id') === null) {
		// 	$id = uniqid(spl_object_hash($el));
		// 	$label->addAttribute('for', $id);
		// 	$input->addAttribute('id', $id);
		// } elseif ($label->getAttribute('for') !== $input->getAttribute('id')) {
		// 	throw new \InvalidArgumentException('Label and input must have the same id');
		// }

		// var_dump($field);

		if ($field->type === FieldType::link) {
			if (!is_array($field->value) || count($field->value) !== 1) {
				throw new \InvalidArgumentException(
					'"' . $field->name . '" field must have a single element in the format of ["url/for/href" => "label for field"] for its value'
				);
			}
			$div = Element::div([
				'class' => 'field',
				'contenteditable' => !$field->readonly && !$field->disabled
			]);
			$anchor = Element::a([
				'href' => array_key_first($field->value),
				'target' => '_blank'
			])->setContent(array_values($field->value)[0]);
			// if field is disabled, link cannot be followed,
			// readonly allows link to be followed, but not edited
			if ($field->disabled) {
				$anchor->setStyle('pointer-events', 'none')
					->setAttribute('disabled', true)	// doesn't really do anything, just a hook for CSS
					->setAttribute('aria-disabled', 'true')
					->setAttribute('tabindex', '-1')	// prevent tabbing to link
					->setAttribute('href', 'javascript:void(0)');
			}
			$div->appendAllContent($anchor);
			$el->appendAllContent($label, $input, $div, $messages);
		} elseif ($field->type === FieldType::money) {
			$div = Element::div([
				'class' => 'field'
			]);
			$span = Element::span(['class' => 'symbol'])
				->setContent($field->constraints->symbol);
			$el->attributes->set([
				'data-currency' => $field->constraints->currency,
				'data-precision' => $field->constraints->precision,
			]);
			$div->appendAllContent($span, $input);
			$el->appendAllContent($label, $div, $messages);
		} else {
			$el->appendAllContent($label, $input, $messages);
		}

		$el->addAttribute('data-input-type', $fieldType);

		if ($fieldType === 'text' && $field->multiline === true) {
			$el->addAttribute('data-multiline');
		}

		$el->setAttribute('disabled', $field->disabled);
		$el->setAttribute('aria-disabled', $field->disabled);
		$el->setAttribute('readonly', $field->readonly);

		// $field->setPlaceholder($field->placeholder ?? '');
		// $field->setConstraints($field->constraints ?? []);
		// $el->provideHint($field->hint ?? '');

		return $el;
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

	private function renderInputElement(Attribute\Set $inputAttrs, Field $field): string
	{
		return match ($field::class) {
			Field\Boolean::class => $this->renderBooleanInput($inputAttrs, $field),
			Field\Date::class => $this->renderDateInput($inputAttrs, $field),
			Field\DateTime::class => $this->renderDateTimeInput($inputAttrs, $field),
			Field\EmailAddress::class => $this->renderEmailAddressInput($inputAttrs, $field),
			Field\Enum::class => $this->renderEnumInput($inputAttrs, $field),
			Field\Money::class => $this->renderMoneyInput($inputAttrs, $field),
			Field\Name::class => $this->renderNameInput($inputAttrs, $field),
			Field\Number::class => $this->renderNumberInput($inputAttrs, $field),
			Field\Passphrase::class => $this->renderPassphraseInput($inputAttrs, $field),
			Field\Password::class => $this->renderPasswordInput($inputAttrs, $field),
			Field\PhoneNumber::class => $this->renderPhoneNumberInput($inputAttrs, $field),
			Field\Text::class => $this->renderTextInput($inputAttrs, $field),
			Field\Time::class => $this->renderTimeInput($inputAttrs, $field),
			Field\Url::class => $this->renderUrlInput($inputAttrs, $field),
			Field\Uuid::class => $this->renderUuidInput($inputAttrs, $field),
			default => throw new \InvalidArgumentException(
				'Cannot render input: Unknown field type "' . $field::class . '"'
			)
		};
	}

	private function renderBooleanInput(Attribute\Set $inputAttrs, Field\Boolean $field): string
	{
		$inputAttrs->replace(new Attribute\Type('checkbox'));
		$input = new Element('input', $inputAttrs);

		// add the "checked" attribute if value is "truthy"
		if ($field->value ?: $field->default ?: $field->isChecked()) {
			$input->attributes->add(new Attribute\Checked());
		}

		// remove "value" attribute
		$value = $input->attributes->find(Attribute\Value::class);

		if ($value !== null && is_bool($value->value)) {
			$input->attributes->remove($value);
		}

		// @todo: implement a way to choose checkbox or switch

		return (new Renderer())->render($input);
	}

	private function renderDateInput(Attribute\Set $inputAttrs, Field\Date $field): string
	{
		$input = new Element('input', $inputAttrs);

		// @todo: implement custom date picker

		return (new Renderer())->render($input);
	}

	private function renderDateTimeInput(Attribute\Set $inputAttrs, Field\DateTime $field): string
	{
		$inputAttrs->replace(new Attribute\Type('datetime-local'));
		$input = new Element('input', $inputAttrs);

		// @todo: implement custom date-time picker

		return (new Renderer())->render($input);
	}

	private function renderEmailAddressInput(Attribute\Set $inputAttrs, Field\EmailAddress $field): string
	{
		$inputAttrs->replace(new Attribute\Type('email'));
		$input = new Element('input', $inputAttrs);

		return (new Renderer())->render($input);
	}

	private function renderEnumInput(Attribute\Set $inputAttrs, Field\Enum $field): string
	{
		// remove unsupported attributes
		$inputAttrs->remove(Attribute\Type::class);

		$input = new Element('select', $inputAttrs);

		foreach ($field->options as $valueName => $valueLabel) {
			$option = new Element('option', new Attribute\Set(Attribute\Value::class, Attribute\Selected::class));
			$option->attributes->add(new Attribute\Value($valueName));
			$option->setContent($valueLabel);

			// set default "selected"
			if ($field->value === $valueName || $field->defaultValue === $valueName) {
				$option->attributes->add(new Attribute\Selected());
			}

			$input->appendContent($option);
		}

		return (new Renderer())->render($input);
	}

	private function renderMoneyInput(Attribute\Set $inputAttrs, Field\Money $field): string
	{
		$inputAttrs->replace(new Attribute\Type('number'));

		$input = new Element('input', $inputAttrs);

		// set default value
		if ($field->value === null || $field->value === '' || $field->defaultValue === null || $field->defaultValue === '') {
			$value = '0.' . str_repeat('0', $field->attributes->get(Attribute\Precision::class)->value);

			if ($field->attributes->contains(Attribute\Min::class)) {
				$value = $field->attributes->get(Attribute\Min::class)->value;
			}

			$input->attributes->add(new Attribute\Value($value));
		}

		// set default step constraint making sure it matches the precision
		if (!$field->attributes->contains(Attribute\Step::class)) {
			$precision = $field->attributes->get(Attribute\Precision::class)->value;
			$step = '0.' . str_repeat('0', $precision - 1) . '1';
			$input->attributes->add(new Attribute\Step($step));
		}

		$precision = $field->attributes->find(Attribute\Precision::class);

		// remove precision from input as it's not a valid attribute
		// @bug: adding a data-precision attribute to the field element still adds it to the
		// input element, but it should not be there.
		$input->attributes->remove(Attribute\Precision::class);

		// @todo: implement currency symbol

		return (new Renderer())->render($input);
	}

	private function renderNameInput(Attribute\Set $inputAttrs, Field\Name $field): string
	{
		$inputAttrs->replace(new Attribute\Type('text'));
		$input = new Element('input', $inputAttrs);

		return (new Renderer())->render($input);
	}

	private function renderNumberInput(Attribute\Set $inputAttrs, Field\Number $field): string
	{
		$inputAttrs->replace(new Attribute\Type('number'));
		$input = new Element('input', $inputAttrs);

		return (new Renderer())->render($input);
	}

	private function renderPassphraseInput(Attribute\Set $inputAttrs, Field\Passphrase $field): string
	{
		$inputAttrs->replace(new Attribute\Type('password'));
		$input = new Element('input', $inputAttrs);

		// @todo implement password strength meter

		return (new Renderer())->render($input);
	}

	private function renderPasswordInput(Attribute\Set $inputAttrs, Field\Password $field): string
	{
		$inputAttrs->replace(new Attribute\Type('password'));
		$input = new Element('input', $inputAttrs);

		// @todo: implement a criteria checker (lists password policies,
		// then gives ticks/crosses if met), rather than error messages.

		return (new Renderer())->render($input);
	}

	private function renderPhoneNumberInput(Attribute\Set $inputAttrs, Field\PhoneNumber $field): string
	{
		$inputAttrs->replace(new Attribute\Type('tel'));
		$input = new Element('input', $inputAttrs);

		// @todo: implement an international prefix selector

		return (new Renderer())->render($input);
	}

	private function renderTextInput(Attribute\Set $inputAttrs, Field\Text $field): string
	{
		// textarea input for multiline...
		if ($field->attributes->find(Attribute\Multiline::class) !== null) {
			$value = $inputAttrs->find(Attribute\Value::class);
			$inputAttrs->remove(Attribute\Type::class, Attribute\Value::class);
			$input = new Element('textarea', $inputAttrs);
			if ($value !== null) {
				$input->setContent($value->value);
			}
		} else {
			$input = new Element('input', $inputAttrs);
		}

		// $fieldType = $field->type->value;
		// $input->addAttributes([
		// 	'name' => $field->name,
		// 	'placeholder' => $field->placeholder ?? '',
		// ]);

		// foreach ($field->attributes as $attr) {
		// 	// these constraints do not have equivalent HTML attributes
		// 	if (in_array($attr->name, ['unique', 'version', 'multiline', 'policy'])) {
		// 		continue;
		// 	}

		// $input->setAttribute($name, $value);
		// }

		// if (isset($field->autocomplete)) {
		// 	$input->setAttribute('autocomplete', $field->autocomplete);
		// }

		// $hasValue =

		// if ($hasValue && $input->tagName === 'textarea') {
		// 	$input->setContent($field->value);
		// } elseif ($hasValue && $type !== 'password') {
		// 	$input->setAttribute('value', $field->value);
		// }

		return (new Renderer())->render($input);
	}

	private function renderTimeInput(Attribute\Set $inputAttrs, Field\Time $field): string
	{
		$input = new Element('input', $inputAttrs);

		// @todo: implement custom time picker

		return (new Renderer())->render($input);
	}

	private function renderUrlInput(Attribute\Set $inputAttrs, Field\Url $field): string
	{
		$inputAttrs->replace(new Attribute\Type('url'));
		$input = new Element('input', $inputAttrs);

		return (new Renderer())->render($input);
	}

	private function renderUuidInput(Attribute\Set $inputAttrs, Field\Uuid $field): string
	{
		$version = $field->attributes->findOrCreate(Attribute\Version::class, fn() => Attribute\Version::any());
		$pattern = $this->getPatternForVersion($version->value);
		$inputAttrs->allow(Attribute\Pattern::class);	// allow pattern attribute for rendering
		$inputAttrs->add(new Attribute\Pattern($pattern));
		$inputAttrs->replace(new Attribute\Type('text'));
		$input = new Element('input', $inputAttrs);

		$input->attributes->remove(Attribute\Version::class);	// not a valid HTML attribute

		return (new Renderer())->render($input);
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
			default => throw new \InvalidArgumentException(
				'Unknown UUID version: ' . $version
			)
		};
	}

	private static function buildInputElementForEnum($schema): Element
	{
		$values = [];

		foreach ($schema->constraints->values as $optionName => $optionLabel) {
			if (isset($schema->value)) {
				$selected = $optionName === $schema->value;
			} else {
				$selected = false;
			}

			$values[] = Element::option(['value' => $optionName, 'selected' => $selected])
				->setContent($optionLabel);
		}

		$input = Element::select([
			'name' => $schema->name,
		])->appendAllContent(...$values);

		return $input;
	}

	private function renderValidationMessagesElement(Field $field): string
	{
		$messages = new Element(
			'ul',
			(new Attribute\Set(Attribute\Class_::class))->add(new Attribute\Class_('validation-messages'))
		);

		foreach ($field->errors as $error) {
			$attrs = (new Attribute\Set(Attribute\Class_::class))->add(new Attribute\Class_('error'));
			$messages->appendContent((new Element('li', $attrs))->setContent($error));
		}

		return (new Renderer())->render($messages);
	}

	public static function createFromSchema($schema): self
	{
		$fieldType = $schema->type->value;
		$field = new self(
			self::buildLabel($schema),
			self::buildInput($schema),
			self::buildErrors($schema)
		);

		$field->addAttribute('data-input-type', $fieldType);

		if ($fieldType === 'string' && $schema->multiline === true) {
			$field->addAttribute('data-multiline');
		}

		// $field->setPlaceholder($schema->placeholder ?? '');
		// $field->setConstraints($schema->constraints ?? []);
		$field->provideHint($schema->hint ?? '');

		return $field;
	}

	private static function buildInput(Field $field): Element
	{
		// var_dump($field->value);
		$fieldType = $field->type;
		$input = match ($fieldType) {
			FieldType::phone => self::buildInputElementOfType('tel', $field),
			FieldType::datetime => self::buildInputElementForDateTime($field),
			FieldType::number => self::buildInputElementOfType('number', $field),
			FieldType::integer => self::buildInputElementForInteger($field),
			FieldType::uuid => self::buildInputElementOfType('text', $field),
			FieldType::url => self::buildInputElementOfType('url', $field),
			FieldType::name => self::buildInputElementOfType('text', $field),
			FieldType::password => self::buildInputElementOfType('password', $field),
			FieldType::money => self::buildInputElementForMoney($field),
			FieldType::link => self::buildInputElementForLink($field),
			default => throw new \InvalidArgumentException(
				'Cannot build input: Unknown property type "' . $fieldType->value . '"'
			)
		};

		if (isset($field->disabled) && $field->disabled === true) {
			$input->addAttribute('disabled');
		}

		return $input;
	}

	private static function buildInputElementForLink(Field $field): Element
	{
		// $div = Element::div(['class'=>'field']);
		$input = Element::input([
			'type' => 'hidden',
			'name' => $field->name,
			'placeholder' => $field->placeholder ?? '',
			'autocomplete' => 'off'
		]);


		foreach ($field->constraints as $name => $value) {
			$input->setAttribute($name, $value);
		}

		if (isset($field->value)) {
			$href = array_key_first($field->value);
			$input->setAttribute('value', $href);
		}

		// $div->appendAllContent($input, $a);

		return $input;
	}

	private static function buildInputElementForMoney(Field $field): Element
	{
		$input = Element::input([
			'type' => 'text',
			'name' => $field->name,
			'placeholder' => $field->placeholder ?? '',
		]);

		foreach ($field->constraints as $name => $value) {
			if (in_array($name, ['currency', 'symbol', 'precision'])) {
				// $input->setAttribute("data-{$name}", $value);
				continue;
			}

			$input->setAttribute($name, $value);
		}

		if (isset($field->value)) {
			$input->setAttribute('value', $field->value);
		}

		return $input;
	}

	private static function buildInputElementForInteger($schema): Element
	{
		$input = Element::input([
			'type' => 'number',
			'name' => $schema->name,
			'placeholder' => $schema->placeholder ?? '',
			'step' => 1,
			'pattern' => '\d+',
		]);

		foreach ((array) $schema->constraints as $name => $value) {
			$input->setAttribute($name, $value);
		}

		if (isset($schema->value)) {
			$input->setAttribute('value', $schema->value);
		}

		return $input;
	}

	private static function buildInputElementForDate($schema): Element
	{
		$input = Element::input([
			'type' => 'date',
			'name' => $schema->name,
			'placeholder' => $schema->placeholder ?? '',
			'data-first-day-of-week' => $schema->firstDayOfWeek,
		]);

		foreach ((array) $schema->constraints as $name => $value) {
			$input->setAttribute($name, $value);
		}

		if (isset($schema->value)) {
			$input->setAttribute('value', (string) $schema->value);
		}

		return $input;
	}

	private static function buildInputElementForDateTime(Field $field): Element
	{
		$input = Element::input([
			'type' => 'datetime-local',
			'name' => $field->name,
			'placeholder' => $field->placeholder ?? '',
			'data-first-day-of-week' => $field->firstDayOfWeek,
		]);

		foreach ((array) $field->constraints as $name => $value) {
			if ($name === 'availability') {
				continue;
			}
			$input->setAttribute($name, $value);
		}

		if (isset($field->value)) {
			// strip timezone, offset, seconds, and nanoseconds,
			// as datetime-local input does not support these
			$time = (string) $field->value->withSecond(0)->withNano(0)->getTime();
			$input->setAttribute('value', (string) $field->value->getDate() . 'T' . $time);
		}

		return $input;
	}

	private static function buildInputElementForDateTimeOld($schema, string $url): Element
	{
		$interval = new DateTimeInterval($schema->constraints->interval);
		$now = new LocalDateTime($schema->now, new \DateTimeZone($schema->timezone));
		$hasDateValue = isset($schema->value) && isset($schema->value->date);
		$hasTimeValue = isset($schema->value) && isset($schema->value->time);

		if ($hasDateValue) {
			$selectedDate = new LocalDateTime($schema->value->date, new \DateTimeZone($schema->timezone));
		} else {
			$selectedDate = $now;
		}

		if ($hasTimeValue) {
			$selectedTime = new LocalDateTime($schema->value->time, new \DateTimeZone($schema->timezone));
		} else {
			$selectedTime = $now;
		}

		$uid = uniqid('cqda-datetime-picker-');
		$month = LocalDateTime::getMonthOf($selectedDate, $schema->firstDayOfWeek);
		$dateTimeInput = Element::div(['data-input-type' => 'datetime']);
		$actualDateTimeField = Element::input([
			'type' => 'hidden',
			'name' => $schema->name,
			'required' => $schema->constraints->required ?? false,
			'pattern' => $schema->constraints->pattern ?? '\d{2}/'
		]);
		$dateTimeField = Element::div([])->addClass('datetime-field');
		$dateField = Element::input([
			'type' => 'text',
			'placeholder' => explode(' ', $schema->placeholder, 2)[0],
			'required' => $schema->constraints->required ?? false
		])->addClass('date-field');
		$timeField = Element::input([
			'type' => 'text',
			'placeholder' => explode(' ', $schema->placeholder, 2)[1],
			'required' => $schema->constraints->required ?? false
		])->addClass('time-field');
		$togglePickerField = Element::input(['type' => 'button', 'popovertarget' => $uid, 'popovertargetaction' => 'toggle'])->addClass('toggle-picker-button');
		$dateTimePicker = Element::div(['popover' => 'auto', 'id' => $uid])->addClass('datetime-picker');
		$previousMonth = $selectedDate->sub(DateTimeInterval::from('P1M'))->format('Y-m-d');
		$nextMonth = $selectedDate->add(DateTimeInterval::from('P1M'))->format('Y-m-d');

		$datePickerHeader = Element::div()
			->addClass('header')
			->appendAllContent(
				Element::a(['href' => $url . '?date=' . $previousMonth, 'data-action' => 'previous-month'])
					->addClass('previous-month')
					->setContent('<<'),
				Element::p()->addClass('month-year')->setContent($selectedDate->format('F Y')),
				Element::a(['href' => $url . '?date=' . $nextMonth, 'data-action' => 'next-month'])
					->addClass('next-month')
					->setContent('>>')
			);
		$datePicker = Element::div([
			'data-has-selection' => $hasDateValue,
		])->addClass('date-picker')->appendAllContent($datePickerHeader);
		$table = Element::div()->addClass('content')->setStyles([
			'grid-template-columns' => 'repeat(7, 1fr)',
			'grid-template-rows' => 'min-content min-content repeat(' . count($month) + 1 . ', 1fr)',
		]);

		$timePickerHeader = Element::div()->addClass('header')->setContent('Time');
		$timePicker = Element::div([
			'data-has-selection' => $hasTimeValue,
		])->addClass('time-picker')->appendAllContent($timePickerHeader);

		// build day header cells
		foreach (LocalDateTime::generateDaysInWeek($schema->firstDayOfWeek) as $index => $dayOfWeek) {
			$table->appendContent(
				Element::div()
					->addClass('header-cell')
					->setStyles([
						'grid-column' => ($index + 1) . ' / span 1',
						'grid-row' => '1 / span 1',
					])
					->setContent(self::ISO_DAY_TO_SHORT_DAY_MAPPINGS[$dayOfWeek])
			);
		}

		$firstDayOfSecondWeek = $month[1][0];

		foreach ($month as $weekIndex => $daysInWeek) {
			foreach ($daysInWeek as $dayIndex => $day) {
				$a = Element::a([
					'href' => $url . '?date=' . $day->format('Y-m-d'),
				])->setContent($day->format('j'));
				$div = Element::div()
					->addClass('body-cell')
					->setStyles([
						'grid-row' => $weekIndex + 2 . ' / span 1',
						'grid-column' => $dayIndex + 1 . ' / span 1',
					]);

				// add a "leading-day" class to $div
				// if the day is in the first week of the month
				// and the day is before the first day of the week
				if ((int) $day->format('m') < (int) $firstDayOfSecondWeek->format('m')) {
					$div->addClass('leading-day');
				} elseif ((int) $day->format('m') > (int) $firstDayOfSecondWeek->format('m')) {
					$div->addClass('trailing-day');
				}

				if ($day->format('Y-m-d') === $now->add(new DateTimeInterval('P1D'))->format('Y-m-d')) {
					$div->addClass('tomorrow');
				} elseif ($day->format('Y-m-d') === $now->sub(new DateTimeInterval('P1D'))->format('Y-m-d')) {
					$div->addClass('yesterday');
				} elseif ($day->format('Y-m-d') === $now->format('Y-m-d')) {
					$div->addClass('today');
				}

				// date has been selected
				if ($hasDateValue && ($day->format('Y-m-d') === $selectedDate->format('Y-m-d'))) {
					$dateField->setAttribute('value', $day->format('d/m/Y'));
					$div->setAttribute('data-selected', true);
				}

				foreach ($schema->availability as $availableDate => $availableTimeSlots) {
					// only process dates that are in the current month
					if ($day->format('Y-m-d') === $availableDate) {
						$ul = Element::ul()->addClass('content');
						$lastAvailableTimeSlot = end($availableTimeSlots);
						$div->setAttribute('data-available', true);

						$lastAvailableTimeSlot = new LocalDateTime($availableDate . 'T' . $lastAvailableTimeSlot, $now->getTimezone());

						// remove "data-available" attribute if the last available time slot is in the past
						if ($now > $lastAvailableTimeSlot) {
							$div->removeAttribute('data-available');
						}

						// only process time slots for selected date
						if ($day->format('Y-m-d') === $selectedDate->format('Y-m-d')) {
							$markAsSelected = false;

							foreach ($availableTimeSlots as $timeSlot) {
								$timeSlot = new LocalDateTime($availableDate . 'T' . $timeSlot, $now->getTimezone());

								// mark next time slot as selected
								if ($hasTimeValue && ($selectedTime >= $timeSlot && $selectedTime < $timeSlot->add($interval))) {
									$markAsSelected = true;
								}

								// skip time slots in past
								if ($timeSlot < $now) {
									continue;
								}

								$li = Element::li()->addClass('time-slot')->appendAllContent(
									Element::a([
										'href' => $url . '?date=' . $day->format('Y-m-d') . '&time=' . $timeSlot->format('H:i'),
									])->setContent($timeSlot->format('H:i')),
								);

								// time has been picked as well
								if ($markAsSelected) {
									$timeField->setAttribute('value', $timeSlot->format('h:i A'));
									$li->setAttribute('data-selected', true);
									$actualDateTimeField->setAttribute('value', $selectedDate->format('Y-m-d') . 'T' . $selectedTime->format('H:i'));
									$markAsSelected = false;
									$dateTimePicker->setAttribute('data-has-selection', true);
								}

								$ul->appendContent($li);
							}

							$timePicker->appendContent($ul);
						}
					}
				}

				$div->appendAllContent($a);
				$table->appendContent($div);
			}
		}

		$datePicker->appendContent($table);
		$dateTimePicker->appendAllContent($datePicker, $timePicker);
		$dateTimeField->appendAllContent($dateField, $timeField, $togglePickerField);
		$dateTimeInput->appendAllContent($actualDateTimeField, $dateTimeField, $dateTimePicker);

		return $dateTimeInput;
	}

	private static function buildInputElementOfType(string $type, Field $field): Element
	{
		$multiline = $field->multiline ?? false;

		// textarea for multiline strings...
		if ($type === 'text' && $multiline) {
			$input = Element::textarea();
		} else {
			$input = Element::input(['type' => $type]);
		}

		$fieldType = $field->type->value;
		$input->addAttributes([
			'name' => $field->name,
			'placeholder' => $field->placeholder ?? '',
		]);

		foreach ($field->constraints as $name => $value) {
			// these constraints do not have equivalent HTML attributes
			if (in_array($name, ['unique', 'version'])) {
				continue;
			}

			// convert php regex to html regex
			if ($name === 'pattern') {
				// remove delimiters, accounting for modifiers
				$value = preg_replace('/^\/(.*)\/[a-zA-Z]*$/', '$1', $value);

				// remove beginning and end of string anchors
				$value = preg_replace('/^\\^|\\$$/', '', $value);
			}

			$input->setAttribute($name, $value);
		}

		// if (isset($field->autocomplete)) {
		// 	$input->setAttribute('autocomplete', $field->autocomplete);
		// }

		$hasValue = isset($field->value);

		if ($hasValue && $input->tagName === 'textarea') {
			$input->setContent($field->value);
		} elseif ($hasValue && $type !== 'password') {
			$input->setAttribute('value', $field->value);
		}

		return $input;
	}

	private static function buildInputElementForBoolean(Field $field): Element
	{
		return Element::input([
			'type' => 'checkbox',
			'name' => $field->name,
			'checked' => $field->value ?? $field->default ?? false
		]);
	}



	private static function buildLabel($schema): Element
	{
		return Element::label()->setContent($schema->label);
	}

	private static function buildErrors($schema): Element
	{
		$errors = Element::div()->addClass('errors');

		if (isset($schema->errors) && is_iterable($schema->errors)) {
			foreach ($schema->errors as $error) {
				$errors->appendContent(Element::p()->setContent($error));
			}
		}

		return $errors;
	}

	public static function createFromType(string $type, string $name, string $label): self
	{
		$label = Element::label()->setContent($label);
		$input = Element::input(['type' => $type, 'name' => $name]);
		$errors = Element::div()->addClass('errors');

		return new self($label, $input, $errors);
	}

	public function provideHint(string $hint): self
	{
		// $this->appendContent(Element::small($hint));

		return $this;
	}

	public function setPlaceholder(string $placeholder): self
	{
		$this->input->setAttribute('placeholder', $placeholder);
		return $this;
	}

	public function require(bool $required = true): self
	{
		$this->input->setAttribute('required', $required);
		return $this;
	}

	public function setConstraints(array|object $constraints): self
	{
		if (is_object($constraints)) {
			$constraints = (array) $constraints;
		}

		foreach ($constraints as $name => $value) {
			$this->input->setAttribute($name, $value);
		}

		return $this;
	}
}
