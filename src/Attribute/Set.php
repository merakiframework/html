<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;
use Meraki\Html\Exception\AttributesNotAllowed;
use Meraki\Html\Form\Field\Constraint;
use Exception;
use InvalidArgumentException;

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

	/**
	 * Looks for an attribute in the set and returns it if found while also
	 * removing it from the set.
	 */
	public function removeAndReturn(string|Attribute $attribute): ?Attribute
	{
		$index = $this->indexOf($attribute);

		if ($index !== null) {
			$attr = $this->attributes[$index];
			unset($this->attributes[$index]);

			return $attr;
		}

		return null;
	}

	public static function createFromSchema(array|object $schema, ?Factory $factory = null): self
	{
		if (is_array($schema) && array_is_list($schema)) {
			throw new \InvalidArgumentException('Schema must be an array of key=>value pairs or an object.');
		}

		$factory ??= new Factory();
		$schema = (array)$schema;
		$self = new self();

		foreach ($schema as $attributeName => $attributeValue) {
			$self->add($factory->create($attributeName, $attributeValue));
		}

		return $self;
	}

	public function __toArray(): array
	{
		return $this->attributes;
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

		$this->assertAllowed(...$this->allowed);

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
	 * @param class-string|string|Attribute $attr The fully qualified class name of the attribute to find.
	 */
	public function find(string|Attribute $attr): ?Attribute
	{
		$index = $this->indexOf($attr);

		return $index !== null ? $this->attributes[$index] : null;
	}

	/**
	 * Find an attribute by its class name or create a new instance of the attribute if it does not exist.
	 *
	 * @param class-string|string|Attribute $attr The fully qualified class name of the attribute to find or create.
	 * @param array $args The arguments to pass to the attribute constructor if the attribute does not exist.
	 */
	public function findOrCreate(string|Attribute $attr, mixed ...$creatorOrArgs): Attribute
	{
		$this->assertAllowed($attr);

		$attribute = $this->find($attr);

		if ($attribute === null) {
			$attribute = $this->createAttribute($attr, $creatorOrArgs);
			$this->add($attribute);
		}

		return $attribute;
	}

	private function createAttribute(string|Attribute $attr, array $creatorOrArgs): Attribute
	{
		// factory function provided
		if (isset($creatorOrArgs[0]) && is_callable($creatorOrArgs[0])) {
			return $creatorOrArgs[0]($this);
		}

		// create own factory based of attribute that could not be found, if it exists
		// and pass arguments to the factory
		$fqcn = $attr instanceof Attribute ? $attr::class : $attr;

		if (class_exists($fqcn)) {
			return new $fqcn(...$creatorOrArgs);
		}

		// finally create a new instance of the `Attribute` superclass and just pass name and value
		if (count($creatorOrArgs) === 2 && is_string($creatorOrArgs[0])) {
			return new Attribute($creatorOrArgs[0], $creatorOrArgs[1]);
		}

		// throw exception if no factory could be created
		throw new \RuntimeException('Could not create factory for attribute "' . $fqcn . '".');
	}

	/**
	 * Set an attribute, replacing any existing attribute of the same class.
	 */
	public function set(Attribute ...$attrs): self
	{
		foreach ($attrs as $attribute) {
			$this->assertAllowed($attribute);
			$this->remove($attribute::class);
			$this->attributes[] = $attribute;
		}

		return $this;
	}

	/**
	 * Replace an attribute with another attribute, only if the attribute class exists in the set.
	 */
	public function replace(string|Attribute $attribute): self
	{
		$index = $this->indexOf($attribute);

		if ($index !== null) {
			$this->attributes[$index] = $attribute;
		}

		return $this;
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

	public function exists(string|Attribute $attribute): bool
	{
		return $this->indexOf($attribute) !== null;
	}

	/**
	 * Check if an attribute exists in the set.
	 *
	 * Pass an instance to check if the exact instance (the attribute's equals() method) exists in the set.
	 * Pass a class name to check if the attribute is set.
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

	/**
	 * Check if an attribute exists in the set.
	 *
	 * You can check for an attribute by passing either the fully
	 * qualified class name, the attribute instance, or the attribute
	 * name.
	 *
	 * @param class-string|string|Attribute $attribute
	 * @return int|null
	 */
	public function indexOf(string|Attribute $attribute): ?int
	{
		// superclass is provided as a string
		if (is_string($attribute) && $attribute === Attribute::class) {
			throw new InvalidArgumentException('Cannot check for the "Meraki\\Html\\Attribute" superclass unless passed as instance.');
		}

		// attribute is provided as a subclass instance or subclass fqcn
		if (is_subclass_of($attribute, Attribute::class)) {
			return $this->getIndexUsingFullyQualifiedClassName($attribute instanceof Attribute ? $attribute::class : $attribute);
		}

		// attribute is provided as a string or the attribute superclass
		return $this->getIndexByAttributeName($attribute instanceof Attribute ? $attribute->name : $attribute);
	}

	private function getIndexByAttributeName(string $attributeName): ?int
	{
		foreach ($this->attributes as $index => $attr) {
			if ($attr->hasNameOf($attributeName)) {
				return $index;
			}
		}

		return null;
	}

	private function getIndexUsingFullyQualifiedClassName(string $fqcn): ?int
	{
		foreach ($this->attributes as $index => $attr) {
			if ($attr::class === $fqcn) {
				return $index;
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
		$str = '';

		/** @var Attribute $attribute */
		foreach ($this->attributes as $attribute) {
			// boolean attributes are special because they are either present for
			// true or absent for false. If the value is false, then the attribute
			// should not be rendered.
			if ($attribute instanceof Attribute\Boolean && $attribute->value === false) {
				continue;
			}

			$str .= ' ' . $attribute;
		}

		return $str;
	}

	public function __clone(): void
	{
		$this->attributes = array_map(fn($attr) => clone $attr, $this->attributes);
	}

	/**
	 * Asserts that the set contains the required attributes.
	 *
	 * @param class-string $attr The Fully Qualified Class Name of the attribute to require.
	 * @param class-string ...$attrs Additional attributes to require, using their Fully Qualified Class Names.
	 */
	public function require(string $attr, string ...$attrs): self
	{
		foreach ([$attr, ...$attrs] as $attr) {
			if (!$this->contains($attr)) {
				throw new Exception('Attribute "' . $attr . '" is required but not found in set.');
			}
		}

		return $this;
	}
}
