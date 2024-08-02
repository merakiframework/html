<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

/**
 * The "domain" attribute is a custom attribute that can be
 * used to specify one or more domains that the user is allowed
 * to enter when entering a value into an input element.
 *
 * Typically, this attribute is used on the "url" and "email"
 * field types, but can be used on any field type.
 *
 * For example, if you have a backend system and you want to
 * restrict the email field to only accept email addresses from
 * your domain, you can use the "domain" attribute:
 *
 * 		$email = new Meraki\Html\Form\Field\Email(
 * 			new Meraki\Html\Attribute\Name('email'),
 * 			new Meraki\Html\Attribute\Label('Email:'),
 * 			Meraki\Html\Attribute\Domain::restrictTo('mycompany.com'),
 * 		);
 */
final class Domain extends Attribute
{
	public array $domains = [];

	public function __construct(string ...$domains)
	{
		$this->setName('domain');

		if (count($domains) > 0) {
			$this->add(...$domains);
		}
	}

	public static function restrictTo(string $domain, string ...$domains): self
	{
		return new self($domain, ...$domains);
	}

	/**
	 * Add one or more domains to the attribute.
	 */
	public function add(string $domain, string ...$domains): void
	{
		$this->domains = array_merge($this->domains, [$domain], $domains);
		$this->setValue($this->domains);
	}

	/**
	 * Remove one or more domains from the attribute.
	 */
	public function remove(string $domain, string ...$domains): void
	{
		$this->domains = array_diff($this->domains, [$domain], $domains);
		$this->setValue($this->domains);
	}

	/**
	 * Check if one or more domains exist in the attribute.
	 */
	public function exists(string $domain, string ...$domains): bool
	{
		$domains = [$domain, ...$domains];

		foreach ($domains as $domain) {
			if (!in_array($domain, $this->domains, true)) {
				return false;
			}
		}

		return true;
	}

	public function clear(): void
	{
		$this->domains = [];
		$this->setValue($this->domains);
	}

	/**
	 * Check if one or more domains exist in the attribute.
	 */
	public function contains(string $domain, string ...$domains): bool
	{
		$domains = [$domain, ...$domains];

		foreach ($domains as $domain) {
			if (!in_array($domain, $this->domains, true)) {
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
			throw new \InvalidArgumentException('The "domain" attribute can not be empty.');
		}

		parent::setValue($value);
	}
}
