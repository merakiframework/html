<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

final class ValidationResult
{
	public function __construct(
		public bool $isValid,
		public mixed $value,
		/** @var string[] $errors */
		public array $errors,
	) {
	}

	public static function success(mixed $value): self
	{
		return self::passed($value);
	}

	public static function passed(mixed $value): self
	{
		return new self(true, $value, []);
	}

	public static function failed(mixed $value, string ...$errors): self
	{
		return new self(false, $value, $errors);
	}

	public static function guess(mixed $value, array $errors): self
	{
		return new self(count($errors) === 0, $value, $errors);
	}
}
