<?php
declare(strict_types=1);

namespace Meraki\Html;

class OldField extends Element
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

	private const NAME_TO_AUTOCOMPLETE_MAPPINGS = [
		'name' => 'name',
		'full_name' => 'name',
		'fullname' => 'name',
		'firstname' => 'given-name',
		'first_name' => 'given-name',
		'lastname' => 'family-name',
		'last_name' => 'family-name',
		'email' => 'email',
		'phone' => 'tel',
		'mobile' => 'tel',
		'cell' => 'tel',
		'country' => 'country',
		'postcode' => 'postal-code',
		'zip' => 'postal-code',
		'birthday' => 'bday',
		'birth_day' => 'bday',
		'birthdate' => 'bday',
		'birth_date' => 'bday',
		'dob' => 'bday',
		'date_of_birth' => 'bday',
		'username' => 'username',
		'sex' => 'sex',
		'gender' => 'sex',
		'url' => 'url',
		'website' => 'url',
	];

	private const TYPE_TO_AUTOCOMPLETE_MAPPINGS = [
		'name' => 'name',
		'email' => 'email',
		'phone' => 'tel',
		'url' => 'url'
	];

	public function __construct(
		public Element $label,
		public Element $input,
		public Element $messages
	) {
		parent::__construct('div', [
			'class' => 'input'
		]);

		if ($label->getAttribute('for') === null && $input->getAttribute('id') !== null) {
			$label->addAttribute('for', $input->getAttribute('id'));
		} elseif ($label->getAttribute('for') !== null && $input->getAttribute('id') === null) {
			$input->addAttribute('id', $label->getAttribute('for'));
		} elseif ($label->getAttribute('for') === null && $input->getAttribute('id') === null) {
			$id = uniqid(spl_object_hash($this));
			$label->addAttribute('for', $id);
			$input->addAttribute('id', $id);
		} elseif ($label->getAttribute('for') !== $input->getAttribute('id')) {
			throw new \InvalidArgumentException('Label and input must have the same id');
		}

		$this->appendContent($label, $input, $messages);
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

	private static function buildInput($schema): Element
	{
		$fieldType = $schema->type;
		$input = match ($fieldType) {
			FormSchemaFieldType::enum => self::buildInputElementForEnum($schema),
			FormSchemaFieldType::boolean => self::buildInputElementForBoolean($schema),
			FormSchemaFieldType::string => self::buildInputElementOfType('text', $schema),
			FormSchemaFieldType::phone => self::buildInputElementOfType('tel', $schema),
			FormSchemaFieldType::email => self::buildInputElementOfType('email', $schema),
			FormSchemaFieldType::datetime => self::buildInputElementForDateTime($schema),
			FormSchemaFieldType::date => self::buildInputElementForDate($schema),
			FormSchemaFieldType::number => self::buildInputElementOfType('number', $schema),
			FormSchemaFieldType::integer => self::buildInputElementForInteger($schema),
			FormSchemaFieldType::uuid => self::buildInputElementOfType('text', $schema),
			FormSchemaFieldType::url => self::buildInputElementOfType('url', $schema),
			FormSchemaFieldType::name => self::buildInputElementOfType('text', $schema),
			FormSchemaFieldType::password => self::buildInputElementOfType('password', $schema),
			default => throw new \InvalidArgumentException('Cannot build input: Unknown property type "' . $fieldType->value . '"')
		};

		if (isset($schema->disabled) && $schema->disabled === true) {
			$input->addAttribute('disabled');
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
			$input->setAttribute('value', $schema->value);
		}

		return $input;
	}

	private static function buildInputElementForDateTime($schema): Element
	{
		$input = Element::input([
			'type' => 'datetime-local',
			'name' => $schema->name,
			'placeholder' => $schema->placeholder ?? '',
			'data-first-day-of-week' => $schema->firstDayOfWeek,
		]);

		foreach ((array) $schema->constraints as $name => $value) {
			$input->setAttribute($name, $value);
		}

		if (isset($schema->value)) {
			$input->setAttribute('value', $schema->value);
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

	private static function buildInputElementOfType(string $type, $schema): Element
	{
		$schema->multiline = $schema->multiline ?? false;

		// textarea for multiline strings...
		if ($type === 'text' && $schema->multiline === true) {
			$input = Element::textarea();
		} else {
			$input = Element::input(['type' => $type]);
		}

		$fieldType = $schema->type->value;
		$input->addAttributes([
			'name' => $schema->name,
			'placeholder' => $schema->placeholder ?? '',
			'autocomplete' => $schema->autocomplete
				?? self::NAME_TO_AUTOCOMPLETE_MAPPINGS[$schema->name]
				?? self::TYPE_TO_AUTOCOMPLETE_MAPPINGS[$fieldType]
				?? 'off'
		]);

		foreach ((array) $schema->constraints as $name => $value) {
			// these constraints do not have equivalent HTML attributes
			if (in_array($name, ['unique', 'version'])) {
				continue;
			}

			$input->setAttribute($name, $value);
		}

		// if (isset($schema->autocomplete)) {
		// 	$input->setAttribute('autocomplete', $schema->autocomplete);
		// }

		$hasValue = isset($schema->value);

		if ($hasValue && $input->tagName === 'textarea') {
			$input->setContent($schema->value);
		} elseif ($hasValue && $type !== 'password') {
			$input->setAttribute('value', $schema->value);
		}

		return $input;
	}

	private static function buildInputElementForBoolean($schema): Element
	{
		if (isset($schema->value) && $schema->value !== null) {
			$checked = $schema->value === 'on';
		} else {
			$checked = $schema->default ?? false;
		}

		$input = Element::input([
			'type' => 'checkbox',
			'name' => $schema->name,
			'checked' => $checked
		]);

		return $input;
	}

	private static function buildInputElementForEnum($schema): Element
	{
		$values = [];

		foreach ($schema->oneOf as $option) {
			if (isset($schema->value)) {
				$selected = $option->name === $schema->value;
			} else {
				$selected = $option->default ?? false;
			}

			$values[] = Element::option(['value' => $option->name, 'selected' => $selected])
				->setContent($option->label);
		}

		$fieldType = $schema->type->value;
		$input = Element::select([
			'name' => $schema->name,
			'autocomplete' => $schema->autocomplete
				?? self::NAME_TO_AUTOCOMPLETE_MAPPINGS[$schema->name]
				?? self::TYPE_TO_AUTOCOMPLETE_MAPPINGS[$fieldType]
				?? 'off'
		])->appendAllContent(...$values);

		return $input;
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

	public function __call(string $methodName, array $args): mixed
	{
		if (method_exists($this->input, $methodName)) {
			$returnValue = call_user_func_array([$this->input, $methodName], $args);
			return $this;
		}

		throw new \BadMethodCallException("Method {$methodName} does not exist");
	}
}
