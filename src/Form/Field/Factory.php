<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;

final class Factory
{
	public function createFromSchema(array $schema): Field
	{
		$schema = (object)$schema;
		$className = $schema->type;
		$attributes = $schema->attributes ?? [];

		return new $className(...$attributes);
	}
}
