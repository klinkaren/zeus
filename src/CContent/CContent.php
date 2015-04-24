<?php
/**
* Database wrapper, provides a database API for the framework but hides details of implementation.
*
*/
class CContent extends CDatabase{

  /**
  * Members
  */
  private $id;
  private $title;
  private $slug;
	private $url;
	private $data;
	private $type;
	private $filter;
	private $published;
	private $save;
	private $acronym;
	private $category;
	private $htmlTable;


  /**
   * CONSTRUCTOR
   *
   */
  public function __construct($options) {
		parent::__construct($options);
		$this->htmlTable = new CHTMLTable();
  } 


  /**
   * Gives all posts or parts from database.  
   *
   * @param  string: "post" or "part"
   * @return string: html with results from db-query
   */
  public function admin($type = "post") {

  	// Checks if user is logged in and of type admin.
	CUser::authenticatedAsAdmin() or die('Check: You must be logged in as admin to gain access to this page.');
  	
  	// Unset variables
  	$html  = null;
    $parts = null;
    $posts = null;

    // Get all content
    $sql = '
      SELECT *, (published <= NOW()) AS available
      FROM content
      WHERE deleted is null AND
      TYPE = ?
      ORDER BY published DESC;
    ';
    $params = array($type);
    $res = $this->ExecuteSelectQueryAndFetchAll($sql, $params);

    // Put results into strings
    foreach($res AS $key => $val) {

    	// Clean and save publish date as datetime.
    	$published = new DateTime(htmlentities($val->published, null, 'UTF-8'));

    	if ($val->TYPE=="part") {
      	$parts .= "<li><a href='content_edit.php?id={$val->id}'>". htmlentities($val->title, null, 'UTF-8') ."</a></li>\n";
     	}elseif($val->TYPE=="post"){
     		$status = $val->available ? "" : " class='notPublished'";
				$posts .= "<tr".$status."><td>".date_format($published, 'Y-m-d') . "<td>" . substr(htmlentities($val->title, null, 'UTF-8'),0,40) . "...</td><td>".htmlentities($val->category, null, 'UTF-8')."</td><td><a href='" . $this->getUrlToContent($val) . "'>visa</a></td><td><a href='content_edit.php?id={$val->id}'>editera</a></td><td><a href='content_delete.php?id={$val->id}'>radera</a></td></tr>";
    	}
    }

    // Create html and return
    if ($type == "part"){
    	$html .= "<div class=adminParts>";
    	$html .= "<h1>Administrera sidans delar</h1>";
    	$html .= "<ul>$parts</ul></div>";
    } elseif($type =="post"){
    	$html .= "<div class=adminNews>";
    	$html .= "<h1>Administera nyheter</h1>";
    	$html .= '<table><tr><th>Publisering</th><th>Rubrik</th><th>Kategori</th><th></th><th></th><th></th>';
			$html .= 	$posts;
	    $html .= "</table>";
    	$html .= '<p><a href="content_new.php">Skapa nyhet</a></p>';
    }

    return $html;
  }

  
  private function setParameters() {
  	// Get parameters 
		$this->id       = isset($_POST['id'])    ? strip_tags($_POST['id']) : (isset($_GET['id']) ? strip_tags($_GET['id']) : null);
		$this->title    = isset($_POST['title']) ? $_POST['title'] : null;
		$this->slug     = isset($_POST['slug'])  ? $_POST['slug']  : null;
		$this->url      = isset($_POST['url'])   ? strip_tags($_POST['url']) : null;
		$this->data     = isset($_POST['DATA'])  ? $_POST['DATA'] : array();
		$this->type     = isset($_POST['TYPE'])  ? strip_tags($_POST['TYPE']) : array();
		$this->filter   = isset($_POST['FILTER']) ? $_POST['FILTER'] : array();
		$this->published= isset($_POST['published'])  ? strip_tags($_POST['published']) : array();
		$this->save     = isset($_POST['save'])  ? true : false;
		$this->acronym  = isset($_SESSION['user']) ? $_SESSION['user']->acronym : null;

		// If newcategory use that, else check if old category chosen.
		$this->category = !empty($_POST['newcategory']) ? $_POST['newcategory'] : (!empty($_POST['category']) ? ($_POST['category']) : null);
  }

