<?php
declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

$el = new Meraki\Html\Form\Field\Uuid(
	new Meraki\Html\Attribute\Name('uuid'),
	new Meraki\Html\Attribute\Label('uuid'),
	// [
	// 	'horse' => 'Horse',
	// 	'cat' => 'Cat',
	// 	'dog' => 'Dog',
	// 	'fish' => 'Fish',
	// ]
	// new Meraki\Html\Attribute\Required(),
	// Meraki\Html\Attribute\Policy::parse('unrestricted'),
	// Meraki\Html\Attribute\Min::of(2),
	// Meraki\Html\Attribute\Max::of(7),
	// Meraki\Html\Attribute\Step::inIncrementsOf('0.01'),
	new Meraki\Html\Attribute\Version(5)
);
// $el->defaultValue = 'dog';
$el->input('eae149cb-79a3-41fa-9c70-2951fa3bdf00');

$style = new Meraki\Html\Attribute\Style([
	'display' => 'flex',
	'flex-direction' => 'column',
	'max-width' => '14em',
]);

$el->attributes->add($style);

var_dump($el->errors);
echo (new Meraki\Html\FieldRenderer())->render($el);
