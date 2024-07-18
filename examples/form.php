<?php
declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

$el = new Meraki\Html\Form\Field\Money(
	new Meraki\Html\Attribute\Name('tester'),
	new Meraki\Html\Attribute\Label('On/Off'),
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
	// Meraki\Html\Attribute\Step::inIncrementsOf('10.05'),
);
// $el->defaultValue = 'dog';
// $el->input('fish');

$style = new Meraki\Html\Attribute\Style([
	'display' => 'flex',
	'flex-direction' => 'column',
	'max-width' => '14em',
]);

$el->attributes->add($style);

var_dump($el->errors);
echo (new Meraki\Html\FieldRenderer())->render($el);