  protected function slugify($str) {
        
		// String to lowercase and trim whitespace
		$str = mb_strtolower(preg_replace("/(^\s+)|(\s+$)/us", "", $str), "UTF-8");
		// Replace å, ä, ö with a, a, o
		$arr = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
		$len = count($arr);
		for($i=0; $i<$len; $i++) {
		    if($arr[$i] == 'å') {
		        $arr[$i] = 'a';
		    }

		    if($arr[$i] == 'ä') {
		        $arr[$i] = 'a';
		    }

		    if($arr[$i] == 'ö') {
		        $arr[$i] = 'o';
		    }
		}
		$str = implode('',$arr);

		$str = preg_replace('/[^a-z0-9-]/', '-', $str);
		$str = trim(preg_replace('/-+/', '-', $str), '-');
		return $str;
   } 



  public function getCreateForm() {

  	$this->setParameters();

		// Check that incoming parameters are valid
		isset($this->acronym) or die('Du måste <a href="loginout.php">logga in</a> för att skapa en sida eller nyhet.');

			// Check if form was submitted
		$output = null;
		if($this->save) {
			$this->filter = empty($this->filter) ? null : $this->filter;
			$this->category = empty($this->category) ? null : $this->category;
			$this->slug = $this->slugify(strip_tags($this->title));
    	$sql = "INSERT INTO content(title, slug, TYPE, DATA, FILTER, published, created, category) VALUES(?, ?, ?, ?, ?, ?, NOW(), ?)";
   		$params = array($this->title, $this->slug, $this->type, $this->data, $this->filter, $this->published, $this->category);
    	$res = $this->ExecuteQuery($sql, $params);
		  if($res) {

		    // save feedback
		    $output = 'Informationen sparades.';
		  }
		  else {
		  	// save feedback
		    $output = 'Informationen sparades EJ.<br><pre>' . print_r($this->ErrorInfo(), 1) . '</pre>';
		  }
		}


		$now =date('Y-m-d H:i:s');
		
		$html = "";
		$html = <<<EDO
<h1>Skapa</h1>
<form method=post>
  <fieldset>
		<legend>Skapa</legend>
	    <p><label>Titel:<br><input type="text" name="title"></label></p>
  		<p><label>Text:<br/><textarea name='DATA' rows="10" cols="100"></textarea></label></p>		
			<p>
			{$this->getCategoryDropDown()}
			<label>...eller skapa en ny kategori: <input type="text" name="newcategory" value=""></label></p>
			</p>
		  <p><label>Filter:<br/><input type='text' name='FILTER'></label></p>
		  <p><label>Publiseringsdatum:<br/><input type='text' name='published' value="$now"></label></p>
			<input type='hidden' name='TYPE' value='post'/>
		  <p class=buttons><input type='submit' name='save' value='Spara'/> <input type='reset' value='Återställ'/></p>
	    </fiedlset>
	    <p><strong>$output</strong></p> 
  </fieldset>
</form>
EDO;

		return $html;

  }

  /**
   * Gives array of all categories with published posts 
   *
   */
  protected function getDistinctCategories($onlyPublished = false){
  	// Get all distinct categories
  	$status = $onlyPublished ? "AND published < NOW()" : "";
  	$sql = "SELECT DISTINCT category from content WHERE category IS NOT NULL $status ORDER BY category ASC";
  	$res = $this->ExecuteSelectQueryAndFetchAll($sql);
  	return $res;
  }

