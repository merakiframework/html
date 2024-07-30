<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;

/**
 * Represents a number input field.
 *
 * A number field can be an integer, float, or scientific notation.
 *
 * DO NOT use a number field for telephone numbers, postal codes,
 * dates, etc... Use (or create your own) specialized field for
 * those types of data. Failing that, use a text field with the
 * appropriate constraint attributes set.
 *
 * Use the constraint attributes to set the number type if you need
 * to restrict the type of number allowed. For example:
 *	- to only allow integers, set the 'step' attribute to 1
 *	- to only allow positive numbers, set the 'min' attribute to 0
 *	- to only allow negative numbers, set the 'max' attribute to 0
 *	- to force floats, set the precision attribute to a positive
 *		integer (e.g. precision=1), then you have to enter a decimal
 *		like '1.0' instead of '1'.
 */
final class Number extends Field
{
	/**
	 * A number is valid if it is an integer, float, or in scientific notation.
	 *
	 * A number can:
	 *	- start with an optional sign (+ or -)
	 *	- have one or more digits
	 *	- have an optional decimal point followed by one or more digits
	 *	- have an optional exponent (e/E followed by an optional sign (+ or -) and one or more digits)
	 */
	public const VALIDATION_REGEX = '/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?$/';

	public static array $allowedAttributes = [
		Attribute\Min::class,
		Attribute\Max::class,
		Attribute\Step::class,
		Attribute\Precision::class,
	];

	public function getType(): Attribute\Type
	{
		return new Attribute\Type('number');
	}

	public function getDefaultAttributes(): array
	{
		return [];
	}

	public function validate(mixed $value): ValidationResult
	{
		// coerce integers/floats to a string, but only if no information is lost
		// this is to avoid floating point precision issues and to allow for large numbers
		if (is_int($value) || is_float($value)) {
			$value = $this->coerceToStringWithoutLoss($value);
		}

		// check value is string and a valid number
		if (!is_string($value) || !preg_match(self::VALIDATION_REGEX, $value)) {
			return ValidationResult::failed($value, 'Value is not a valid number.');
		}

		$errors = [];
		$min = $this->attributes->find(Attribute\Min::class);
		$max = $this->attributes->find(Attribute\Max::class);
		$step = $this->attributes->find(Attribute\Step::class);
		$precision = $this->attributes->find(Attribute\Precision::class);

		// get precision from step if precision attribute not set
		// @todo: if you can set precision from step, then is precision attribute even needed?
		if ($step !== null && $precision === null  && strpos($step->value, '.') !== false) {
			$precision = new Attribute\Precision(strlen(substr(strrchr($step->value, "."), 1)));
		}

		// check length constraints
		if ($min !== null && bccomp($value, $min->value) === -1) {
			$paddedValue = $this->padToPrecision($min->value, $precision);
			$errors[] = 'Number must be ' . $paddedValue . ' or higher.';
		}

		if ($max !== null && bccomp($value, $max->value) === 1) {
			$paddedValue = $this->padToPrecision($max->value, $precision);
			$errors[] = 'Number must be ' . $paddedValue . ' or lower.';
		}

		// check $value is in a valid increment of $step
		if ($step !== null) {
			$stepValue = $this->padToPrecision($step->value, $precision);

			if (!$this->isIncrementsOf($stepValue, $value)) {
				$errors[] = 'Number must be in increments of ' . $stepValue . '.';
			}
		}

		// check precision constraint
		if ($precision !== null) {
			$actualPrecision = strrchr($value, '.') !== false ? strlen(substr(strrchr($value, "."), 1)) : 0;

			if ($actualPrecision !== $precision->value) {
				$errors[] = $precision->value . ' decimal places of precision required: got ' . $actualPrecision . ' decimal places.';
			}
		}

		return ValidationResult::guess($value, $errors);
	}

	private function isIncrementsOf(string $step, string $value): bool
	{
		// Avoid division by zero
		if (bccomp($step, '0', 10) === 0) {
			return false;
		}

		$modulus = bcmod($value, $step, 10);

		return bccomp($modulus, '0', 10) === 0;
	}

	public function restrictRange(int|float $min, int|float $max): self
	{
		$this->attributes->set(new Attribute\Min($min));
		$this->attributes->set(new Attribute\Max($max));

		return $this;
	}

	public function stepInIncrementsOf(int|float|string $step): self
	{
		$this->attributes->set(new Attribute\Step((string)$step));

		return $this;
	}

	public function requirePrecisionOf(int $precision): self
	{
		$this->attributes->set(new Attribute\Precision($precision));

		return $this;
	}

	private function padToPrecision(string $value, ?Attribute\Precision $precision): string
	{
		if ($precision === null) {
			return $value;
		}

		$decimalPointPosition = strpos($value, '.');

		if ($decimalPointPosition === false) {
			$decimalPointPosition = strlen($value);
			$value .= '.';
		}

		$decimalPlaces = strlen($value) - $decimalPointPosition - 1;

		if ($decimalPlaces < $precision->value) {
			$padding = str_repeat('0', $precision->value - $decimalPlaces);
			$value .= $padding;
		}

		return $value;
	}

	private function coerceToStringWithoutLoss(int|float $value): string
	{
		$castedValue = (string)$value;

		// check if casted value is the same as original value
		if ($value === (int)$castedValue || $value === (float)$castedValue) {
			return $castedValue;
		}

		throw new \RuntimeException('Value cannot be safely cast to a string without loss.');
	}
}
