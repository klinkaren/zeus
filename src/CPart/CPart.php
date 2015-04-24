<?php
/**
* Database wrapper, provides a database API for the framework but hides details of implementation.
*
*/
class CPart extends CContent{

  /**
  * Members
  */
  private $data;
  private $type;
  private $id;
  private $url;
  private $admin = false;




  /**
   * CONSTRUCTOR
   *
   */
  public function __construct($options) {
  	parent::__construct($options);
		$this->setAdmin();

  } 


  /**
   * Checks if logged in as admin
   *
   */
  private function setAdmin() {
  	$this->admin = isset($_SESSION['user']) ? ( ($_SESSION['user']->acronym) == "admin" ? true : false ) : null;
  }



  /**
   * Gives part from database
   *
   * @param  $part string: Title of part that should be returned. 
   * @return $html string: HTML with title and data for the part. 
   */
  public function getPart($part) {
	// Create instance of CTextFilter
	$textFilter = new CTextFilter();

	// Get content
	$sql = "
	SELECT *
	FROM content
	WHERE
	  TYPE = 'part' AND
	  title = '$part';
	";
	$res = $this->ExecuteSelectQueryAndFetchAll($sql, array($this->url));

	if(isset($res[0])) {
	  $c = $res[0];
		// Sanitize content before using it
		$this->title  = htmlentities($c->title, null, 'UTF-8');
		$this->data   = $textFilter->doFilter(htmlentities($c->DATA, null, 'UTF-8'), $c->FILTER);
		$this->id     = $c->id;

		// Prepare editLink
		$editLink = $this->admin ? "<span class='editLink'><a href='content_edit.php?id={$this->id}'>uppdatera texten</a></span>" : null;
		
		// Prepare html
		$html = <<<EDO
		<h3>{$this->title}</h3>
		{$this->data}
		{$editLink}
EDO;
	}
	else {
	  $html = null;
	}

	return $html;
  }


}