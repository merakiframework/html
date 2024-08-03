<?php
declare(strict_types=1);

namespace Meraki\Html;
use Meraki\Html\Form\Field;

class Renderer
{
	private $submitButtonText = 'Submit';

	public function supports(Element $element): bool
	{
		return true;
	}

	public function changeSubmitButtonText(string $text): self
	{
		$this->submitButtonText = $text;

		return $this;
	}

	public function render(Element $element): string
	{
		if ($element instanceof Field) {
			return (new FieldRenderer())->render($element);
		}

		$str = '<' . $element->tagName . (string)$element->attributes . '>';

		if (!$element->isSelfClosing()) {
			if ($element instanceof Form) {
				$str .= $this->buildFormContent($element);
			} else {
				$str .= $this->buildContent($element->children);
			}
			$str .= '</' . $element->tagName . '>';
		}

		return $str;
	}

	protected function buildFormContent(Form $form): string
	{
		$content = '';

		foreach ($form->fields as $field) {
			$content .= $this->render($field);
		}

		// add submit button
		$content .= '<input type="submit" value="' . $this->escapeValue($this->submitButtonText) . '">';

		return $content;
	}

	protected function buildFieldContent(Form\Field $field): string
	{
		$content = '';

		foreach ($field->content as $field) {
			$content .= $this->render($field);
		}

		return $content;
	}

	protected function escapeValue(string $value): string
	{
		return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	private function buildContent(array $children): string
	{
		$content = '';

		foreach ($children as $el) {
			if ($el instanceof Element) {
				$content .= $this->render($el);
			} else {
				$content .= (string)$el;
			}
		}

		return $content;
	}
}
