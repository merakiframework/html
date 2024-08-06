<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;
use Meraki\Html\Form\Field\Constraint;

/**
 * Typical policy properties include:
 * - numbers: the minimum number of numbers required
 * - uppercase: the minimum number of uppercase letters required
 * - lowercase: the minimum number of lowercase letters required
 * - symbols: the minimum number of symbols required
 * - letters: shorthand for defining both uppercase and lowercase
 * 		letters required
 * - either: makes it so that at least one of the specified character
 * 		types is required instead of all being required
 * - consecutive: the maximum number of consecutive characters allowed (e.g. aaa or 111)
 * - sequential: the maximum number of sequential characters allowed (e.g. abc or 123)
 */
final class Policy extends Attribute implements Constraint
{
	public const PREDEFINED_RULE_SETS = ['strict', 'moderate', 'basic', 'relaxed', 'unrestricted'];

	public function __construct(private array|string $rules = [])
	{
		$this->setName('policy');

		// @todo: this logic belongs in the attribute factory
		if (is_string($rules)) {
			$rules = self::parse(trim($rules));
			$this->setValue($rules->value);
		} elseif (is_array($rules) && count($rules) > 0) {
			$this->applyRules($rules);
		} else {
			$this->setValue('');
		}
	}

	private function applyRules(array $rules): void
	{
		foreach ($rules as $property => $value) {
			$this->update($property, $value);
		}
	}

	/**
	 * A strict policy requires a password to have at least one of
	 * each character type, and must not have any more than 3 consecutive
	 * characters or 2 sequential characters.
	 *
	 * Invalid examples:
	 * 	- "password123" (no symbols, no uppercase, contains more than 2 sequential characters)
	 * 	- "password" (no numbers, no symbols, no uppercase)
	 * 	- "Pa55word!!!!" (more than 3 consecutive characters "!!!!")
	 * 	- "abc111" (no symbols, no uppercase, neither consecutive nor sequential requirement met)
	 */
	public static function strict(): self
	{
		return new self([
			'numbers' => '1',
			'letters' => '1',
			'symbols' => '1',
			'consecutive' => '3',
			'sequential' => '2',
			'either' => 'consecutive,sequential',
		]);
	}

	/**
	 * A moderate policy requires a password to have at least one of
	 * each character type.
	 */
	public static function moderate(): self
	{
		return new self([
			'numbers' => '1',
			'letters' => '1',
			'symbols' => '1',
		]);
	}

	/**
	 * A basic policy requires a password to have at least uppercase
	 * and lowercase letters, and at least one number or at least one
	 * symbol.
	 */
	public static function basic(): self
	{
		return new self([
			'letters' => '1',
			'either' => 'numbers,symbols',
		]);
	}

	/**
	 * A relaxed policy requires a password to have at least one
	 * uppercase character and one lowercase character.
	 */
	public static function relaxed(): self
	{
		return new self([
			'letters' => '1',
		]);
	}

	/**
	 * An unrestricted policy allows a password to have any combination
	 * of characters.
	 */
	public static function unrestricted(): self
	{
		return new self();
	}

	public static function parse(string $policy): self
	{
		return match ($policy) {
			'strict' => self::strict(),
			'moderate' => self::moderate(),
			'basic' => self::basic(),
			'relaxed' => self::relaxed(),
			'unrestricted', '' => self::unrestricted(),
			default => self::custom($policy),
		};
	}

	public static function custom(array|string $rules): self
	{
		if (is_string($rules)) {
			if (strlen($rules) === 0) {
				return self::unrestricted();
			}

			$parts = [];

			foreach (explode(';', $rules, 2) as $part) {
				if (strpos($part, ':') === false) {
					throw new \InvalidArgumentException('The policy must be a string of CSS-like properties.');
				}

				[$property, $value] = explode(':', $part, 2);
				$parts[trim($property)] = trim($value);
			}

			$rules = $parts;
		}

		return new self($rules);
	}

	private function updateValue(): void
	{
		$built = '';

		foreach ($this->rules as $property => $value) {
			$built .= $property . ': ' . $value . '; ';
		}

		$this->setValue($built);
	}

	public function update(string $property, string $value): void
	{
		if (!preg_match('/^[a-z-]+$/', $property)) {
			throw new \InvalidArgumentException('The property must be a string of lowercase letters and hyphens.');
		}

		if (!preg_match('/^[a-zA-Z0-9-]+$/', $value)) {
			throw new \InvalidArgumentException('The value must be a string of alphanumeric characters and hyphens.');
		}

		if ($property === 'letters') {
			$this->rules['uppercase'] = $value;
			$this->rules['lowercase'] = $value;
			$this->updateValue();
			return;
		}

		$this->rules[$property] = $value;
		$this->updateValue();
	}

	public function find(string $property): ?string
	{
		if (!$this->requires($property)) {
			return null;
		}

		return $this->rules[$property];
	}

	public function get(string $property): string
	{
		if (!$this->requires($property)) {
			throw new \InvalidArgumentException('The policy does not have a "' . $property . '" property.');
		}

		return $this->rules[$property];
	}

	public function remove(string $property): void
	{
		if (!$this->requires($property)) {
			return;
		}

		unset($this->rules[$property]);
		$this->updateValue();
	}

	public function requires(string $property): bool
	{
		return isset($this->rules[$property]);
	}

	public function setValue(bool|int|string|\Stringable|null $value): void
	{
		if (!is_string($value)) {
			throw new \InvalidArgumentException('The policy must be a string.');
		}

		// policy rules have the sme format as css style properties
		// e.g. "color: red; background-color: blue;"
		if (mb_strlen($value) > 0 && !preg_match('/^(?:[a-z-]+: ?[a-zA-Z0-9-]+; ?)+$/', $value)) {
			throw new \InvalidArgumentException('The policy must be a string of CSS-like properties.');
		}

		parent::setValue($value);
	}
}
