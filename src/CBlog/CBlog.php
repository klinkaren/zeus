<?php
/**
* Database wrapper, provides a database API for the framework but hides details of implementation.
*
*/
class CBlog extends CContent{

  /**
  * Members
  */
  private $title;
  private $published;
  private $data;
  private $filter;
  private $slug;
  private $acronym;
  private $slugSql;
  private $res;
  private $pageNav;
  private $rows;




  /**
   * CONSTRUCTOR
   *
   */
  public function __construct($options) {
  	parent::__construct($options);
    
    // Create instance of CPageNavigation
    $this->pageNav = new CPageNavigation($options);
  } 



  public function editBlog() {
    // Get all content
    $sql = '
      SELECT *, (published <= NOW()) AS available
      FROM content
      WHERE TYPE = "post";
    ';
    $res = $this->ExecuteSelectQueryAndFetchAll($sql);

    // Put results into a list
    $items = null;
    foreach($res AS $key => $val) {
      $items .= "<li>{$val->TYPE} (" . (!$val->available ? 'inte ' : null) . "publicerad): " . htmlentities($val->title, null, 'UTF-8') . " (<a href='content_edit.php?id={$val->id}'>editera</a> <a href='" . parent::getUrlToContent($val) . "'>visa</a> <a href='content_delete.php?id={$val->id}'>radera</a>)</li>\n";
    }
    $html = "<ul>$items</ul>";
    $html .= '<p><a href="content_blog.php?">Visa alla bloggposter</a></p>';
    $html .= '<p><a href="content_new.php">Skapa ny sida/bloggpost</a></p>';
    return $html;
  }

/* Behövs den här verkligen?
	public function viewBlog() {
    // Get all content
    $sql = '
      SELECT *, (published <= NOW()) AS available
      FROM content
      WHERE TYPE = "post";
    ';
    $res = $this->ExecuteSelectQueryAndFetchAll($sql);

    // Put results into a list
    $items = null;
    foreach($res AS $key => $val) {
      $items .= "<li>{$val->TYPE} (" . (!$val->available ? 'inte ' : null) . "publicerad): " . htmlentities($val->title, null, 'UTF-8') . " (<a href='content_edit.php?id={$val->id}'>editera</a> <a href='" . parent::getUrlToContent($val) . "'>visa</a> <a href='content_delete.php?id={$val->id}'>radera</a>)</li>\n";
    }
    $html  = $this->getBreadcrumbs();
    $html .= "<ul>$items</ul>";
    $html .= '<p><a href="nyheter.php?">Visa alla bloggposter</a></p>';
    $html .= '<p><a href="content_new.php">Skapa ny sida/bloggpost</a></p>';
    return $html;
  }
*/



  public function getPost() {

  	// Create text filter
	$this->filter = new CTextFilter();

	// Get parameters 
	$this->getParams();

	// Get content
	$this->res = $this->getContent();

	// Create html and return
	$html = $this->createHTML();
  	return $html;
  }


  public function getWidget($numPosts){
    is_numeric($numPosts) or die('Check: Number of posts in function getWidget() must be numeric.');
    $this->filter = new CTextFilter();
    $sql ="SELECT * FROM content WHERE TYPE='post' AND published < NOW() AND DELETED IS NULL ORDER BY published DESC LIMIT ".$numPosts.";";
    $res = $this->ExecuteSelectQueryAndFetchAll($sql);

    // Store latest posts in string
    $posts = "";
    foreach ($res as $val) {
      $data = isset($val->FILTER) ? $this->filter->doFilter(htmlentities($val->DATA, null, 'UTF-8'), $val->FILTER) : htmlentities($val->DATA, null, 'UTF-8');
      $published = new DateTime(htmlentities($val->published, null, 'UTF-8'));
      $posts .= '<div class="widgetPost threeParted"><header><h1>'.$val->title.'</h1><span class="subHeader">Publicerat: '.$published->format('Y-m-d').'</span></header>'.$this->getIntro($data, $val->slug, 300).'</div>';
    } 

    // Save everything in one string and return
    $html  = '<div class="blogWidget"><h1>Senaste nyheter</h1>';
    $html .= $posts;
    $html .= '</div>';
    return $html;

  }

  private function getBreadcrumbs() {
    $breadcrumb = "<ul class='breadcrumb'>\n<li><a href='nyheter.php'>Nyheter</a> »</li>\n";
    $breadcrumb .= isset($this->category)?"<li><a href='nyheter.php?category={$this->category}'>{$this->category}</a> » </li>\n":"";
    $breadcrumb .= isset($this->slug)?"<li><a href='nyheter.php?slug={$this->slug}'>{$this->title}</a> » </li>\n":"";
    $breadcrumb .= "</ul>\n";
    return $breadcrumb;
  }

