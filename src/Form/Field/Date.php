<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;
use Brick\DateTime\LocalDate;
use Brick\DateTime\Period;
use Brick\DateTime\Parser\DateTimeParseException;

final class Date extends Field
{
	public static array $allowedAttributes = [
		Attribute\Min::class,
		Attribute\Max::class,
		Attribute\Step::class,
		Attribute\FirstDayOfWeek::class,
	];

	public function getType(): Attribute\Type
	{
		return new Attribute\Type('date');
	}

	public function validate(mixed $value): ValidationResult
	{
		$errors = [];
		$min = $this->toLocalDate($this->attributes->find(Attribute\Min::class));
		$max = $this->toLocalDate($this->attributes->find(Attribute\Max::class));
		$step = $this->toPeriod($this->attributes->find(Attribute\Step::class));

		try {
			$value = LocalDate::parse($value);

			if ($min !== null && $value->isBefore($min)) {
				$errors[] = 'Value must be at or after ' . (string) $min . '.';
			}

			if ($max !== null && $value->isAfter($max)) {
				$errors[] = 'Value must be at or before ' . (string) $max . '.';
			}

			if ($step !== null && $min !== null && !$this->isIntervalOf($min, $value, $step)) {
				$errors[] = 'Value must be an interval of ' . (string) $step . ' starting from ' . (string) $min . '.';
			}

			return ValidationResult::guess($value, $errors);
		} catch (DateTimeParseException $e) {
			return ValidationResult::failed($value, 'Date must be provided in a format compatible with ISO 8601.');
		}
	}

	/**
	 * Check if $current is an interval of $period starting from $start.
	 */
	private function isIntervalOf(LocalDate $start, LocalDate $current, Period $period): bool
	{
		while ($start->isBeforeOrEqualTo($current)) {
			if ($start->isEqualTo($current)) {
				return true;
			}

			$start = $start->plusPeriod($period);
		}

		return false;
	}

	private function toLocalDate(?Attribute $attr): ?LocalDate
	{
		if ($attr === null) {
			return null;
		}

		return LocalDate::parse($attr->value);
	}

	private function toPeriod(?Attribute $attr): ?Period
	{
		if ($attr === null) {
			return null;
		}

		return Period::parse($attr->value);
	}

	public function weekStartsOn(int $day): self
	{
		$this->attributes->add(new Attribute\FirstDayOfWeek($day));

		return $this;
	}

	public function getDefaultAttributes(): array
	{
		return [];
	}
}
