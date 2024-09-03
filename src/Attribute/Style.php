<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

/**
 * Attribute for specifying one or more styles for an element.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/style
 */
final class Style extends Attribute
{
	private array $styles = [];

	public function __construct(array $styles = [])
	{
		$this->setName('style');

		if (count($styles) > 0) {
			$this->set($styles);
		}
	}

	/**
	 * Set one or more styles for the style attribute.
	 */
	public function set(string|array $name, null|string|int|\Stringable $value = null): self
	{
		if (is_array($name) && $value === null) {
			foreach ($name as $style => $value) {
				$this->set($style, $value);
			}

			return $this;
		}

		$this->styles[$name] = $value;

		$this->setValue($this->styles);

		return $this;
	}

	public function get(string $name): mixed
	{
		return $this->styles[$name] ?? null;
	}

	public function remove(string $style): self
	{
		unset($this->styles[$style]);
		$this->setValue($this->styles);

		return $this;
	}

	public function exists(string $style): bool
	{
		return array_key_exists($style, $this->styles);
	}

	protected function setValue(mixed $value): void
	{
		if (is_array($value)) {
			$value = $this->buildStyleString($value);
		}

		$value = trim($value);

		if (empty($value)) {
			throw new \InvalidArgumentException('The "style" attribute can not be empty.');
		}

		parent::setValue($value);
	}

	private function buildStyleString(array $styles): string
	{
		$str = '';

		foreach ($styles as $name => $value) {
			$str .= $name . ': ' . $value . ';';
		}

		return $str;
	}
}
