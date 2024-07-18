<?php
declare(strict_types=1);

namespace Meraki\Html;

class Renderer
{
	public function supports(Element $element): bool
	{
		return true;
	}

	public function render(Element $element): string
	{
		$str = '<' . $element->tagName . (string)$element->attributes . '>';

		if (!$element->isSelfClosing()) {
			$str .= $this->buildContent($element->children);
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
