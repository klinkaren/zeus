<?php
/**
 * A game with one or more players.
 *
 */
class CDiceGame  {

	private $players;
	private $numPlayers;
	private $eventCounter; // eventCounter keeps track on how many events have happened in game (rolls and saves) to know whos turn it is.
	private $activePlayer;
	private $winner;

	/**
	 * Constructor
	 *
	 */
	public function __construct() {

	}


	public function view(){
		$humans = 1;
		$computers = 3;

		// Destroy object and end game if called for.
		if(isset($_GET['endGame'])) {
			// ? $game->destroygame();
			unset($_SESSION['game']);
		}

		// Start new game if called for.
		if(isset($_GET['init'])){
		    $this->createGame(array("Katniss"),array("President Snow", "Seneca Crane", "Effie Trinket"));
		  	$_SESSION['game'] = $this;
		}

		// Get game from the session or offer opportunity to start new game.
		if(isset($_SESSION['game'])) {
			$game = $_SESSION['game'];
			if(isset($_GET['roll'])){
				$game->rollDice();
			} elseif(isset($_GET['save'])){
				$game->saveScore();
			}
			$html = $game->showBoard();
		}else {
			$html = "<h1>Tävla om en månads fri filmvisning!</h1><p>Lyckas du vinna är du 
			som medlem med i en utlottning där tre lyckliga vinnare kommer få en gratis månad 
			med så mycket film de vill! För att vi ska kunna kontakta dig vid vinst ber vi dig 
			att se över att den e-postadress som du angett på <a href=user.php>din medlemssida</a> är korrekt.</p>
			<h1>Bakgrund</h1><p>Katniss Everdeen sitter på tåget på väg till huvudstaden för att medverka i Hunger Games. 
			För att lätta upp stämningen föreslår Effie Trinket att de ska spela ett spel. Eftersom spelet spelas 
			via internet är också President Snow och tävlingsledare Seneca Crane med i spelet. Det här är din chans 
			att hjälpa Katniss till en första liten vinst i kampen mot etablissemanget, förtrycket från huvudstaden 
			och allt vad Hunger Games står för.</p><h1>Spelets regler</h1><p>Spelets mål är att bli första 
			spelare att nå 100 poäng genom att kasta en tärning. Spelet är turbaserat och du som Katniss Everdeen 
			har i varje omgång möjlighet att välja ett av två val: Kasta tärningen eller Spara poängen. Väljer du 
			att spara poängen får du inte kasta tärningen den omgången. Om du kastar en etta förlorar du alla dina 
			poäng som inte sparats. Lycka till!</p>";


		$html .= $this->getFieldset($humans, $computers);
		}
		return $html;
	}

	private function getFieldset($humans, $computers) {

		$html = '<div class="center"><form method="get">
					<input type="hidden" name="init">
					<button type="button" title="Starta tävlingen!" class="startGame" onclick="form.submit()"></button>
					</form></div>';
		return $html;
}

