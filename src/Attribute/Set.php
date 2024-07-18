<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Exception;
use Meraki\Html\Attribute;
use Meraki\Html\Exception\AttributesNotAllowed;
use Meraki\Html\Form\Field\Constraint;

class Set implements \Countable, \IteratorAggregate
{
	/**
	 * @var class-string[] $attributes The attributes in the set. An empty array means all attributes are allowed.
	 */
	private array $allowed = [];

	/**
	 * @var Attribute[] $attributes The attributes in the set.
	 */
	private array $attributes = [];

	/**
	 * Create a new set of attributes.
	 *
	 * If no attributes are provided, all attributes are allowed.
	 *
	 * @param class-string ...$allowed The Fully Qualified Class Names of the attributes to allow.
	 */
	public function __construct(string ...$allowed)
	{
		if (count($allowed) > 0) {
			$this->allow(...$allowed);
		}
	}

	public function get(string|Attribute $attribute): Attribute
	{
		$attr = $this->find($attribute);

		if ($attr === null) {
			$fqcn = $attribute instanceof Attribute ? $attribute::class : $attribute;

			throw new \RuntimeException('Could not find attribute "' . $fqcn . '" in set.');
		}

		return $attr;
	}

	/**
	 * Create a new set of attributes with the global attributes already added.
	 *
	 * @param class-string ...$allowed The Fully Qualified Class Names of additional attributes to allow.
	 */
	public static function useGlobal(string ...$allowed): self
	{
		if (count($allowed) === 0) {
			return (new self())->allowGlobal();
		}

		return (new self())->allowGlobal()->allow(...$allowed);
	}

	public function getConstraints(): self
	{
		$constraints = [];

		foreach ($this->attributes as $attribute) {
			if ($attribute instanceof Constraint) {
				$constraints[] = $attribute;
			}
		}

		return (new self(...$this->allowed))->add(...$constraints);
	}

	/**
	 * Retrieve a new subset of attributes containing the required attributes.
	 *
	 * @param class-string $attr The Fully Qualified Class Name of the attribute to include in the subset.
	 * @param class-string ...$attrs Additional attributes to include in the subset, using their Fully Qualified Class Names.
	 */
	public function subset(string ...$attrs): self
	{
		$subset = new self(...$this->allowed);

		foreach ($attrs as $attr) {
			$attribute = $this->find($attr);

			if ($attribute !== null) {
				$subset->add($attribute);
			}
		}

		return $subset;
	}

	/**
	 * Allow use of the global attributes in the set.
	 */
	public function allowGlobal(): self
	{
		return $this->allow(
			Attribute\Accesskey::class,
			Attribute\Class_::class,
			Attribute\Contenteditable::class,
			// 'dir',
			// 'draggable',
			Attribute\Hidden::class,
			Attribute\Id::class,
			// 'lang',
			// 'spellcheck',
			Attribute\Style::class,
			// 'tabindex',
			Attribute\Title::class,
			// 'translate'
		);
	}

	/**
	 * Create a new set of attributes with specific attributes allowed.
	 *
	 * @param class-string $attr The Fully Qualified Class Name of the attribute to allow.
	 * @param class-string ...$attrs The Fully Qualified Class Names of additional attributes to allow.
	 */
	public static function use(string ...$attrs): self
	{
		return new self(...$attrs);
	}

	/**
	 * Create a new set of attributes with all attributes allowed.
	 */
	public static function allowAny(): self
	{
		return new self();
	}

	/**
	 * Allow an attribute to be used in the set.
	 *
	 * If all attributes were initially allowed, using this method
	 * will automatically disallow all attributes except the ones
	 * specified. (aka goes from an unrestricted set to a restricted set.)
	 *
	 * @param class-string $attr The Fully Qualified Class Name of the attribute to allow.
	 * @param class-string ...$attrs Additional attributes to allow, using their Fully Qualified Class Names.
	 */
	public function allow(string ...$attrs): self
	{
		$this->allowed = array_merge($this->allowed, $attrs);

		return $this;
	}

	/**
	 * Disallow an attribute from being used in the set.
	 *
	 * @param class-string $attr The Fully Qualified Class Name of the attribute to disallow.
	 * @param class-string ...$attrs Additional attributes to disallow, using their Fully Qualified Class Names.
	 */
	public function disallow(string ...$attrs): self
	{
		$this->allowed = array_diff($this->allowed, $attrs);

		return $this;
	}

