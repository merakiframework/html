<?php
declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

final class FieldSchemaIdentity
{
	public string $type = Meraki\Html\Form\Field\Uuid::class;
	public string $name = 'id';
	public string $label = 'ID';
	public bool $required = false;
}

final class FieldSchemaService
{
	public string $type = Meraki\Html\Form\Field\Enum::class;
	public string $name = 'service';
	public string $label = 'Service';
	public string $hint = 'The type of service you would like to book.';
	public bool $required = true;

	public array $options = [
		'car-hire' => 'Car Hire',
		'car-lesson' => 'Car Lesson',
		'motorcycle-lesson' => 'Motorcycle Lesson',
		'q-ride' => 'Q-Ride',
		'truck-lesson' => 'Truck Lesson',
	];
}

final class FieldSchemaBookingStartsAt
{
	public string $type = Meraki\Html\Form\Field\DateTime::class;
	public string $name = 'booking_starts_at';
	public string $label = 'Starts At';
	public string $hint = 'The date and time that the lesson starts at.';
	public bool $required = true;

	public string $min = '2024-07-03T13:57:00';
	public string $max = '2025-07-03T14:57:00';

	public string $step = '3600';	// 1 hour
	// public string $value = '2024-07-03T13:57:00';
}

final class FieldSchemaBookingValue
{
	public string $type = Meraki\Html\Form\Field\Money::class;
	public string $name = 'booking_value';
	public string $label = 'Value';
	// public string $placeholder = 'E.g. $150.00';
	public string $hint = 'How much is the booking worth?';

	public bool $required = true;
	public string $currency = 'AUD';
	public int $precision = 2;
}

final class FieldSchemaName
{
	public string $type = Meraki\Html\Form\Field\Name::class;
	public string $name = 'name';
	public string $label = 'Name';
	public string $hint = 'Your full name as it appears on your driver\'s licence.';
	public bool $required = false;
	// public ?string $value = null;
	// public string $content = 'John Doe';

	public bool $disabled = false;
}

$nameField = Meraki\Html\Form\Field::createFromSchema(new FieldSchemaName());
//->makeLinkable('/users/1234', 'User 1234');

$form = Meraki\Html\Form::createFromSchema([
	'name' => 'edit_booking',
	'title' => 'Edit Booking',
	'method' => 'PATCH',
	'action' => '/bookings/1234',
	'fields' => [
		Meraki\Html\Form\Field::createFromSchema(new FieldSchemaIdentity())
			->name('booking_id')
			->disable(),
		$nameField,
		Meraki\Html\Form\Field::createFromSchema(new FieldSchemaService())
			->disable(),
		Meraki\Html\Form\Field::createFromSchema(new FieldSchemaBookingStartsAt()),
		Meraki\Html\Form\Field::createFromSchema(new FieldSchemaBookingValue()),
	]
]);

// var_dump($nameField);
// var_dump($nameField->isValid());

echo (new Meraki\Html\Renderer())->changeSubmitButtonText('Book')->render($form);


// $el = new Meraki\Html\Form\Field\Money(
// 	new Meraki\Html\Attribute\Name('value'),
// 	new Meraki\Html\Attribute\Label('Value'),
	// [
	// 	'horse' => 'Horse',
	// 	'cat' => 'Cat',
	// 	'dog' => 'Dog',
	// 	'fish' => 'Fish',
	// ]
	// Meraki\Html\Attribute\Currency::aud(),
	// Meraki\Html\Attribute\Policy::parse('unrestricted'),
	// Meraki\Html\Attribute\Min::of(2),
	// Meraki\Html\Attribute\Max::of(7),
	// Meraki\Html\Attribute\Step::inIncrementsOf('0.01'),
	// new Meraki\Html\Attribute\Version(5)
// );
// $el->defaultValue = 'dog';
// $el->input('eae149cb-79a3-41fa-9c70-2951fa3bdf00');

// $el->hint('Enter a value between 2 and 7.');

// $style = new Meraki\Html\Attribute\Style([
// 	'display' => 'flex',
// 	'flex-direction' => 'column',
// 	'max-width' => '14em',
// ]);

// $el->attributes->add($style);

// var_dump($el->errors);
// echo (new Meraki\Html\FieldRenderer())->render($el);

// $form = Meraki\Html\Form::createFromSchema([
// 	'name' => 'edit_booking',
// 	'title' => 'Edit Booking',
// 	'method' => 'PATCH',
// 	'action' => '/bookings/' . $uuid->__toString(),
// 	'fields' => [
// 		// Field::createFromSchema(new FieldSchema\BookingFinishesAt($queryBus)),
// 		// Field::createFromSchema(new FieldSchema\Timezone($queryBus))
// 		// 	->name('booking_timezone')
// 		// 	->disable(),
// 		// Field::createFromSchema(new FieldSchema\BookingStatus()),
// 		// Field::createFromSchema(new FieldSchema\BookingValue())->name('booking_value'),
// 		// Field::createFromSchema(new FieldSchema\Instructor($queryBus))->prefill(),
// 		// Field::createFromSchema(new FieldSchema\BookingStudent()),
// 		// Field::createFromSchema(new FieldSchema\UseOwnVehicle()),
// 		// Field::createFromSchema(new FieldSchema\Location()),
// 	]
// ]);
