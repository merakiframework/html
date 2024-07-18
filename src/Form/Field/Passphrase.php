<?php
declare(strict_types=1);

namespace Meraki\Html\Form\Field;

use Meraki\Html\Form\Field;
use Meraki\Html\Attribute;

/**
 * A passphrase is a sequence of words or other text used for authentication.
 *
 * What makes a passphrase different from a password is that it is typically
 * longer and more complex. Passphrases typically do not require special
 * characters or numbers, but instead rely on the length and complexity of
 * the words used. This is measured as entropy.
 *
 * To calculate the entropy of a passphrase, an algorithm is used. The formula
 * determines how to calculate the entropy and its range. For example,
 * the Shannon entropy formula is a common formula used to calculate the
 * strength of a password, which then returns an entropy result between 0 and
 * 128 bits. With a higher number meaning a stronger passphrase.
 */
final class Passphrase extends Field
{
	public static array $allowedAttributes = [
		Attribute\Entropy::class,
		Attribute\Algorithm::class,
		// Attribute\Formula::class,
	];

	public function getType(): Attribute\Type
	{
		return new Attribute\Type('passphrase');
	}

	public function getDefaultAttributes(): array
	{
		return [];
	}

	public function validate(mixed $value): ValidationResult
	{
		if (!is_string($value)) {
			return ValidationResult::failed('Passphrase must be a string.');
		}

		$this->attributes->add(new Attribute\Algorithm('shannon'));	// default algorithm
		$this->attributes->add($this->getRecommendedEntropy());		// default entropy

		$errors = [];
		$requiredEntropy = $this->attributes->get(Attribute\Entropy::class)->value;
		$calculatedEntropy = $this->calculateEntropy($value);
		var_dump($calculatedEntropy);

		// check entropy constraint
		if ($calculatedEntropy < $requiredEntropy) {
			$errors[] = 'Passphrase is not strong enough. Required strength of: ' . $requiredEntropy . ', got '.$calculatedEntropy.'.';
		}

		return ValidationResult::guess($value, $errors);
	}

	private function getRecommendedEntropy(): Attribute\Entropy
	{
		$algo = $this->attributes->get(Attribute\Algorithm::class)->value;

		return match ($algo) {
			'shannon' => new Attribute\Entropy(76),
			'renyi' => new Attribute\Entropy(57),
			'enhanced' => new Attribute\Entropy(19), // 'enhanced' is a custom formula
			default => throw new \InvalidArgumentException(
				'Unknown passphrase algorithm "' . $algo . '": could not provide a default entropy. Please speicify the "entropy" attribute manually.'
			),
		};
	}

	private function calculateEntropy(string $value): int
	{
		/** @var int $formula */
		$formula = match ($this->attributes->get(Attribute\Algorithm::class)->value) {
			'shannon' => $this->calculateShannonEntropy($value),
			'renyi' => $this->calculateRenyiEntropy($value),
			'enhanced' => $this->calculateEnhancedEntropy($value),
			default => throw new \InvalidArgumentException('Invalid passphrase formula constraint.'),
		};

		return $formula * mb_strlen($value);
	}

	/**
	 * Calculate the entropy of a passphrase using a custom formula.
	 *
	 * This formula is built on top of the Shannon entropy formula (for
	 * frequency analysis), then adds weights to certain character sets
	 * to increase the entropy of the passphrase. This allows for using
	 * a passphrase that is easier to remember, but still secure.
	 *
	 * The formula is as follows:
	 * 		- foreach character set calculate the Shannon entropy
	 * 		- multiply the entropy of each character set by a weight
	 * 		- sum the weighted entropies
	 * 		- get the average of each character set
	 * 		- return the average character entropy
	 */
	private function calculateEnhancedEntropy(string $value): int
	{
		$entropy = 0;

		// Character type analysis including Unicode support
		$size = mb_strlen($value);
		$hasLowercase = preg_match('/\p{Ll}/u', $value); // Unicode lowercase letters
		$hasUppercase = preg_match('/\p{Lu}/u', $value); // Unicode uppercase letters
		$hasDigits = preg_match('/\p{N}/u', $value);     // Unicode digits
		$hasSymbols = preg_match('/\P{L}\p{N}/u', $value); // Unicode symbols (non-letters and numbers)

		// weights per character set
		// @todo allow these to be set by the user
		$weightLowercase = 1;
		$weightUppercase = 1.4;
		$weightDigits = 1.1;
		$weightSymbols = 1.7;

		// Calculate entropy for each character set
		// this is done by calculating the shannon
		// entropy on only the characters for that set
		// in the passphrase
		if ($hasLowercase) {
			$entropy += $this->calculateShannonEntropy(preg_replace('/\P{Ll}/u', '', $value)) * $weightLowercase;
		}

		if ($hasUppercase) {
			$entropy += $this->calculateShannonEntropy(preg_replace('/\P{Lu}/u', '', $value)) * $weightUppercase;
		}

		if ($hasDigits) {
			$entropy += $this->calculateShannonEntropy(preg_replace('/\P{N}/u', '', $value)) * $weightDigits;
		}

		if ($hasSymbols) {
			$entropy += $this->calculateShannonEntropy(preg_replace('/\P{L}\p{N}/u', '', $value)) * $weightSymbols;
		}

		// check for division by zero
		if ($hasLowercase + $hasUppercase + $hasDigits + $hasSymbols === 0) {
			return 0;
		}

		// Calculate the average entropy per character set
		$entropy /= ($hasLowercase + $hasUppercase + $hasDigits + $hasSymbols);

		// Calculate the entropy per character
		$entropy /= $size;

		return (int) ceil($entropy);
	}

	private function calculateShannonEntropy(string $value): int
	{
		$entropy = 0;
		$size = mb_strlen($value);

		foreach (self::mb_count_chars($value) as $occurrence) {
			$p = $occurrence / $size;
			$entropy -= $p * log($p, 2);
		}

		return (int) ceil($entropy);
	}

	private function calculateRenyiEntropy(string $value): int
	{
		// @todo allow alpha to be set by the user
		$alpha = 2;
		$sum = 0;
		$size = mb_strlen($value);

		foreach (self::mb_count_chars($value) as $occurrence) {
			$p = $occurrence / $size;
			$sum += pow($p, $alpha);
		}

		$entropy = log($sum) / (1 - $alpha);
		return (int) ceil($entropy);
	}

	/**
	 * Count the number of occurrences of each character in a string.
	 *
	 * @param string $string The string to count characters in.
	 * @return array An associative array where the key is the character
	 * 				 and the value is the number of occurrences. Only
	 * 				 returns characters that occur at least once. (The
	 * 				 same as `count_chars($str, 1)`.)
	 */
	private static function mb_count_chars(string $string): array
	{
		$chars = [];
		$length = mb_strlen($string);

		for ($i = 0; $i < $length; $i++) {
			$char = mb_substr($string, $i, 1);

			if (!array_key_exists($char, $chars)) {
				$chars[$char] = 1;
			} else {
				$chars[$char]++;
			}
		}

		return $chars;
	}
}
