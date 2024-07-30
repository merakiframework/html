<?php
declare(strict_types=1);

namespace Meraki\Html;

class Element
{
	public Attribute\Set $attributes;

	public array $children = [];

	private ?self $parent = null;

	public array $content = [];

	public function __construct(public string $tagName, ?Attribute\Set $attributes = null)
	{
		$this->attributes = $attributes ?? new Attribute\Set();
	}

	public function id(string $id = ''): self
	{
		if ($id === '') {
			$id = Attribute\Id::generateRandom();
		} else {
			$id = new Attribute\Id($id);
		}

		$this->attributes->add($id);

		return $this;
	}

	public function isSelfClosing()
	{
		return in_array($this->tagName, [
			'area',
			'base',
			'br',
			'col',
			'embed',
			'hr',
			'img',
			'input',
			'link',
			'meta',
			'param',
			'source',
			'track',
			'wbr'
		]);
	}

	public static function __callStatic(string $tagName, array $args): self
	{
		return new self($tagName, ...$args);
	}

	public function isRoot(): bool
	{
		return $this->parent === null;
	}

	public function hasParent(): bool
	{
		return $this->parent !== null;
	}

	public function hide(): self
	{
		$this->attributes->add(new Attribute\Hidden());

		return $this;
	}

	public function show(): self
	{
		$this->attributes->remove(new Attribute\Hidden());

		return $this;
	}

	public function createAndAppendElement(string $tagName): self
	{
		return $this->appendContent(new self($tagName, $this));
	}

	public function setParent(self $parent): self
	{
		return $this->setParentNode($parent);
	}

	public function setParentNode(self $parent): self
	{
		$this->parent = $parent;

		return $this;
	}

	// public function appendContent(string|self $node): self
	// {
	// 	$this->checkTagCanHaveContent();

	// 	$this->children[] = $node;

	// 	if ($node instanceof self) {
	// 		$node->setParent($this);
	// 		return $node;
	// 	}

	// 	return $this;
	// }

	public function appendContent(string|self ...$nodes): self
	{
		$this->checkTagCanHaveContent();

		$this->children = $this->content = [...$this->children, ...$nodes];

		foreach ($nodes as $node) {
			if ($node instanceof self) {
				$node->setParent($this);
			}
		}

		return $this;
	}

	public function setContent(string|self $node, string|self ...$nodes): self
	{
		$this->checkTagCanHaveContent();

		$this->children = [];

		foreach ([$node, ...$nodes] as $node) {
			if ($node instanceof self) {
				$node->setParent($this);
			}

			$this->children[] = $node;
		}

		return $this;
	}

	public function prependContent(string|self $node): self
	{
		$this->checkTagCanHaveContent();

		$this->children = array_merge([$node], $this->children);

		if ($node instanceof self) {
			$node->setParent($this);
			return $node;
		}

		return $this;
	}


	public function hasContent(): bool
	{
		return !empty($this->children);
	}

	public function __isset(string $name): bool
	{
		return $this->attributes->contains($name);
	}

	public function __get(string $name): mixed
	{
		return $this->attributes->findByName($name);
	}

	public function is(string $expectedTagName): bool
	{
		return strcasecmp($this->tagName, $expectedTagName) === 0;
	}

	public function destroy(): void
	{
		if ($this->hasParent()) {
			$this->parent = null;
			//$this->parent->removeChild($this);
		}

		foreach ($this->children as $child) {
			if ($child instanceof self) {
				$child->destroy();
			}
		}

		$this->children = [];
		$this->clearAttributes();
	}

	private function checkTagCanHaveContent(): void
	{
		if ($this->isSelfClosing()) {
			throw new \InvalidArgumentException('Element "' . $this->tagName . '" cannot have content');
		}
	}
}