  private function getParams() {
	$this->slug     = isset($_GET['slug'])     ? $_GET['slug']              : null;
  $this->category = isset($_GET['category']) ? $_GET['category']          : null;
	$this->acronym  = isset($_SESSION['user']) ? $_SESSION['user']->acronym : null;
  $this->page     = isset($_GET['page'])     ? $_GET['page']              : 1;
  $this->hits     = 7;
  }



  private function getContent() {
  $params = null;
  $this->slugSql = '1';
  $this->categorySql = '1';
  if($this->slug){
    $this->slugSql = 'slug = ?';
    $params[] = $this->slug; 
  }elseif ($this->category) {
    $this->categorySql = 'category = ?';
    $params[] = $this->category; 
  }else{
    $params = null; 
  }
	$sql = "
	SELECT *
	FROM content
	WHERE
	  type = 'post' AND
	  $this->slugSql AND
    $this->categorySql AND
	  published <= NOW() AND
    deleted IS NULL
	ORDER BY published DESC
	";
  // Set how many rows
  $rowsQuery = $this->ExecuteSelectQueryAndFetchAll($sql, $params);
  $this->rows = $this->setSqlRows($rowsQuery);
	
  $sql .= " LIMIT ".$this->hits." OFFSET " . (($this->page - 1) * $this->hits);
  $res = $this->ExecuteSelectQueryAndFetchAll($sql, $params);

  return $res;
  
  }

  private function setSqlRows($sql) {
    $i = 0;
    foreach ($sql as $key => $val) {
      $i+=1;
    }
    return $i;
  }

  private function getAside($side){
    // Get all distinct categories
    $res = parent::getDistinctCategories(true);

    // Create list of categories
    $categories = null;
    foreach ($res as $val) {
      // If selected category
      $class = (strtolower($val->category) == strtolower($this->category)) ? ' class="selected"' : "";
      $categories .= '<li'.$class.'><a href="nyheter.php?category='.$val->category.'">'.$val->category.'</a></li>';
    }

    // Put together as html and return
    $html  = '<aside class='.$side.'><nav>';
    $html .= '<h1>Kategorier</h1><ul class="categories">';
    $html .= $categories;
    $html .= '</ul></nav></aside>';
    return $html;
  }


  private function getIntro($text, $slug, $chars){
    $res = mb_substr($text, 0, $chars);
    $res = mb_substr($res, 0, strrpos($res, ' '));
    $cat = isset($this->category) ? '&category='.$this->category : "";
    $res .= '... <a class="readMore" href="nyheter.php?slug='.$slug.$cat.'">läs&nbspmer</a>';
    return $res;
  }

  private function createHTML() {
  	$html  = null;
    $html .= $this->getAside("right");

	if(isset($this->res[0])) {
	  foreach($this->res as $val) {
	    $this->title    = htmlentities($val->title, null, 'UTF-8');
	    $this->data     = isset($val->FILTER) ? $this->filter->doFilter(htmlentities($val->DATA, null, 'UTF-8'), $val->FILTER) : htmlentities($val->DATA, null, 'UTF-8');
      $published 		  = new DateTime(htmlentities($val->published, null, 'UTF-8'));


      // If more than one result, show intro with link
      $this->data = isset($this->res[1]) ? $this->getIntro($this->data, $val->slug, 250) : $this->data;

      // Add category to link if set
      $cat = $this->category ? '&category='.$this->category : "";

	    $html          .= <<<EOD
	<section class="left hasSide">
	  <article class="nyhet">
	  <header>
	  <h1><a href='nyheter.php?slug={$val->slug}{$cat}'>{$this->title}</a></h1>
	  <span class="created">Publiserat: {$published->format('Y-m-d')}</span>
	  </header>
	  <content>
	  {$this->data}
	  </content>
	  <hr>
	  </article>
	</section>
EOD;
	  }
    // Add page navigation if more than one post
    if(isset($this->res[1])){
      $html .= $this->pageNav->getPageNavigation($this->rows,$this->page, $this->hits, "left");     
    }
  }
  else if($this->slug) {
    $html = "Det fanns inte en sådan bloggpost.";
  }
  else if($this->category) {
    $html = "Det fanns inte en sådan kategori.";
  }
  else {
    $html = "Det fanns inga bloggposter.";
  }



  $result  = $this->getBreadcrumbs();
  $result .= $html;
	return $result;
  }


}