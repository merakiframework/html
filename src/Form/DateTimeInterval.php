<?php
declare(strict_types=1);

namespace Meraki\Html\Form;

use RuntimeException;

final class DateTimeInterval extends \DateInterval
{
	public function __toString(): string
	{
		$interval = 'P';

		if ($this->y > 0) {
			$interval .= $this->y . 'Y';
		}

		if ($this->m > 0) {
			$interval .= $this->m . 'M';
		}

		if ($this->d > 0) {
			$interval .= $this->d . 'D';
		}

		if ($this->h > 0 || $this->i > 0 || $this->s > 0) {
			$interval .= 'T';
		}

		if ($this->h > 0) {
			$interval .= $this->h . 'H';
		}

		if ($this->i > 0) {
			$interval .= $this->i . 'M';
		}

		if ($this->s > 0) {
			$interval .= $this->s . 'S';
		}

		if (strlen($interval) < 3) {
			throw new \InvalidArgumentException('No Interval provided');
		}

		return $interval;
	}
	public function multiple(int $factor): self
	{
		// anything multiplied by 0 is 0
		if ($factor === 0) {
			return new self('PT0S');
		}

		// multiplying by 1 changes nothing
		if ($factor === 1) {
			return $this;
		}

		// repeated subtraction
		if ($factor < 0) {
			for ($i = $factor; $i < 0; $i++) {
				$interval = $this->subtract($interval);
			}

			return $interval;
		}

		// repeated addition
		for ($i = 1; $i < $factor; $i++) {
			$interval = $this->add($interval);
		}

		return $interval;
	}

	public function subtract($other): self
	{
		$diff = $this->inSeconds() - self::from($other)->inSeconds();

		if ($diff < 0) {
			throw new \InvalidArgumentException('Cannot subtract ' . $this->format('%H:%i:%s') . ' from ' . $other->format('%H:%i:%s'));
		}

		return self::fromSeconds($diff);
	}

	public function sub($other): self
	{
		return $this->subtract($other);
	}

	public function add($other): self
	{
		return self::fromSeconds($this->inSeconds() + self::from($other)->inSeconds());
	}

	public function compare($other): int
	{
		if ($this->inSeconds() < self::from($other)->inSeconds()) {
			return -1;
		} elseif ($this->inSeconds() > self::from($other)->inSeconds()) {
			return 1;
		} else {
			return 0;
		}
	}

	public function lessThan($other): bool
	{
		return $this->inSeconds() < self::from($other)->inSeconds();
	}

	public function lessThanOrEqualTo($other): bool
	{
		return $this->inSeconds() <= self::from($other)->inSeconds();
	}

	public function greaterThan($other): bool
	{
		return $this->inSeconds() > self::from($other)->inSeconds();
	}

	public function greaterThanOrEqualTo($other): bool
	{
		return $this->inSeconds() >= self::from($other)->inSeconds();
	}

	public function equals($other): bool
	{
		return $this->inSeconds() === self::from($other)->inSeconds();
	}

	public function equalTo($other): bool
	{
		return $this->equals($other);
	}

	public function isEqualTo($other): bool
	{
		return $this->equals($other);
	}

	public function inSeconds(): int
	{
		return $this->s + ($this->i * 60) + ($this->h * 60 * 60);
	}

	public function toSeconds(): int
	{
		return $this->inSeconds();
	}

	public function inMinutes(): int
	{
		if ($this->s > 0) {
			throw new RuntimeException('Cannot convert interval to minutes: seconds are present and precision will be lost.');
		}

		return $this->i + ($this->h * 60);
	}

	public static function fromStartAndEnd(\DateTimeInterface $start, \DateTimeInterface $end): self
	{
		$start = LocalDateTime::fromDateTime($start);
		$end = LocalDateTime::fromDateTime($end);
		$interval = $start->diff($end);

		return self::fromNative($interval);
	}

	public static function fromNative(\DateInterval $interval): self
	{
		return self::from($interval);
	}

	public function formatAsHumanReadable(): string
	{
		// return a human readable representation of the interval
		// e.g. 1 hour 30 minutes

		$parts = [];

		if ($this->y > 0) {
			$parts[] = $this->y . ' year' . ($this->y > 1 ? 's' : '');
		}

		if ($this->m > 0) {
			$parts[] = $this->m . ' month' . ($this->m > 1 ? 's' : '');
		}

		if ($this->d > 0) {
			$parts[] = $this->d . ' day' . ($this->d > 1 ? 's' : '');
		}

		if ($this->h > 0) {
			$parts[] = $this->h . ' hour' . ($this->h > 1 ? 's' : '');
		}

		if ($this->i > 0) {
			$parts[] = $this->i . ' minute' . ($this->i > 1 ? 's' : '');
		}

		if ($this->s > 0) {
			$parts[] = $this->s . ' second' . ($this->s > 1 ? 's' : '');
		}

		return implode(' ', $parts);
	}

	public function diff(self $interval): self
	{
		return $this->subtract($interval);
	}

	public static function fromSeconds(int|string $seconds): self
	{
		if (is_string($seconds)) {
			if (!ctype_digit($seconds)) {
				throw new \InvalidArgumentException('Invalid seconds provided');
			}

			$seconds = (int) $seconds;
		}

		$hours = floor($seconds / 60 / 60);
		$minutes = floor(($seconds - ($hours * 60 * 60)) / 60);
		$seconds = $seconds - ($hours * 60 * 60) - ($minutes * 60);

		return new self('PT' . $hours . 'H' . $minutes . 'M' . $seconds . 'S');
	}

	public static function from($interval): self
	{
		if ($interval instanceof self) {
			return $interval;
		}

		// convert to self
		if ($interval instanceof \DateInterval) {
			$str = 'P';

			if ((int) $interval->format('%y') > 0) {
				$str .= $interval->format('%y') . 'Y';
			}

			if ((int) $interval->format('%m') > 0) {
				$str .= $interval->format('%m') . 'M';
			}

			if ((int) $interval->format('%d') > 0) {
				$str .= $interval->format('%d') . 'D';
			}

			if ((int) $interval->format('%h') > 0 || (int) $interval->format('%i') > 0 || (int) $interval->format('%s') > 0) {
				$str .= 'T';
			}

			if ((int) $interval->format('%h') > 0) {
				$str .= $interval->format('%h') . 'H';
			}

			if ((int) $interval->format('%i') > 0) {
				$str .= $interval->format('%i') . 'M';
			}

			if ((int) $interval->format('%s') > 0) {
				$str .= $interval->format('%s') . 'S';
			}

			if (strlen($str) < 3) {
				throw new \InvalidArgumentException('No Interval provided');
			}

			$interval = $str;
		}

		if (is_string($interval)) {
			return new self($interval);
		}

		throw new RuntimeException('Could not create interval');
	}
}
