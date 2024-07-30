<?php
declare(strict_types=1);

namespace Meraki\Html;

final class DatePickerElement extends Element implements CustomElement
{
	public function __construct()
	{
		parent::__construct('date-time-picker');
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
}
