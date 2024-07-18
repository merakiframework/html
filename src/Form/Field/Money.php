<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;

final class Money extends Field
{
	public static array $allowedAttributes = [
		Attribute\Min::class,
		Attribute\Max::class,
		Attribute\Precision::class,
		Attribute\Currency::class,
		Attribute\Step::class,
	];

	public function getType(): Attribute\Type
	{
		return new Attribute\Type('money');
	}

	public function validate(mixed $value): ValidationResult
	{
		// using strings only to avoid floating point precision issues
		// also using strings over integers to allow for large numbers
		if (!is_string($value) || !preg_match('/^-?\d+(\.\d+)?$/', $value)) {
			return ValidationResult::failed($value, 'Value is not a valid monetary amount.');
		}

		// convert negative zero to positive zero
		// look for a negative zero, followed by a decimal point with 0 or more zeroes
		if (preg_match('/^-0+(\.0+)?$/', $value)) {
			// just remove negative sign
			// leave other zeroes as is
			$value = substr($value, 1);
		}

		$errors = [];
		$precision = $this->attributes->find(Attribute\Precision::class);
		$min = $this->attributes->find(Attribute\Min::class);
		$max = $this->attributes->find(Attribute\Max::class);

		if ($precision === null && $min !== null && $max !== null) {
			$precision = new Attribute\Precision($this->determinePrecision($min->value, $max->value));
		} elseif ($precision === null && $min !== null) {
			$precision = new Attribute\Precision($this->getPrecision($min->value));
		} elseif ($precision === null && $max !== null) {
			$precision = new Attribute\Precision($this->getPrecision($max->value));
		}

		// @todo: some times the browser will chop off trailing zeros (e.g. send "0.3" instead of "0.30")
		// even if step is set correctly (e.g. "0.01" requires 2 decimal places). This is a problem with
		// the browser, not neccessarily the user input. The server should be able to handle this case by
		// converting trailing zeros to the correct number of decimal places.

		if ($precision !== null) {
			$value = $this->padPrecision($value, $precision->value);

			$this->assertCorrectPrecision($precision->value, $min, $max);

			if ($this->getPrecision($value) !== $precision->value) {
				$errors[] = 'The amount needs to have ' . $precision->value . ' decimal places of precision.';
			}

			if ($min !== null && $this->compareAmounts($value, $min->value, $precision->value) === -1) {
				$errors[] = 'The amount needs to be '.$min->value.' or higher.';
			}

			if ($max !== null && $this->compareAmounts($value, $max->value, $precision->value) === 1) {
				$errors[] = 'The amount needs to be '.$max->value.' or lower.';
			}
		}

		// @todo: implement step attribute

		return ValidationResult::guess($value, $errors);
	}

	/**
	 * Pad the monetary amount to the correct precision.
	 *
	 * This method is used to pad a monetary amount to the correct precision.
	 * For example, if the amount is '1.0' and the precision is 2, then the
	 * amount will be padded to '1.00'.
	 */
	private function padPrecision(string $amount, int $precision): string
	{
		$decimalPointPosition = strpos($amount, '.');

		if ($decimalPointPosition === false) {
			$amount .= '.';
			$decimalPointPosition = strlen($amount);
		}

		$amount .= str_repeat('0', $precision - (strlen($amount) - $decimalPointPosition - 1));

		return $amount;
	}

	private function assertCorrectPrecision(int $places, ?Attribute ...$attrs): void
	{
		foreach ($attrs as $attr) {
			if ($attr !== null && $this->getPrecision($attr->value) !== $places) {
				throw new \InvalidArgumentException(sprintf(
					'The "%s" attribute needs to have %d decimal places of precision: %d given.',
					$attr->name,
					$places,
					$this->getPrecision($attr->value)
				));
			}
		}
	}

	/**
	 * Compare two monetary amounts with precision.
	 *
	 * For example, if $precision is 2, then
	 * 		- '1.00' and '1.01' are considered not equal
	 * 		- '1.00' is less than '1.01'
	 * 		- '1.01' is greater than '1.00'
	 * 		- '1.00' and '1.001' are considered equal
	 */
	private function compareAmounts(string $amount1, string $amount2, int $precision): int
	{
		$amount1 = bcadd($amount1, '0', $precision);
		$amount2 = bcadd($amount2, '0', $precision);

		return bccomp($amount1, $amount2, $precision);
	}

	/**
	 * Determine the precision of two monetary amounts.
	 *
	 * This method is used to determine the precision of two monetary amounts
	 * when the precision is not provided to the compareAmounts() method.
	 *
	 * The precision is determined by the number of decimal places in the two
	 * amounts. If the two amounts do not have the same number of decimal
	 * places, then an exception is thrown.
	 */
	private function determinePrecision(string $amount1, string $amount2): int
	{
		$precision1 = $this->getPrecision($amount1);
		$precision2 = $this->getPrecision($amount2);

		if ($precision1 !== $precision2) {
			throw new \InvalidArgumentException('The min and max attribute values must have the same precision.');
		}

		return $precision1;
	}

	/**
	 * Get the precision of a monetary amount.
	 *
	 * This method is used to determine the precision of a monetary amount.
	 * The precision is determined by the number of decimal places in the
	 * amount. For example, '1.00' has a precision of 2, '1.0' has a precision
	 * of 1, and '1' has a precision of 0.
	 */
	private function getPrecision(string $amount): int
	{
		$decimalPointPosition = strpos($amount, '.');

		if ($decimalPointPosition === false) {
			return 0;
		}

		return strlen($amount) - $decimalPointPosition - 1;
	}

	public function getDefaultAttributes(): array
	{
		return [
			// new Attribute\Min('0.00'),
			// new Attribute\Max(self::MAX_AMOUNT),
			new Attribute\Precision(2),
			// new Attribute\Currency('AUD'),
		];
	}
}
