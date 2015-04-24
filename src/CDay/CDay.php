<?php
/**
 * Class representing a day in the swedish Babe Ruth calender made by Viktor Kjellberg.
 *
 */
class CDay extends CWeek {

	private $nameOfDay;
	private $red = false; 		// boolean
	private $inMonth = true; 	// boolean
	private $dayInMonth;
	private $date;

	/**
	 * Constructor
	 *
	 */
	public function __construct($year, $month, $week, $dayInWeek) {
		$this->setNameOfDay($dayInWeek);
		$this->setRed($dayInWeek);
		$this->setDate($year, $week, $dayInWeek+1);
		$this->setDayNo();
		$this->setInMonth($month);
	}

	/**
	 * Set if day is in month or not (a few leading and trailing days each month might be shown greyed out).
	 */
	private function setInMonth($month) {
		$monthOfDay = date("n", strtotime($this->date));
		if($monthOfDay!=$month){
			$this->inMonth = false;
		}
	}

	/**
	 * Set the day number to be shown in calendar. 
	 */
	private function setDayNo() {
		$this->dayInMonth = date("j", strtotime($this->date));
	}


	/**
	 * Set date of day
	 */
	private function setDate($year, $week, $day) {
		$gendate = new DateTime();
		$gendate->setISODate($year,$week,$day); //year , week num , day
		$this->date = $gendate->format('Y-m-d'); //"prints"  26-12-2013
	}

	/**
	 * Returns the day as html to be added to calendar
	 */
	public function getDayAsHtml() {
		$class = 'day';
		if($this->red){
			$class .= ' red';
		}
		if(!$this->inMonth){
			$class .= ' notInMonth';
		}
		if($this->date==date('Y-m-d')){
			$class .= ' today';
		}
		$html = '<div class="'.$class.'">';
		$html .= $this->dayInMonth.'<br>';
		$html .= '</div>';
		return $html;
	}

	/**
	 * Sets day marked as red.
	 */
	private function setRed($day) {
		if($day==6){
			$this->red=true;
		}
	}

	/**
	 * Sets swedish name of day from number of day (0-6 where 0 is monday and 6 is sunday)
	 */
	private function setNameOfDay($day) {
		switch ($day) {
			case '0':
				$this->nameOfDay = "Måndag";
				break;
			case '1':
				$this->nameOfDay = "Tisdag";
				break;
			case '2':
				$this->nameOfDay = "Onsdag";
				break;
			case '3':
				$this->nameOfDay = "Torsdag";
				break;
			case '4':
				$this->nameOfDay = "Fredag";
				break;
			case '5':
				$this->nameOfDay = "Lördag";
				break;
			case '6':
				$this->nameOfDay = "Söndag";
				break;
			default:
				echo "Only deals with values between 0-6.";
				break;
		}

	}
}