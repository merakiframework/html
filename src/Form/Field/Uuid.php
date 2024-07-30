<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Ramsey\Uuid\Exception\UuidExceptionInterface;

final class Uuid extends Field
{
	public static array $allowedAttributes = [
		Attribute\Version::class,	// 'any', 1, 2, 3, 4, 5, 6, 7, or 8
		Attribute\Placeholder::class,
	];

	public function getDefaultAttributes(): array
	{
		return [
			Attribute\Version::any(),
		];
	}

	public function getType(): Attribute\Type
	{
		return new Attribute\Type('uuid');
	}

	public function validate(mixed $value): ValidationResult
	{
		if (!is_string($value)) {
			return ValidationResult::failed('UUID must be a string.');
		}

		try {
			$value = RamseyUuid::fromString($value);
			$version = $this->attributes->find(Attribute\Version::class) ?? Attribute\Version::any();

			if ($version->value !== 'any' && $version->value !== $value->getVersion()) {
				return ValidationResult::failed($value, "A version {$version->value} UUID is required: Version {$value->getVersion()} UUID provided.");
			}
		} catch (UuidExceptionInterface $e) {
			return ValidationResult::failed($value, 'Invalid UUID format: '.$e->getMessage());
		}

		return ValidationResult::success($value);
	}
}