  /**
   * Gives a dropdown with all unique categories for content.
   *
   * @return string: HTML for a dropdown (select)
   */
  private function getCategoryDropDown(){
  	// Get all distinct categories
  	$res = $this->getDistinctCategories(false);
  	
  	// Create options from sql-result
		$options = null;

  	foreach ($res as $key => $val) {
  		$selected = $val->category == $this->category ? "selected" : null;
  		$options .= '<option '.$selected.' value="'.$val->category.'">'.$val->category.'</option>';
  	}

  	// Create html and return
		$html  = '<label>Kategori:<br/><select name="category">';
		$html .= $options;
		$html .= '</select></label>';
  	return $html;
  }

	public function getEditForm() {

		$this->setParameters();

		// Check that incoming parameters are valid
		isset($this->acronym) or die('Check: You must login to edit.');
		is_numeric($this->id) or die('Check: Id must be numeric.');

		// Check if form was submitted
		$output = null;
		if($this->save) {
		  $sql = '
		    UPDATE content SET
		      title   = ?,
		      slug    = ?,
		      url     = ?,
		      DATA    = ?,
		      FILTER  = ?,
		      published = ?,
		      category = ?,
		      updated = NOW()
		    WHERE 
		      id = ?
		  ';

		  // Set to null if empty
		  $this->url = empty($this->url) ? null : $this->url;
		  $this->filter = empty($this->filter) ? null : $this->filter;
			$this->category = empty($this->category) ? null : $this->category;
			$this->slug = $this->slugify(strip_tags($this->title));

			// Query db
		  $params = array($this->title, $this->slug, $this->url, $this->data, $this->filter, $this->published, $this->category, $this->id);
		  $res = $this->ExecuteQuery($sql, $params);

		  if($res) {
		    $output = 'Informationen sparades.';
		  }
		  else {
		    $output = 'Informationen sparades EJ.<br><pre>' . print_r($this->ErrorInfo(), 1) . '</pre>';
		  }
		}

		// Select from database
		$sql = 'SELECT * FROM content WHERE id = ?';
		$res = $this->ExecuteSelectQueryAndFetchAll($sql, array($this->id));

		if(isset($res[0])) {
		  $c = $res[0];
		}
		else {
		  die('Misslyckades: det finns inget innehåll med sådant id.');
		}

		// Sanitize content before using it.
		# borde inte det här vara $this->title etc?
		$title  = htmlentities($c->title, null, 'UTF-8');
		$slug   = htmlentities($c->slug, null, 'UTF-8');
		$url    = htmlentities($c->url, null, 'UTF-8');
		$data   = htmlentities($c->DATA, null, 'UTF-8');
		$type   = htmlentities($c->TYPE, null, 'UTF-8');
		$filter = htmlentities($c->FILTER, null, 'UTF-8');
		$published = htmlentities($c->published, null, 'UTF-8');
		$this->category  = htmlentities($c->category, null, 'UTF-8');


		$cats = $type == "post" ? "<p>{$this->getCategoryDropDown()}<label>...eller skapa en ny kategori: <input type='text' name='newcategory' value=''></label></p></p>" :"";
		$html = "";
		$html = <<<EDO
<h1>Uppdatera</h1>
<form method=post>
  <fieldset>
  <legend>Uppdatera innehåll</legend>
  <input type='hidden' name='id' value='{$this->id}'/>
  <p><label>Titel:<br/><input type='text' name='title' value='{$title}'/></label></p>
  <p><label>Slug:<br/><input type='text' name='slug' value='{$slug}'/></label></p>
  <p><label>Text:<br/><textarea name='DATA' rows="5" cols="80">{$data}</textarea></label></p>
  {$cats}
  <p><label>Filter:<br/><input type='text' name='FILTER' value='{$filter}'/></label></p>
  <p><label>Publiseringsdatum:<br/><input type='text' name='published' value='{$published}'/></label></p>
  <p class=buttons><input type='submit' name='save' value='Spara'/> <input type='reset' value='Återställ'/></p>
  <output>{$output}</output>
  </fieldset>
</form>
EDO;

		return $html;
	}



public function getDeleteForm() {

		$this->setParameters();

		// Check that incoming parameters are valid
		isset($this->acronym) or die('Check: You must login to delete.');
		is_numeric($this->id) or die('Check: Id must be numeric.');

		// Check if form was submitted
		$output = null;
		if($this->save) {
		  $sql = '
		    UPDATE content SET
		      deleted = NOW()
		    WHERE 
		      id = ?
		  ';
		  $url = empty($this->url) ? null : $this->url;
		  $params = array($this->id);
		  $res = $this->ExecuteQuery($sql, $params);
		  if($res) {
		    $output = 'Informationen raderades.';
		  }
		  else {
		    $output = 'Informationen raderades EJ.<br><pre>' . print_r($db->ErrorInfo(), 1) . '</pre>';
		  }
		}

		// Select from database
		$sql = 'SELECT * FROM content WHERE id = ?';
		$res = $this->ExecuteSelectQueryAndFetchAll($sql, array($this->id));

		if(isset($res[0])) {
		  $c = $res[0];
		}
		else {
		  die('Misslyckades: det finns inget innehåll med sådant id.');
		}

		// Sanitize content before using it.
		$title  = htmlentities($c->title, null, 'UTF-8');
		$slug   = htmlentities($c->slug, null, 'UTF-8');
		$url    = htmlentities($c->url, null, 'UTF-8');
		$data   = htmlentities($c->DATA, null, 'UTF-8');
		$type   = htmlentities($c->TYPE, null, 'UTF-8');
		$filter = htmlentities($c->FILTER, null, 'UTF-8');
		$published = htmlentities($c->published, null, 'UTF-8');

		$html = "";
		$html = <<<EDO
<h1>Ta bort</h1>
<form method=post>
  <fieldset>
  <legend>Uppdatera innehåll</legend>
  <input type='hidden' name='id' value='{$this->id}'/>
  <p><label>Titel:<br/><input type='text' name='title' value='{$title}'/></label></p>
  <p><label>Text:<br/><textarea name='DATA' rows="5" cols="80">{$data}</textarea></label></p>
  <p class=buttons><input type='submit' name='save' value='Ta bort'/></p>
  <output>{$output}</output>
  </fieldset>
</form>
EDO;

		return $html;
	}
  


