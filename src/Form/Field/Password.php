<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;

/**
 * Represents a password input field.
 */
final class Password extends Field
{
	public static array $allowedAttributes = [
		Attribute\Min::class,
		Attribute\Max::class,
		Attribute\Policy::class,
	];

	public function getType(): Attribute\Type
	{
		return new Attribute\Type('password');
	}

	public function getDefaultAttributes(): array
	{
		return [];
	}

	public function validate(mixed $value): ValidationResult
	{
		if (!is_string($value)) {
			return ValidationResult::failed('Password must be a string.');
		}

		$errors = [];
		$min = $this->attributes->find(Attribute\Min::class);
		$max = $this->attributes->find(Attribute\Max::class);
		$policy = $this->attributes->find(Attribute\Policy::class) ?? Attribute\Policy::unrestricted();

		if ($min !== null && mb_strlen($value) < $min->value) {
			$errors[] = 'Password must be at least ' . $min->value . ' characters long.';
		}

		if ($max !== null && mb_strlen($value) > $max->value) {
			$errors[] = 'Password cannot be more than ' . $max->value . ' characters long.';
		}

		if ($policy->requires('lowercase') && preg_match_all('/\p{Ll}/u', $value) < (int)$policy->get('lowercase')) {
			$errors[] = 'Password must contain at least ' . $policy->get('lowercase') . ' lowercase letter(s).';
		}

		if ($policy->requires('uppercase') && preg_match_all('/\p{Lu}/u', $value) < (int)$policy->get('uppercase')) {
			$errors[] = 'Password must contain at least ' . $policy->get('uppercase') . ' uppercase letter(s).';
		}

		if ($policy->requires('numbers') && preg_match_all('/\p{N}/u', $value) < (int)$policy->get('numbers')) {
			$errors[] = 'Password must contain at least ' . $policy->get('numbers') . ' number(s).';
		}

		if ($policy->requires('symbols') && preg_match_all('/\p{S}/u', $value) < (int)$policy->get('symbols')) {
			$errors[] = 'Password must contain at least ' . $policy->get('symbols') . ' symbol(s).';
		}

		return ValidationResult::guess($value, $errors);
	}

	private function getPolicyRequirements(string $policy): array
	{
		return match ($policy) {
			'strict' => [
				'numbers' => '1',
				'letters' => '1',
				'symbols' => '1',
				'consecutive' => '3',
				'sequential' => '2',
				'either' => 'consecutive,sequential',
			],
			'moderate' => [
				'numbers' => '1',
				'letters' => '1',
				'symbols' => '1',
			],
			'basic' => [
				'letters' => '1',
				'either' => 'numbers,symbols',
			],
			'relaxed' => [
				'letters' => '1',
			],
			'unrestricted' => [],
			default => throw new \InvalidArgumentException('Invalid policy: ' . $policy),
		};
	}
}
