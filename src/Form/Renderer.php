<?php
declare(strict_types=1);

namespace Meraki\Html\Form;

use Meraki\Html\Element;
use Meraki\Html\Form;
use Meraki\Html\Form\FieldRenderer;

final class Renderer
{
	private string $buttonText = 'Save';

	public function changeButtonText(string $text): void
	{
		$this->buttonText = $text;
	}

	public function supports(Element $el): bool
	{
		return $el->is('form');
	}

	public function render(Element $form): string
	{
		if (!$this->supports($form)) {
			throw new \InvalidArgumentException('Element must be a form');
		}

		if (!in_array($form->method, ['GET', 'POST'])) {
			// method spoofing...
			$formMethod = 'POST';
		} else {
			$formMethod = $form->method;
		}

		$el = new Element('form', [
			'name' => $form->name,
			'action' => $form->action,
			'method' => $formMethod,
			'data-method' => $form->method,	// actual method
		]);

		if ($form->noValidate) {
			$el->setAttribute('novalidate', 'novalidate');
		}

		// add hidden field for method spoofing
		if ($formMethod !== $form->method) {
			$el->appendContent(Element::input([
				'type' => 'hidden',
				'name' => '_method',
				'value' => $form->method,	// actual method
			]));
		}

		foreach ($form->fields as $field) {
			$renderedField = (new FieldRenderer())->render($field);
			$el->appendContent($renderedField);
		}

		$formActions = Element::div()->addClass('button-group form-actions');

		$formActions->appendContent(Element::input(['type' => 'submit', 'value' => $this->buttonText]));

		if ($form->canBeCancelled) {
			$formActions->appendContent(Element::a(['href' => $form->cancelAction]))
				->setContent('Cancel')
				->addClass('button cancel');
		}

		if ($form->canBeReset) {
			$formActions->appendContent(Element::input(['type' => 'reset', 'value' => 'Reset'])->addClass('button'));
		}

		$el->appendContent($formActions);

		return $el->render();
	}

	public function group(Element $legend, Element $node, Element ...$nodes): Element
	{
		if ($legend->tagName !== 'legend') {
			throw new \InvalidArgumentException('First argument must be a legend element for fieldset');
		}

		$fieldset = Element::fieldset()->addContent(Element::legend($node));

		foreach ([$node, ...$nodes] as $node) {
			$fieldset->appendContent($node);
		}

		return $fieldset;
	}
}
