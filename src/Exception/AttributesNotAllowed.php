<?php
declare(strict_types=1);

namespace Meraki\Html\Exception;

use Meraki\Html\Exception;

/**
 * Exception thrown when an attribute is not allowed.
 */
final class AttributesNotAllowed extends \RuntimeException implements Exception
{
	/**
	 * @param class-string[] $attrs The name of the attribute that is not allowed.
	 */
	public function __construct(array $attrs)
	{
		parent::__construct(sprintf(
			'One or more attributes are not allowed in the set: %s',
			implode(', ', $attrs)
		));
	}
}
