<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

/**
 * The "protocol" attribute is a custom attribute that can be
 * used to specify one or more protocols that the user is allowed
 * to enter when entering a value into an input element.
 *
 * Typically, this attribute is used on the "url" field type,
 * but can be used on any field type.
 *
 * For example, to restrict the url field to only accept "http"
 * and "https" protocols:
 *
 * 		$url = new Meraki\Html\Form\Field\Url(
 * 			new Meraki\Html\Attribute\Name('website'),
 * 			new Meraki\Html\Attribute\Label('Website:'),
 * 			Meraki\Html\Attribute\Protocols::restrictTo('http', 'https'),
 * 		);
 */
final class Protocol extends Attribute
{
	public array $protocols = [];

	public function __construct(string ...$protocols)
	{
		$this->setName('protocol');

		if (count($protocols) > 0) {
			$this->add(...$protocols);
		}
	}

	public static function restrictTo(string $protocol, string ...$protocols): self
	{
		return new self($protocol, ...$protocols);
	}

	/**
	 * Add one or more protocols to the attribute.
	 */
	public function add(string $protocol, string ...$protocols): void
	{
		$this->protocols = array_merge($this->protocols, [$protocol], $protocols);
		$this->setValue($this->protocols);
	}

	/**
	 * Remove one or more protocols from the attribute.
	 */
	public function remove(string $protocol, string ...$protocols): void
	{
		$this->protocols = array_diff($this->protocols, [$protocol], $protocols);
		$this->setValue($this->protocols);
	}

	/**
	 * Check if one or more protocols exist in the attribute.
	 */
	public function exists(string $protocol, string ...$protocols): bool
	{
		$protocols = [$protocol, ...$protocols];

		foreach ($protocols as $protocol) {
			if (!in_array($protocol, $this->protocols, true)) {
				return false;
			}
		}

		return true;
	}

	public function clear(): void
	{
		$this->protocols = [];
		$this->setValue($this->protocols);
	}

	/**
	 * Check if one or more protocols exist in the attribute.
	 */
	public function contains(string $protocol, string ...$protocols): bool
	{
		$protocols = [$protocol, ...$protocols];

		foreach ($protocols as $protocol) {
			if (!in_array($protocol, $this->protocols, true)) {
				return false;
			}
		}

		return true;
	}

	protected function setValue(mixed $value): void
	{
		if (is_array($value)) {
			$value = implode(' ', $value);
		}

		$value = trim($value);

		if (empty($value)) {
			throw new \InvalidArgumentException('The "protocol" attribute can not be empty.');
		}

		parent::setValue($value);
	}
}
