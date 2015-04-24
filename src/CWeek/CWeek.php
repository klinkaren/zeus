<?php
/**
 * Class representing a week in the swedish Babe Ruth calender made by Viktor Kjellberg.
 *
 */
class CWeek extends CCal {

	private $weekNo;
	private $month;
	private $year;
	private $days;
	
	/**
	 * Constructor
	 *
	 */
	public function __construct($year, $month, $weekNo) {
		$this->weekNo = $weekNo;
		$this->month = $month;
		$this->year = $year;
		$this->setDays();
	}

	/**
	 * Set days of week.
	 */
	private function setDays() {
		for ($i=0; $i <7 ; $i++) { 
			$this->days[$i] = new CDay($this->year, $this->month, $this->weekNo, $i);
		}
	}

	/**
	 * Returns the given week as html.
	 */
	public function getWeekAsHtml() {
		$html='<div class="week">';
		$html .= '<div class="weekNo">'.$this->weekNo.'</div>';
		foreach ($this->days as $key => $day) {
			$html .= $day->getDayAsHtml();
		}

		$html .='</div>';
		return $html;
	}

	/**
	 * Returns week number of this week.
	 */
	public function getWeekNo() {
		return $this->weekNo;
	}

}