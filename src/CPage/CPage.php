<?php
/**
* Database wrapper, provides a database API for the framework but hides details of implementation.
*
*/
class CPage extends CContent{

  /**
  * Members
  */
  private $data;
  private $type;
  private $id;
  private $url;
  private $acronym;




  /**
   * CONSTRUCTOR
   *
   */
  public function __construct($options) {
  	parent::__construct($options);
  	$this->setParameters();

  } 


  private function setParameters(){

	// Create instance of CTextFilter
	$textFilter = new CTextFilter();

		// Get parameters 
	$this->url     = isset($_GET['url']) ? $_GET['url'] : null;
	$this->acronym = isset($_SESSION['user']) ? $_SESSION['user']->acronym : null;
	
	// Get content
	$sql = "
	SELECT *
	FROM Content
	WHERE
	  TYPE = 'page' AND
	  url = ? AND
	  published <= NOW();
	";
	$res = $this->ExecuteSelectQueryAndFetchAll($sql, array($this->url));

	if(isset($res[0])) {
	  $c = $res[0];
	}
	else {
	  die('Misslyckades: det finns inget innehåll.');
	}



	// Sanitize content before using it
	$this->title  = htmlentities($c->title, null, 'UTF-8');
	$this->data   = $textFilter->doFilter(htmlentities($c->DATA, null, 'UTF-8'), $c->FILTER);
	$this->id     = $c->id;
  	
  }

  public function gettitle() {
  	return $this->title;

  }

  public function getPage() {

	// Prepare editLink
	$editLink = $this->acronym ? "<a href='content_edit.php?id={$this->id}'>Uppdatera sidan</a>" : null;

	$html = <<<EDO
	<h1>{$this->title}</h1>
	{$this->data}

	{$editLink}
EDO;
	return $html;
  }


}