<?php
declare(strict_types=1);

namespace Meraki\Html\Form;

use Meraki\Html\Form\Field;
use Meraki\Html\Element;

final class TextInputRenderer
{
	public function supports(Element $el): bool
	{
		return $el instanceof Field\Text;
	}

	public function render(Element $field): string
	{
		if (!$this->supports($field)) {
			throw new \InvalidArgumentException(
				"TextInputRenderer does not support '{$field->tagName}' element."
			);
		}

		$field = $el;
		$label = $field->getLabel();
		$labelElement = new Element('label', ['for' => $field->getId()], $label);
		$input = new Element('input', $field->getAttributes());
		$wrapper = new Element('div', ['class' => 'form-group'], [$labelElement, $input]);

		return (string) $wrapper;
	}
}
