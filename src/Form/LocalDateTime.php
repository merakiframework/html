<?php
declare(strict_types=1);

namespace Meraki\Html\Form;

final class LocalDateTime extends \DateTimeImmutable
{
	public int $firstDayOfWeek = 1;	// @todo belongs in a `DateWeek` class or similar
	public int $dayOfWeek;

	public function __construct(string $time = 'now', ?\DateTimeZone $timezone = null)
	{
		parent::__construct($time, $timezone);
		$this->dayOfWeek = (int) $this->format('N');

		if ($timezone === null) {
			// throw new \InvalidArgumentException('Timezone is required');
		}
	}

	public static function now(string|\DateTimeZone $timezone): self
	{
		return new self('now', $timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone));
	}

	public static function parse(string $time): self
	{
		$dateTime = parent::createFromFormat(self::ATOM, $time);

		if ($dateTime === false) {
			throw new \InvalidArgumentException('Invalid date format');
		}

		return new self($dateTime->format('Y-m-d H:i:s.u'), $dateTime->getTimezone());
	}

	public static function createFromFormat(string $format, string $time, ?\DateTimeZone $timezone = null): self
	{
		$dateTime = parent::createFromFormat($format, $time, $timezone);

		if ($dateTime === false) {
			throw new \InvalidArgumentException('Invalid date format');
		}

		return new self($dateTime->format('Y-m-d H:i:s.u'), $dateTime->getTimezone());
	}

	public static function fromDateTime(\DateTimeInterface $dateTime): self
	{
		return new self($dateTime->format('Y-m-d H:i:s.u'), $dateTime->getTimezone());
	}

	/**
	 * Limit this datetime to a maximum value.
	 */
	public function limit(self $max): self
	{
		if ($this > $max) {
			return $max;
		}

		return $this;
	}

	public function getWeek(): \DatePeriod
	{
		if ($this->dayOfWeek < $this->firstDayOfWeek) {
			$diff = $this->dayOfWeek + (7 - $this->firstDayOfWeek);
			$startOfWeek = $this->sub(DateTimeInterval::from('P' . $diff . 'D'));
		} elseif ($this->dayOfWeek > $this->firstDayOfWeek) {
			$diff = $this->dayOfWeek - $this->firstDayOfWeek;
			$startOfWeek = $this->sub(DateTimeInterval::from('P' . $diff . 'D'));
		} else {
			$startOfWeek = $this;
		}

		// @todo change to iterable `DateWeek` object or similar
		return new \DatePeriod(
			$startOfWeek,
			DateTimeInterval::from('P1D'),
			$startOfWeek->add(DateTimeInterval::from('P6D')),
			\DatePeriod::INCLUDE_END_DATE
		);
	}

	// override native `add` method to return a new instance of `LocalDateTime` instead of `DateTime`
	public function add(\DateInterval $interval): self
	{
		$interval = DateTimeInterval::from($interval);
		$modified = parent::add($interval);

		if ($modified === false) {
			throw new \InvalidArgumentException('Invalid date modify');
		}

		return new self($modified->format('Y-m-d H:i:s.u'), $modified->getTimezone());
	}

	public function sub(\DateInterval $interval): self
	{
		$interval = DateTimeInterval::from($interval);
		$modified = parent::sub($interval);

		if ($modified === false) {
			throw new \InvalidArgumentException('Invalid date modify');
		}

		return new self($modified->format('Y-m-d H:i:s.u'), $modified->getTimezone());
	}

	public function addDays(int $days): self
	{
		return $this->add(DateTimeInterval::from('P' . $days . 'D'));
	}

	// makes time go to the next interval. For example, if $interval is
	// "PT15M" and the time is 12:03, the time will be changed to 12:15.
	public function toNext(DateTimeInterval $interval): self
	{
		$seconds = $interval->toSeconds();
		$seconds = $seconds - ($this->getTimestamp() % $seconds);

		return $this->add(DateTimeInterval::fromSeconds($seconds));
	}

	public function toNearest(DateTimeInterval $interval): self
	{
		$seconds = $interval->toSeconds();
		$seconds = $seconds - ($this->getTimestamp() % $seconds);

		if ($seconds > $interval->toSeconds() / 2) {
			return $this->add(DateTimeInterval::fromSeconds($seconds));
		}

		return $this->sub(DateTimeInterval::fromSeconds($seconds));
	}

	public function intervalOf(DateTimeInterval $interval): bool
	{
		// return `true` if the time is an interval of $interval
		return $this->getTimestamp() % $interval->inSeconds() === 0;
	}

	// makes time go to the previous interval. For example, if $interval is
	// "PT15M" and the time is 12:03, the time will be changed to 12:00.
	public function toPrevious(DateTimeInterval $interval): self
	{
		$seconds = $interval->toSeconds();
		$seconds = $this->getTimestamp() % $seconds;

		return $this->sub(DateTimeInterval::fromSeconds($seconds));
	}

	public function firstDayOfWeekIs(int $day): self
	{
		$clone = clone $this;
		$clone->firstDayOfWeek = $day;

		return $clone;
	}

	public function isBetween(self $from, self $to): bool
	{
		return $this >= $from && $this <= $to;
	}

	public static function calculateDayOffsetFromFirstDayOfWeek(int $day, int $firstDayOfWeek = 1): int
	{
		return ($day - $firstDayOfWeek + 7) % 7;
	}

	public static function getMonthsFor(\DateTimeInterface $from, \DateTimeInterface $to, int $firstDayOfWeek = 1): array
	{
		// $from = new \DateTimeImmutable('2023-09-30');
		$period = new \DatePeriod(
			$from->modify('first day of this month'),
			new DateTimeInterval('P1M'),
			$to->modify('first day of this month'),
			\DatePeriod::INCLUDE_END_DATE
		);
		$months = [];

		// loop through every month between $from (inclusive) and $to (inclusive)
		foreach ($period as $month) {
			$month = self::fromDateTime($month);
			$month = self::getMonthOf($month, $firstDayOfWeek);
			$months[] = $month;
		}

		return $months;
	}

	// returns a DatePeriod object that represents the times between $from and $to
	// with the given $interval, inclusive of $from and $to
	public function until(self $to, DateTimeInterval $interval): iterable
	{
		$current = $this;

		// @todo change to iterable `DateTimeRange`/`DateTimePeriod` object or similar
		while ($current <= $to) {
			yield $current;
			$current = $current->add($interval);
		}
	}

	public function daysUntil(self $to): iterable
	{
		return $this->until($to, DateTimeInterval::from('P1D'));
	}

	public static function getMonthOf(\DateTimeInterface $date, int $firstDayOfWeek = 1)
	{
		$calendar = [];
		$firstDayOfMonth = self::fromDateTime($date)->modify('first day of this month');
		$lastDayOfMonth = self::fromDateTime($date)->modify('last day of this month');
		$numDays = (int) $lastDayOfMonth->format('d');

		// Calculate the number of leading days (days before the first day of the week)
		$leadingDays = ($firstDayOfMonth->format('N') + 7 - $firstDayOfWeek) % 7;

		// Add leading days
		for ($i = 0; $i < $leadingDays; $i++) {
			$calendar[] = $firstDayOfMonth->sub(new DateTimeInterval('P' . ($leadingDays - $i) . 'D'));
		}

		// Add days of the month
		for ($i = 1; $i <= $numDays; $i++) {
			$calendar[] = $firstDayOfMonth->setDate(
				(int) $firstDayOfMonth->format('Y'),
				(int) $firstDayOfMonth->format('m'),
				$i
			);
		}

		// move to first day of next month
		$lastDayOfMonth = $lastDayOfMonth->add(new DateTimeInterval('P1D'));

		// Add trailing days
		$trailingDays = 7 - (count($calendar) % 7);

		if ($trailingDays < 7 && $trailingDays > 0) {
			for ($i = 0; $i < $trailingDays; $i++) {
				$calendar[] = $lastDayOfMonth->add(new DateTimeInterval('P' . $i . 'D'));
			}
		}

		// Split the calendar into weeks
		$weeks = array_chunk($calendar, 7);

		return $weeks;
	}

	public static function generateDaysInWeek(int $firstDayOfWeek = 1): array
	{
		$days = [];

		for ($i = 1, $dayOfWeek = $firstDayOfWeek; $i < 8; $i++) {
			$days[] = $dayOfWeek;

			if ($dayOfWeek === 7) {
				$dayOfWeek = 1;
			} else {
				$dayOfWeek += 1;
			}
		}

		return $days;
	}

	public function getWeeksInMonth(): array
	{
		$startDate = $this->modify('first day of this month');
		$endDate = $this->modify('last day of this month');
		$daysOffsetToStartOfWeek = self::calculateDayOffsetFromFirstDayOfWeek((int) $startDate->format('N'), $this->firstDayOfWeek);
		$startDate = $startDate->modify('-' . $daysOffsetToStartOfWeek . ' days');
		$weeks = [];

		// Iterate through the month and group dates into weeks
		while ($startDate <= $endDate) {
			$week = [];

			for ($i = 0; $i < 7; $i++) {
				$week[] = $startDate;
				$startDate = $startDate->modify('+1 day');
			}

			$weeks[] = $week;
		}

		return $weeks;
	}

	public function getDate(): self
	{
		return new self($this->format('Y-m-d'));
	}

	public function getDay(): int
	{
		return (int) $this->format('d');
	}

	public function getMonth(): int
	{
		return (int) $this->format('m');
	}

	public function getYear(): int
	{
		return (int) $this->format('Y');
	}

	public function getHour(): int
	{
		return (int) $this->format('H');
	}

	public function getMinute(): int
	{
		return (int) $this->format('i');
	}

	public function getSecond(): int
	{
		return (int) $this->format('s');
	}

	public function getMicrosecond(): int
	{
		return (int) $this->format('u');
	}

	public function getTime(): self
	{
		return new self($this->format('H:i:s.u'));
	}

	public function modify(string $modify): self
	{
		$modified = parent::modify($modify);

		if ($modified === false) {
			throw new \InvalidArgumentException('Invalid date modify');
		}

		return new self($modified->format('Y-m-d H:i:s.u'), $modified->getTimezone());
	}

	public function __toString(): string
	{
		return $this->format(self::ATOM);
		// return $this->format(self::ISO8601_EXPANDED);
	}
}
