<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;
use Brick\DateTime\LocalDateTime;
use Brick\DateTime\Parser\DateTimeParseException;
use Brick\DateTime\Duration;

final class DateTime extends Field
{
	public static array $allowedAttributes = [
		Attribute\Min::class,
		Attribute\Max::class,
		Attribute\Step::class,
		Attribute\FirstDayOfWeek::class,
		Attribute\Availability::class,	// @todo: implement this
	];
	public function getType(): Attribute\Type
	{
		return new Attribute\Type('date-time');
	}

	public function validate(mixed $value): ValidationResult
	{
		$errors = [];
		$min = $this->toLocalDateTime($this->attributes->find(Attribute\Min::class));
		$max = $this->toLocalDateTime($this->attributes->find(Attribute\Max::class));
		$step = $this->toDuration($this->attributes->find(Attribute\Step::class));

		try {
			$value = LocalDateTime::parse($value);

			if ($min !== null && $value->isBefore($min)) {
				$errors[] = 'Value must be at or after '.(string)$min.'.';
			}

			if ($max !== null && $value->isAfter($max)) {
				$errors[] = 'Value must be at or before ' . (string)$max . '.';
			}

			if ($step !== null && $min !== null && !$this->isIntervalOf($min, $value, $step)) {
				$errors[] = 'Value must be an interval of ' . (string)$step . ' starting from ' . (string)$min . '.';
			}

			return ValidationResult::guess($value, $errors);
		} catch (DateTimeParseException $e) {
			return ValidationResult::failed($value, 'Date and time must be provided in a format compatible with ISO 8601.');
		}
	}

	/**
	 * Check if $current is an interval of $duration starting from $start.
	 */
	private function isIntervalOf(LocalDateTime $start, LocalDateTime $current, Duration $duration): bool
	{
		while ($start->isBeforeOrEqualTo($current)) {
			if ($start->isEqualTo($current)) {
				return true;
			}

			$start = $start->plusDuration($duration);
		}

		return false;
	}

	private function toLocalDateTime(?Attribute $attr): ?LocalDateTime
	{
		if ($attr === null) {
			return null;
		}

		return LocalDateTime::parse($attr->value);
	}

	private function toDuration(?Attribute $attr): ?Duration
	{
		if ($attr === null) {
			return null;
		}

		return Duration::parse($attr->value);
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
