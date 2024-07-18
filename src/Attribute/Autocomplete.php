<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Autocomplete extends Attribute
{
	private const NAME_TO_AUTOCOMPLETE_MAPPINGS = [
		'name' => 'name',
		'full_name' => 'name',
		'fullname' => 'name',
		'firstname' => 'given-name',
		'first_name' => 'given-name',
		'lastname' => 'family-name',
		'last_name' => 'family-name',
		'email' => 'email',
		'phone' => 'tel',
		'mobile' => 'tel',
		'cell' => 'tel',
		'country' => 'country',
		'postcode' => 'postal-code',
		'zip' => 'postal-code',
		'birthday' => 'bday',
		'birth_day' => 'bday',
		'birthdate' => 'bday',
		'birth_date' => 'bday',
		'dob' => 'bday',
		'date_of_birth' => 'bday',
		'username' => 'username',
		'sex' => 'sex',
		'gender' => 'sex',
		'url' => 'url',
		'website' => 'url',
	];

	private array $tokens = [];

	public function __construct(string $token, string ...$tokens)
	{
		$this->setName('autocomplete');
		$this->add($token, ...$tokens);
	}

	public function add(string $token, string ...$tokens): self
	{
		$this->tokens[] = $token;
		$this->tokens = array_merge($this->tokens, $tokens);

		$this->updateValue();

		return $this;
	}

	public static function createFromNameAttribute(Attribute\Name $name): self
	{
		if (isset(self::NAME_TO_AUTOCOMPLETE_MAPPINGS[$name->value])) {
			return new self(self::NAME_TO_AUTOCOMPLETE_MAPPINGS[$name->value]);
		}

		throw new \RuntimeException("Cannot create an autocomplete attribute from the name '{$name->value}'.");
	}

	public function createFromAttributeType(Attribute\Type $type): self
	{
		if ($type->is('email') || $type->is('email-address')) {
			return new self('email');
		}

		if ($type->is('tel') || $type->is('telephone') || $type->is('phone')) {
			return new self('tel');
		}

		if ($type->is('url') || $type->is('uri')) {
			return new self('url');
		}

		throw new \RuntimeException("Cannot create an autocomplete attribute from the type '{$type->value}'.");
	}

	public static function off(): self
	{
		return new self('off');
	}

	public static function on(): self
	{
		return new self('on');
	}

	private function updateValue(): void
	{
		$this->setValue(implode(' ', $this->tokens));
	}
}