	private function createGame($players = array("Player 1"), $computers = array("Computer 1", "Computer 2")){
		foreach ($players as $key => $val) {
	    	$this->players[] = new CDicePlayer($val, false);
		}
		foreach ($computers as $key => $val) {
	    	$this->players[] = new CDicePlayer($val, true);
		}
		$this->numPlayers = count($this->players);
		$this->eventCounter = 0;
		$this->activePlayer = 0;
	}
	/**
	 * Shows game status and gives players options.
	 */
	public function showBoard(){
		
		// If next is computer. Make move. 
		if ( $this->players[$this->activePlayer]->isComputer() ) {
			$this->makeComputerMove();
		}

		// Show status of all players
		$html = '<div class="gameBoard">';
			$html .= '<div class="players">';
				foreach ($this->players as $id => $player) {

					$winner = $this->winner == $player->getName() ? "winner" : "";
					$html .= '<div class="player '.$winner." ".(($this->activePlayer == $id) ? "-active" : "") .'">';
						$html .= "<h2>".$player->getName()."</h2>";
						// Picture
						$html .= '<div class="center"><img class="avatar" src="img.php?src=tavling/'.$id.'.jpg&width=180&height=180&crop-to-fit&quality=80"/></div>';
						$html .= "<p>Poäng: ".$player->getScore()."<br>";
						$html .= "Sparad poäng: ".$player->getSavedScore()."</br>";
						$html .= "Antal kast: ".$player->getNumRolls()."</p>";
						
						// Div that holds dice if it has been rolled. 
						$html .= '<div class="diceHolder">';
							if($this->eventCounter > $id){
								$html .= $player->getRollAsImage();
							}


						$html .= '</div>';

					$html .= '</div>';
				}
			$html .= '</div>';

			// Show who is up and options, unless winner.
			$html .= '<div class="info">';
			if ($this->gameWon()){
				if($this->winner == "Katniss"){
					$html .= "<p><span class=win>".$this->winner." vinner! Någonstans långt inne i Katniss börjar ett frö växa. Kanske måste inte saker vara som de alltid varit? Kanske går det att stå upp mot huvudstaden och etablissemanget?</span></p>";
					$html .= "<p>Bra jobbat ".$_SESSION['user']->name."! Du är nu med i utlottningen av en månads fri filmvisning. För att vara säker på att vi kan nå dig ifall du vinner, vänligen säkerställ att den epostadress du uppgivit på <a href=user.php>din medlemssida</a> stämmer.";
					$html .= '<p><a href="?endGame">Spela igen</a> <i>(påverkar inte dina chanser i utlottningen av en fri månad av filmvisning)</i>.</p>';
					unset($_SESSION['game']);
				} else {
					$html .= "<span class=loose>Nej, ".$this->winner." vinner! Katniss känner sig nedslagen och längtar hem till Distrikt 12.</span>";
					$html .= "<p><a href='?endGame'>Försök igen</a>.</p>";
				}
			} else {
				$html .= "";
				$html .= "<p><a href='?roll'>Kasta tärningen</a> | <a href='?save'>Spara poäng</a> | <a href='?endGame'>Avsluta spelet</a></p>";

			}
			$html .= '</div>';
		$html .= "</div>";

		// Reset game

		return $html;		
	}


	/**
	 * Rolls the dice of player whos turn it is and adds it as an event.
	 * 
	 * Only possible if game is not won.
	 */
	public function rollDice(){
		if(!($this->gameWon())) {
			$this->players[$this->activePlayer]->rollDice();
			if($this->players[$this->activePlayer]->getScore() >= 100) {
				$this->winner = $this->players[$this->activePlayer]->getName();
			}
			$html = $this->players[$this->activePlayer]->getRollAsImage();
			$this->addEvent();	
			return $html;					
		}
	}
	
	/**
	 * Saves score of player whos turn it is and adds it as an event.
	 * 
	 * Only possible if game is not won.
	 */
	public function saveScore(){
		if(!($this->gameWon())) {
			$this->players[$this->activePlayer]->saveScore();
			$this->addEvent();
		}
	}

	/**
	 * Controlls move for computer. To increase AI; update here :)
	 * 
	 * Saves score if difference between score and saved score is at least as big as $saveIfGap.
	 * Otherwise choses to roll the dice.
	 *
	 */
	private function makeComputerMove(){
		if(!($this->gameWon())) {
			$saveIfGap = 7;
			$score = $this->players[$this->activePlayer]->getScore();
			$savedScore = $this->players[$this->activePlayer]->getSavedScore();
			
			// Save score if at least $saveIfGap bigger than saved score.
			if ( ($score-$savedScore) >= $saveIfGap ){
				$this->saveScore();
			} else {
				$this->rollDice();
			}

			// If next is computer. Make move. 
			if ( $this->players[$this->activePlayer]->isComputer() ) {
				$this->makeComputerMove();
			}
		}
	}

	private function gameWon(){
		if(isset($this->winner)) {
			return true;
		}
		else {
			return false;
		}	
	}

	/**
	 * Add another event to the eventCounter.
	 *
	 */
	private function addEvent(){
		$this->eventCounter++;
		$this->updateActivePlayer();		
	}

	/**
	 * Update which players turn it is.
	 *
	 */
	private function updateActivePlayer() {
		$this->activePlayer = $this->eventCounter % $this->numPlayers;
	}
	
}