	/**
	 * Check if an attribute is allowed in the set.
	 *
	 * @param class-string|Attribute $attr The Fully Qualified Class Name of the attribute to check or an attribute instance.
	 * @param class-string|Attribute ...$attrs Additional attributes to check, using their Fully Qualified Class Names or attribute instances.
	 * @return bool True if all attributes provided are allowed, false otherwise.
	 */
	public function allowed(string|Attribute ...$attrs): bool
	{
		if (count($this->allowed) === 0) {
			return true;
		}

		$attrs = array_map(fn($attr) => $attr instanceof Attribute ? $attr::class : $attr, $attrs);

		foreach ($attrs as $attr) {
			if (!in_array($attr, $this->allowed, true)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if an attribute is not allowed in the set.
	 *
	 * @param class-string|Attribute $attr The Fully Qualified Class Name of the attribute to check or an attribute instance.
	 * @param class-string|Attribute ...$attrs Additional attributes to check, using their Fully Qualified Class Names or attribute instances.
	 */
	private function assertAllowed(string|Attribute ...$attrs): void
	{
		if (!$this->allowed(...$attrs)) {
			throw new AttributesNotAllowed(array_map(
				fn($attr) => $attr instanceof Attribute ? $attr::class : $attr,
				$attrs
			));
		}
	}

	/**
	 * Find an attribute by its class name.
	 *
	 * @param class-string $attr The fully qualified class name of the attribute to find.
	 */
	public function find(string|Attribute $attr): ?Attribute
	{
		if ($attr instanceof Attribute) {
			$attr = $attr::class;
		}

		foreach ($this->attributes as $attribute) {
			if ($attribute instanceof $attr) {
				return $attribute;
			}
		}

		return null;
	}

	/**
	 * Find an attribute by its class name or create a new instance of the attribute if it does not exist.
	 *
	 * @param class-string $attr The fully qualified class name of the attribute to find or create.
	 * @param array $args The arguments to pass to the attribute constructor if the attribute does not exist.
	 */
	public function findOrCreate(string $attr, callable $creator): Attribute
	{
		$this->assertAllowed($attr);

		$attribute = $this->find($attr);

		if ($attribute === null) {
			$attribute = $creator();
			$this->add($attribute);
		}

		return $attribute;
	}

	/**
	 * Set an attribute, replacing any existing attribute of the same class.
	 */
	public function set(Attribute ...$attrs): void
	{
		foreach ($attrs as $attribute) {
			$this->assertAllowed($attribute);
			$this->remove($attribute);
			$this->attributes[] = $attribute;
		}
	}

	/**
	 * Replace an attribute with another attribute, only if the attribute class exists in the set.
	 */
	public function replace(string|Attribute $attribute): void
	{
		$index = $this->indexOf($attribute);

		if ($index !== null) {
			$this->attributes[$index] = $attribute;
		}
	}

	/**
	 * Find an attribute by its name.
	 */
	public function findByName(string $name): ?Attribute
	{
		foreach ($this->attributes as $attribute) {
			if ($attribute->hasNameOf($name)) {
				return $attribute;
			}
		}

		return null;
	}

	/**
	 * Add an attribute to the set, if it does not already exist.
	 */
	public function add(Attribute ...$attrs): self
	{
		foreach ($attrs as $attribute) {
			$this->assertAllowed($attribute);

			if (!$this->contains($attribute)) {
				$this->attributes[] = $attribute;
			}
		}

		return $this;
	}

	public function exists(Attribute $attribute): bool
	{
		return $this->indexOf($attribute) !== null;
	}

	/**
	 * Check if an attribute exists in the set.
	 *
	 * @param class-string|Attribute $attribute The fully qualified class name or the attribute instance.
	 */
	public function contains(string|Attribute $attribute): bool
	{
		return $this->indexOf($attribute) !== null;
	}

	/**
	 * Remove an attribute from the set.
	 *
	 * @param class-string|Attribute $attribute The fully qualified class
	 * 		name of the attribute to remove or an attribute instance.
	 */
	public function remove(string|Attribute ...$attrs): self
	{
		foreach ($attrs as $attr) {
			$index = $this->indexOf($attr);

			if ($index !== null) {
				unset($this->attributes[$index]);
			}
		}

		return $this;
	}

	public function indexOf(string|Attribute $attribute): ?int
	{
		$attribute = $this->find($attribute);

		if ($attribute !== null) {
			foreach ($this->attributes as $index => $existingAttribute) {
				if ($attribute instanceof ($existingAttribute::class)) {
					return $index;
				}
			}
		}

		return null;
	}

	public function count(): int
	{
		return count($this->attributes);
	}

	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->attributes);
	}

	public function isEmpty(): bool
	{
		return $this->count() === 0;
	}

	public function clear(): self
	{
		$this->attributes = [];

		return $this;
	}

	public function __toString(): string
	{
		return array_reduce(
			$this->attributes,
			fn(string $attrs, Attribute $attr): string => $attrs . ' ' . $attr,
			''
		);
	}

	public function __clone(): void
	{
		$this->attributes = array_map(fn($attr) => clone $attr, $this->attributes);
	}
}