  public function viewAll() {
    // Get all content
    $sql = '
      SELECT *, (published <= NOW()) AS available
      FROM content
      WHERE deleted is null;
    ';
    $res = $this->ExecuteSelectQueryAndFetchAll($sql);

    // Put results into a list
    $items = null;
    foreach($res AS $key => $val) {
      $items .= "<li>{$val->TYPE} (" . (!$val->available ? 'inte ' : null) . "publicerad): " . htmlentities($val->title, null, 'UTF-8') . " (<a href='content_edit.php?id={$val->id}'>editera</a> <a href='" . $this->getUrlToContent($val) . "'>visa</a> <a href='content_delete.php?id={$val->id}'>radera</a>)</li>\n";
    }
    $html = "<ul>$items</ul>";
    $html .= '<p><a href="nyheter.php?">Visa alla bloggposter</a></p>';
    $html .= '<p><a href="content_new.php">Skapa ny sida/bloggpost</a></p>';
    return $html;
  }




	/**
	 * Create link to content based on type.
	 *
	 * @param object $content to link to.
	 * @return string with url to display content.
	 */
	protected function getUrlToContent($content) {
	  switch($content->TYPE) {
	    case 'page': 
	    	return "content_page.php?url={$content->url}"; 
	    	break;
	    case 'post': 
	    	return "nyheter.php?slug={$content->slug}"; 
	    	break;
	    default: 
	    	return null; 
	    	break;
	  }
	}




}