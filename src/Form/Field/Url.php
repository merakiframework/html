<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;

final class Url extends Field
{
	public static array $allowedAttributes = [
		Attribute\Min::class,	// URLs must have at least 1 character
		Attribute\Max::class,	// URLs must be less than 2048 characters
		Attribute\Type::class,	// 'absolute', 'relative', or 'any'
		Attribute\Placeholder::class,
	];

	public function getType(): Attribute\Type
	{
		return new Attribute\Type('url');
	}

	public function getDefaultAttributes(): array
	{
		return [
			// new Attribute\Min(1),
			// new Attribute\Max(2048),
			// new Attribute\Type('absolute'),
			new Attribute\Autocomplete('url'),
		];
	}

	public function validate(mixed $value): ValidationResult
	{
		if (!is_string($value)) {
			return ValidationResult::failed('Value must be a string.');
		}

		$minLengthForType = $this->getMinLengthForType();

		$this->constraints->assert(
			'min',
			fn(mixed $min) => $min >= $minLengthForType,
			'Minimum length for URL type "' . $this->constraints->type . '" is ' . $minLengthForType . ' characters.'
		);

		$errors = [];

		if ($this->constraints->type === 'absolute' && !$this->isAbsoluteUrl($value)) {
			$errors[] = 'URL must start with a scheme and have a host (e.g. "http://example.com").';
		} elseif ($this->constraints->type === 'relative' && !$this->isRelativeUrl($value)) {
			$errors[] = 'URL must start with a path (e.g. "/path/to/resource").';
		} elseif ($this->constraints->type === 'any' && !$this->isAnyUrl($value)) {
			$errors[] = 'URL must be an absolute or relative URL. (e.g. "http://example.com" or "/path/to/resource").';
		} else {
			throw new \InvalidArgumentException('Invalid URL type constraint.');
		}

		// check length constraints
		if (mb_strlen($value) < $this->constraints->min) {
			$errors[] = 'Value must be more than ' . $this->constraints->min . ' characters long.';
		}

		if (mb_strlen($value) > $this->constraints->max) {
			$errors[] = 'Value must be less than ' . $this->constraints->max . ' characters long.';
		}

		return ValidationResult::guess($value, $errors);
	}

	/**
	 * An absolute URI has a scheme, host, path, and optional query.
	 *
	 * The spec does not allow for a fragment in the uri.
	 *
	 * absolute-URI = scheme ":" hier-part [ "?" query ]
	 * hier-part    = "//" authority path-abempty / path-absolute / path-rootless / path-empty
	 */
	private function isAbsoluteUrl(string $value): bool
	{
		$parts = self::mb_parse_uri($value);

		// path is always present, and query is optional
		return $parts->scheme !== null		// scheme is required
			&& $parts->authority !== null	// authority is required
			&& $parts->fragment === null;	// fragment is not allowed
	}

	/**
	 * A relative URI has an authority, path, optional query, and optional fragment.
	 *
	 * relative-ref  = relative-part [ "?" query ] [ "#" fragment ]
	 * relative-part = "//" authority path-abempty / path-absolute / path-noscheme / path-empty
	 */
	private function isRelativeUrl(string $value): bool
	{
		$parts = self::mb_parse_uri($value);

		return $parts->scheme === null && $parts->authority !== null;
	}

	/**
	 * A URL can be either an absolute or relative URL.
	 */
	private function isAnyUrl(string $value): bool
	{
		return $this->isAbsoluteUrl($value) || $this->isRelativeUrl($value);
	}

	private static function mb_parse_uri(string $uri): \stdClass
	{
		// split the URI into parts
		$uri_regex = '/^(([^:\/?#]+):)?(\/\/([^\/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?/';
		$result = preg_match($uri_regex, $uri, $matches);

		if ($result === false) {
			throw new \RuntimeException('Failed to parse URI.');
		}

		return (object)[
			'scheme' => $matches[2] ?? null,
			'authority' => $matches[4] ?? null,
			'path' => $matches[5],	// path is always present
			'query' => $matches[7] ?? null,
			'fragment' => $matches[9] ?? null,
		];
	}

	private function getMinLengthForType(): int
	{
		$type = $this->constraints->type;

		return match ($type) {
			'absolute' => 5,	// 1 (scheme) + 3 (://) + 1 (host)
			'relative' => 1,	// 1 (path)
			'any' => 1,			// 1 (path)
			default => throw new \InvalidArgumentException('Invalid URL type constraint.'),
		};
	}
}
