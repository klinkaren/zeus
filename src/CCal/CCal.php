<?php
/**
 * Class for the swedish Babe Ruth calender made by Viktor Kjellberg.
 *
 */
class CCal {

	private $curYear;
	private $curMonth;
	private $curDay;
	private $strMonth;
	private $year;
	private $month;
	private $weeks;
	private $movieTitle;
	private $movieId;
	private $movies = array(
		array(5, "From Dusk till Dawn"),
		array(1, "Pulp fiction"),
		array(11, "Vicky Cristina Barcelona"),
		array(25, "The Great Gatsby"),
		array(24, "Kingdom of heaven"),
		array(7, "Kingsman"),
		array(22, "The Last Samurai"),
		array(10, "Django unchained"),
		array(9, "Inglourious Basterds"), 
		array(8, "Full Metal Jacket"),
		array(21, "Enemy at the gates"),
		array(14, "In Time")
		);

	/**
	 * Constructor
	 *
	 */
	public function __construct() {
	}

	public function view(){
		// Get calendar from the session or start new calendar.
		if(isset($_SESSION['cal'])) {
			$cal = $_SESSION['cal'];
			if(isset($_GET['next'])){
				$cal->nextMonth();
			} elseif(isset($_GET['prev'])){
				$cal->prevMonth();
			}

			// Show todays month (by unsetting session causing calender to reload).
			if(isset($_GET['destroyCal'])) {
				unset($_SESSION['cal']);
			}
			$html = $cal->getCal();
		} else {

			// save calendar to session
			$this->createCalendar();
			$_SESSION['cal'] = $this;
			$html = $this->getCal();
		}

		
		return $html;

	}

	private function createCalendar(){
		$this->curYear = date('Y');
		$this->curMonth = date('m');
		$this->curDay = date('d');

		// Set calender to current year and month
		$this->setCal(date('Y'), date('n'));
	}



	/**
	  * Sets calendar based on supplied year and month.
	  *
	  */ 
	private function setCal($year, $month) {
		$this->setMonth(sprintf('%02d', $month));
		$this->setYear($year);
		$this->setWeeks();
	}
	
	/**
	 * Updates calender to previous month
	 *
	 */
	private function prevMonth() {
		if($this->month==1){
			$month=12;
			$year=$this->year-1;
		} else {
			$month = ($this->month-1);
			$year = $this->year;
		}
		$this->setCal($year, $month);
	}


	/**
	 * Updates calender to next month
	 *
	 */
	private function nextMonth() {
		if($this->month==12){
			$month=1;
			$year=$this->year+1;
		} else {
			$month = ($this->month+1);
			$year = $this->year;
		}
		$this->setCal($year, $month);
	}

	/**
	 * Returns html version of calender
	 *
	 */
	private function getCal(){

		function getNameOfDay($day) {
			switch ($day) {
				case '0':
					$returnMe = "Måndag";
					break;
				case '1':
					$returnMe = "Tisdag";
					break;
				case '2':
					$returnMe = "Onsdag";
					break;
				case '3':
					$returnMe = "Torsdag";
					break;
				case '4':
					$returnMe = "Fredag";
					break;
				case '5':
					$returnMe = "Lördag";
					break;
				case '6':
					$returnMe = "Söndag";
					break;
				default:
					echo "Only deals with values between 0-6.";
					break;
			}
			return $returnMe;
		}

		$this->setMovieParams($this->month);

		$html = '<div class="month">';
		$html .= '<div class="cal'.$this->month.'"><img src="img.php?src=cal/'.$this->month.'.jpg&width=978&height=450&crop-to-fit"/><div class="textOver">Månadens film:<br>
		<span class="monthMovieTitle">'.$this->movieTitle.'</span><br/><a href="film.php?id='.$this->movieId.'">Hyr filmen</a></div><div class="textOverBg">.</div></div>';
		$html .= '<div class="monthHeading">'.$this->strMonth.", ".$this->year.'</div>';
		$html .= '<div class="calNav">';
			$html .= '<div class="calNavLeft">';		
				$html .= '<a href="?prev">&lt;&lt;Föregående månad</a>';
			$html .= '</div>';
			$html .= '<div class="calNavRight">';
				$html .= '<a href="?next">Nästa månad&gt;&gt;</a>';
			$html .= '</div>';
		$html .= '</div>';
			
		// Infoline containing weekdays
		$html .= '<div class="infoLine">';
			$html .= '<div class="weekNo">v</div>';
			for ($i=0; $i < 7; $i++) { 
				$html .='<div class="dayTopline">';
				$html .=getNameOfDay($i);
				$html .='</div>';
			}
		$html .= '</div>';

		foreach ($this->weeks as $key => $week) {
			$html .= $week->getWeekAsHtml();
		}
		$html .= '</div>';

		return $html;	
	}

	private function setMovieParams($month){
		$this->movieId = $this->movies[$month-1][0];
		$this->movieTitle = $this->movies[$month-1][1];
	}


	/**
	 * Creates all weeks of month and saves em in array
	 */
	private function setWeeks() {

		//local function for adding a day
		function addDayswithdate($date, $days) {
		    $date = strtotime("+".$days." days", strtotime($date));
		    return date("Y-m-d", $date);
		}

		// Clear anything already set.
		unset($this->weeks);

		//Set first and last day of month.
		$day = $this->year."-".$this->month."-01"; 
		$lastDayOfMonth = date("Y-m-t", strtotime($this->year."-".$this->month."-01"));

		// Create array for week of all days in month.
		while ($day <= $lastDayOfMonth) {

			// Get week of $day
			$time = new DateTime($day);
			$week = intval($time->format("W"));

			// add to array
			$allWeeks[] = $week;

			// step to next day
			$day = addDayswithdate($day, 1);
		}
		
		// removing duplicates
		$allWeeks = array_unique($allWeeks);

		// creating weeks
		foreach ($allWeeks as $week) {
			$this->weeks[] = new CWeek($this->year, $this->month, $week);
		}		
	}

	/**
	 * Sets year
	 *
	 */
	private function setYear($year) {
		$this->year = $year;
	}

	/**
	 * Returns month as a string (swedish) from month as a number.
	 *
	 */
	private function setMonth($month) {
		$this->month = $month;
		switch ($month) {
			case 1:
				$this->strMonth = "Januari";
				break;
			case 2:
				$this->strMonth = "Februari";
				break;
			case 3:
				$this->strMonth = "Mars";
				break;
			case 4:
				$this->strMonth = "April";
				break;
			case 5:
				$this->strMonth = "Maj";
				break;
			case 6:
				$this->strMonth = "Juni";
				break;
			case 7:
				$this->strMonth = "Juli";
				break;
			case 8:
				$this->strMonth = "Augusti";
				break;
			case 9:
				$this->strMonth = "September";
				break;
			case 10:
				$this->strMonth = "Oktober";
				break;
			case 11:
				$this->strMonth = "November";
				break;
			case 12:
				$this->strMonth = "December";
				break;
			default:
				$this->strMonth = "Kan enbart hantera värden mellan 1-12.";
				break;
		}
	}
}