<?php


class CMovieSearch extends CDatabase {

  /**
  * Members
  */
  private $title;
  private $director;
  private $year1;
  private $year2;
  private $genre;
  private $page;
  private $order;
  private $orderby;
  private $query;
  private $sql;
  private $rows;
  private $hits;
  private $hitsOptions = array(4 => "4", 8 => "8", 12 => "12", 16 => "16", 24 => "24");
  private $htmlTable;
  private $image;
  private $YEAR;
  private $plot;
  private $price;
  private $imdb;
  private $youtube;
  private $published;
  private $created;
  private $save;
  private $currentCategories = array();


  /**
   * CONSTRUCTOR
   * Creates an instans of CDatabase, that connects to a MySQL database using PHP PDO.
   *
   */
  public function __construct($options) {
      parent::__construct($options);
      $this->htmlTable = new CHTMLTable();
  } 


  /**
   * Gives admin page
   *
   * @return @string with html-code
   */
  public function admin(){

    // Checks if user is logged in and of type admin.
    CUser::authenticatedAsAdmin() or die('Check: You must be logged in as admin to gain access to this page.');

    $this->getParams();
    $html = null;
    if(isset($_GET['editMovie'])){

      # skicka med id också...
      $html .= $this->editMovie($_GET['editMovie']);

    }elseif(isset($_GET['newMovie'])){
      $html .= $this->newMovie($_GET['newMovie']);

    }elseif(isset($_GET['deleteMovie'])){
      $this->deleteMovie($_GET['deleteMovie']);
      $html .= $this->editOverview();
    }elseif(isset($_GET['undeleteMovie'])){
      $this->undeleteMovie($_GET['undeleteMovie']);
      $html .= $this->editOverview();
    }else{
      $html .= $this->editOverview();
    }

    return $html;
  }

  /** 
   * Get HTML
   *
   * @return string html for search form and result.
   */
  public function getHTML() {

    $this->getParams();
    $this->sql = $this->getData();
    $res = $this->getForm();

    // Create navigation options for hits per page
    $res .= $this->getHits();

    // Create an html table, send the search query and get the result back as html.
    $htmlTable = new CHTMLTable();
    $res .= $htmlTable->getTable($this->sql);

    // Create navigation for pages
    $res .= $this->getPageNavigation();

    return $res;
  }



  /** 
   * Gives an overview of all movies with short introductions and links to every seperate movie
   *
   * @return string html for search form and result.
   */
  public function getOverview() {
    $res = null;
    $this->getParams();
    $this->sql = $this->getData();
    $res .= $this->getBreadcrumbs();
    $res .= $this->overviewForm();

    // Create navigation options for hits per page
    $res .= $this->getHits();

    // Send the search query and get the result back as html.
    $res .= $this->htmlTable->overview($this->sql);

    // Create navigation for pages
    $res .= $this->getPageNavigation(); 
    $res .= $this->totalHits();
    return $res;
  }


  public function getLatestTitles($numMovies){
    is_numeric($numMovies) or die('Check: Number of movies (numMovies) in function getLatestTitles() must be numeric.');
    $sql = "SELECT * FROM vmovie WHERE published < NOW() AND deleted IS NULL ORDER BY id DESC LIMIT ".$numMovies.";";
    $res = $this->ExecuteSelectQueryAndFetchAll($sql);
    $html  = '<div class="latestTitles"><h1>Nyinkomna titlar</h1>';
    $html .= $this->htmlTable->overview($res);
    $html .= '</div>';

    return $html;
  }


  public function getRandom($numMovies, $heading, $class){
    is_numeric($numMovies) or die('Check: Number of posts in function getRandom() must be numeric.');
    $sql = "SELECT * FROM vmovie WHERE published < NOW() AND deleted IS NULL ORDER BY RAND() DESC LIMIT ".$numMovies.";";

    $res = $this->ExecuteSelectQueryAndFetchAll($sql);
    $html  = '<div class="'.$class.'"><h1>'.$heading.'</h1>';


      $html .= $this->htmlTable->overview($res);

    $html .= '</div>';

    return $html;
  }


  private function editOverview(){
    $orderby = isset($this->orderby) ? $this->orderby : "id";
    $order = isset($this->order) ? $this->order : "desc";
    $sql = "SELECT * from vmovie ORDER BY $orderby $order";
    $res = $this->ExecuteSelectQueryAndFetchAll($sql);
    $html  = '<div class="editTitles"><h1>Redigera titlar</h1>';
    $html .= $this->htmlTable->getEditTable($res);
    $html .= '</div>';
    $html .= '<p><a href="?newMovie">Skapa ny film</a></p>';
    return $html;
  }


  private function deleteMovie($id){
    $sql = 'UPDATE movie SET deleted = NOW() WHERE id = ?';
    $params = array($id);
    $res = $this->ExecuteQuery($sql, $params);
  }


  private function undeleteMovie($id){
    $sql = 'UPDATE movie SET deleted = NULL WHERE id = ?';
    $params = array($id);
    $res = $this->ExecuteQuery($sql, $params);
  }


  private function deleteCategory($movieId, $genreId){
    $sql= "DELETE FROM movie2genre WHERE idMovie = ? AND idGenre = ?;";
    $params = array($movieId, $genreId);
    $this->ExecuteQuery($sql, $params);
  }


  private function addCategory($movieId, $genreId){
    $sql= "INSERT INTO movie2genre(idGenre, idMovie) VALUES(?, ?);";
    $params = array($genreId, $movieId);
    $this->ExecuteQuery($sql, $params);
  }


  private function getGenreIdFromName($cat){
    $sql = "SELECT id from genre where name = ?;";
    $params = array($cat);
    $res = $this->ExecuteSelectQueryAndFetchAll($sql, $params);
    isset($res[0]) or die ('error in function getGenreIdFromName');
    $genreId = $res[0]->id;
    return $genreId;
  }


  /**
   * Updates categories of movies in DB. 
   *
   * @param $id = ID of movie, @param @updated = array of updated categories 
   *
   */
  private function updateCategories($id, $updated){
    $this->setCurrentCategories();
    $current = $this->currentCategories;
    $add = array();
    $delete = array();

    // Delete movies (the current ones we wont find in the updated ones)
    foreach ($current as $c) {
      if (!in_array($c, $updated)) {
          $this->deleteCategory($id, $this->getGenreIdFromName($c));
      }
    }

    // Add movies (the updated ones we wont find in the current ones)
    foreach ($updated as $u) {
      if (!in_array($u, $current)) {
        $this->addCategory($id, $this->getGenreIdFromName($u));
      }
    }

    // Update current categories before rendering page (to get correct values in checkboxes)
    $this->setCurrentCategories();
  }


  private function editMovie($id){
    $html = null;
    if(isset($this->save)){
      $this->updateCategories($id, $_POST['selectedGenre']);
      // sql for update
      $sql = 'UPDATE movie SET title=?, director=?, YEAR=?, plot=?, image=?, price=?, imdb=?, youtube=?, published=?, updated=NOW() WHERE id = ?;';
      $params = array($this->title, $this->director, $this->YEAR, $this->plot, $this->image, $this->price, $this->imdb, $this->youtube, $this->published, $id);
      $res = $this->ExecuteQuery($sql, $params);
      $output = "Redigerade filmen '".$this->title."' med id ".$id;
      $html .= $this->getMovieForm("edit", $output);
      return $html;
    }else{

      // Set movie parameters from id
      $sql = "SELECT * from movie WHERE id = $id;";
      $res = $this->ExecuteSelectQueryAndFetchAll($sql);
      foreach($res as $key => $val) {
        $this->title=$val->title;
        $this->director=$val->director;
        $this->YEAR=$val->YEAR;
        $this->plot=$val->plot;
        $this->image=$val->image;
        $this->price=$val->price;
        $this->imdb=$val->imdb;
        $this->youtube=$val->youtube;
        $this->published=$val->published;
      }

      $html = $this->getMovieForm("edit");
    }
      return $html;
  }


  private function newMovieCheck(){
    if( isset($this->title) && isset($this->director) && isset($this->YEAR) && isset($this->plot) && isset($this->image) && isset($this->price) && isset($this->published) ){
      return true;
    } else {
      return false;
    }
  }


  private function newMovie(){

    $output = "";
    $html = "";
    if(isset($this->save)){
      if($this->newMovieCheck()){
        $image = "movie/".$this->image;

      $sql = "INSERT INTO movie(title, director, YEAR, plot, image, price, imdb, youtube, published, created) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, NOW());";
      $params = array($this->title, $this->director, $this->YEAR, $this->plot, $image, $this->price, $this->imdb, $this->youtube, $this->published);
      $res = $this->ExecuteQuery($sql, $params);
      if($res) {

        // set id of new movie
        $this->id = $this->LastInsertId();

        // save genres
        $this->updateCategories($this->id, $_POST['selectedGenre']);

        // save feedback
        $output .= 'Informationen sparades. Du kan nu redigera informationen om du vill.';
        $html = $this->getMovieForm("edit", $output);
      }else {
        // save feedback
        $output .= 'Informationen sparades EJ.<br><pre>' . print_r($this->ErrorInfo(), 1) . '</pre>';
        $html = $this->getMovieForm("new", $output);
      }
      } else {
        $output .= "Data sparades ej då nödvändig information saknas.";
        $html = $this->getMovieForm("new", $output);
      }
    } else {
      $html = $this->getMovieForm("new");
    }

    return $html;
  }


  private function setCurrentCategories(){
    $this->currentCategories = array();
    // Set currentCategories parameter from id
    $sql = "SELECT G.name from genre as G
              LEFT OUTER JOIN movie2genre AS M2G 
                ON G.id = M2G.idGenre
            WHERE M2G.idMovie = ?";
    $params=array($this->id);
    $res = $this->ExecuteSelectQueryAndFetchAll($sql, $params);
    foreach ($res as $key => $val) {
      $this->currentCategories[] = $val->name;
    }
  }


  private function getCategories(){
    $html = null;
    $selected = null;
    $this->setCurrentCategories();
    
    $allCategories = $this->getAllCategories();
    foreach ($allCategories as $key => $val) {

      //Set checked if category selected
      if (in_array($val, $this->currentCategories)) {
          $selected = "checked";
      }

      $html .= '<input type="checkbox" name="selectedGenre[]" value="'.$val.'"'.$selected.'>'.$val." ";
      $selected = null;
    }

    return $html;
  }


  private function getMovieForm($type = "new", $output = null){

    if($type == "new"){
      $heading = "Skapa ny film";
      $published =date('Y-m-d H:i:s');

    }elseif($type = "edit"){
      $heading = "Redigera film";
      $published = $this->published;

    }else{
      echo "Errormsg: Error in getMovieForm";

    }

    $html = "
    <h1>{$heading}</h1>
    <p><i>Fält märkta med * måste fyllas i.</i></p>
    <form method=post>
      <fieldset>
        <legend>Film</legend>
        <p><label>*Titel:<br><input type='text' name='title' value='$this->title'></label></p>
        <p><label>*Regissör:<br><input type='text' name='director' value='$this->director'></label></p>
        <p><label>*År:<br/><input type='text' name='YEAR' value='$this->YEAR'></label></p>
        <p><label>*Plot:<br/><textarea name='plot' rows=10 cols=100>$this->plot</textarea></label></p>   
        <p>{$this->getCategories()}</p> 
        <p><label>*Bild:<br/><input type='text' name='image' value='$this->image'></label></p>
        <p><label>*Pris:<br/><input type='number' name='price' value='$this->price'></label></p>
        <p><label>IMDb:<br/><input type='text' name='imdb' value='$this->imdb'></label></p>
        <p><label>Youtube:<br/><input type='text' name='youtube' value='$this->youtube'></label></p>
        <p><label>*Publiseringsdatum:<br/><input type='datetime' name='published' value='$published'></label></p>
        <input type='hidden' name='save' value='save'/>
        <p class=buttons><input type='submit' name='newMovie' value='spara'/> <input type='reset' value='Återställ'/></p>
      <p><strong>$output</strong></p> 
      </fieldset>
    </form>";
    return $html;
  }

  private function totalHits(){
    return "<div class='totalResults smallText'> Totalt antal träffar: ".$this->rows."</div>";
  }


  /**
   * Get html for search form
   *
   * @return string html for search form
   */
  private function overviewForm() {
    $res = "<form>
      <h2 class=noTrailingSpace>Sök/Filtrera/Sortera</h2>
      <div class=overviewMovies>
        <div class='overviewSearch'>
          <label>Sök på titel: </label>
          <input class='searchbox' type='text' name='title' value='{$this->title}' onblur='onBlur(this)' onfocus='onFocus(this)'>
          <label>och/eller regissör: </label>
          <input class='searchbox' type='search' name='director' value='{$this->director}' onblur='onBlur(this)' onfocus='onFocus(this)'>
          <button class='search' type='submit' name='submit'>Sök</button>
        </div>
      
        <div class='overviewGenre'>
          {$this->getOverviewGenre()}
        </div>
        <div class='overviewSort'>
          {$this->getOverviewSort()}
        </div>
      </div>
    </form>";
    return $res;
  } 


  private function getOverviewSort(){  
    $type = [
      ['title','Titel'],
      ['YEAR','År'],
      ['director','Regissör'],
    ];
    
    $html = "<nav><span class='showAll'><a href='?'>Återställ</a></span><ul class='sortList'>Sortera enligt: ";
    foreach($type as list($name, $namn)) {
      $selected = $name==$this->orderby ? " class='selected' " : null;
      $html .= "<li{$selected}><a href='?orderby={$name}";
      $html .= isset($this->genre)?('&genre='.$this->genre):null;
      $html .= isset($this->title)?('&title='.$this->title):null;
      $html .= isset($this->hits)?('&hits='.$this->hits):null;
      $html .= isset($this->director)?('&director='.$this->director):null;
      $html .= "''>{$namn}</a></li>";
    }
    $html .= "</ul></nav>";

    return $html;
  }


  /**
   * Bygg genreList
   *
   * @param string htmlkod
   */
  public function getOverviewGenre() {
    $sql = "SELECT DISTINCT G.name
      FROM genre AS G
        INNER JOIN movie2genre AS M2G
        ON G.id = M2G.idGenre
        GROUP BY G.name ASC";

    $res = $this->ExecuteSelectQueryAndFetchAll($sql,null,false);

    $html = "<ul class='genreList'>\n";
    foreach($res as $item) {
      $selected = $item->name==$this->genre ? " class='selected' " : null;
      $html .= "<li{$selected}><a href='filmer.php?genre={$item->name}";
      $html .= isset($this->orderby)?'&orderby='.$this->orderby:null;
      $html .= isset($this->title)?'&title='.$this->title:null;
      $html .= isset($this->hits)?'&hits='.$this->hits:null;
      $html .= isset($this->director)?'&director='.$this->director:null;
      $html .= "'>{$item->name}</a></li>\n";
    }
    $html .= "</ul>\n</nav>";
    return $html;
  }


  private function getGenreQuery(){
    $res = array();
    foreach ($this->genre as $val) {
      $res[] = 'genre LIKE "%'.$val.'%"';
    }
    return $res;
  }


   /**
   * Get data from database
   *
   * @return string html for search result.
   */
  private function getData() {
    $limit = "";

    $where = array();
    $params = array();

    // Search title
    if($this->title) {

      $where[] = "title LIKE ?";
      $params[] = "%".$this->title."%";
    } 

    
    // Search director
    if($this->director) {

      $where[] = "director LIKE ?";
      $params[] = "%".$this->director."%";
    } 

    // Search year
    if($this->year1 && $this->year2) {
      $where[] = "YEAR >= ?";
      $where[] = "YEAR <= ?";
      $params[] = $this->year1;
      $params[] = $this->year2;
    }

    //Search genre
    if($this->genre){
        $where[] = "genre LIKE ?";
        $params[] = "%".$this->genre."%";
    }

    // Pagination
    if($this->hits && $this->page) {
      $limit = " LIMIT $this->hits OFFSET " . (($this->page - 1) * $this->hits);
    }

    // Create sql-query (only show movies that are published and not deleted)
    $this->query = "SELECT * FROM vmovie WHERE published < NOW() AND deleted IS NULL";

    if(!empty($params)) {
      $this->query .= " AND ".join(" AND ",$where);
    }

    // Set how many rows
    $rowsQuery = $this->ExecuteSelectQueryAndFetchAll($this->query, $params);
    $this->rows = $this->setSqlRows($rowsQuery);

    $this->query .= " GROUP BY $this->orderby $this->order";
    $this->query .= " $limit";

    // Get data
    return $this->ExecuteSelectQueryAndFetchAll($this->query, $params);

  }

  private function setSqlRows($sql) {
    $i = 0;
    foreach ($sql as $key => $val) {
      $i+=1;
    }
    return $i;
  }


  /**
   * Get html for search form
   *
   * @return string html for search form
   */
  private function getForm() {
    $res = "<form>
      <fieldset>
        <legend>Sök</legend>
        <p><label>Titel: </label><input type='search' name='title' value='{$this->title}'></p>
        <p>eller</p>
        <p>
          <label>Skapad mellan åren: 
            <input type='text' name='year1' value='{$this->year1}'/> - <input type='text' name='year2' value='{$this->year2}'/>
          </label>
        </p>
        <p>eller</p>
        {$this->getGenreList()}
        {$this->getSortOptions()}
        <input type='hidden' name='genre' value='{$this->genre}'/> 
        <p><button type='submit' name='submit'>Sök</button></p>
        <p><a href='?'><strong>Visa alla</strong></a></p>
      </fieldset>
     
    </form>";
    return $res;
  } 

  private function getSortOptions(){
    $sortOptions = '<p>
        <label>Sortera efter:
          <select name="orderby">
            <option value="id">Id</option>
            <option value="title">Titel</option>
            <option value="YEAR">År</option>
          </select>
        </label>
        <label>Ordning:
          <select name="order">
              <option value="asc">Stigande</option>
              <option value="desc">Fallande</option>
          </select>
        </label>
      </p>';

    return $sortOptions;
  }


 /**
   * Bygg genreList
   *
   * @param string htmlkod
   */
  public function getGenreList() {

    $sql = "SELECT DISTINCT G.name
            FROM genre AS G
              INNER JOIN movie2Genre AS M2G
              ON G.id = M2G.idGenre";

    $res = $this->ExecuteSelectQueryAndFetchAll($sql,null,false);
    $genreList = "<p>Välj genre:<br/>";
    foreach($res as $key => $val) {
        $genreList .="<a href='?genre={$val->name}'>{$val->name}</a> ";
    }
    $genreList .="</p>";

    return $genreList;
  }


  /**
   *Set parametres value to same value as input box
   */
  private function getParams(){

    // Get parameters 
    $this->title   = isset($_GET['title'])   ? $_GET['title'] : null;
    $this->title   = isset($_POST['title'])   ? $_POST['title'] : $this->title;
    $this->director= isset($_GET['director'])   ? $_GET['director'] : null;
    $this->director= isset($_POST['director'])   ? $_POST['director'] : $this->director;
    $this->hits    = isset($_GET['hits'])    ? $_GET['hits']  : 8;
    $this->page    = isset($_GET['page'])    ? $_GET['page']  : 1;
    $this->year1   = isset($_GET['year1']) && !empty($_GET['year1']) ? $_GET['year1'] : null;
    $this->year2   = isset($_GET['year2']) && !empty($_GET['year2']) ? $_GET['year2'] : null;
    $this->orderby = isset($_GET['orderby']) ? $_GET['orderby'] : 'id';
    $this->order   = isset($_GET['order'])   ? strtolower($_GET['order'])   : 'asc';
    $this->genre   = isset($_GET['genre'])  ? $_GET['genre'] : null;
    $this->YEAR    = isset($_POST['YEAR'])   ? $_POST['YEAR'] : null;
    $this->plot    = isset($_POST['plot'])   ? $_POST['plot'] : null;
    $this->image   = isset($_POST['image'])   ? $_POST['image'] : null;
    $this->price   = isset($_POST['price'])   ? $_POST['price'] : null;
    $this->imdb    = isset($_POST['imdb'])   ? $_POST['imdb'] : null;
    $this->youtube = isset($_POST['youtube'])   ? $_POST['youtube'] : null;
    $this->published = isset($_POST['published'])   ? $_POST['published'] : null;
    $this->save    = isset($_POST['save'])? $_POST['save']: null;
    $this->id      = isset($_GET['editMovie']) ? $_GET['editMovie'] : null;
    //$this->currentCategories = isset($_POST['selectedGenre']) ? $_POST['selectedGenre'] : $this->currentCategories;

    // Remove empty values
    $this->title   = empty($this->title)   ? null : $this->title;
    $this->director   = empty($this->director)   ? null : $this->director;
    $this->YEAR   = empty($this->YEAR)   ? null : $this->YEAR;
    $this->plot   = empty($this->plot)   ? null : $this->plot;
    $this->image   = empty($this->image)   ? null : $this->image;


    // Check that incoming parameters are valid
    is_numeric($this->hits) or die('Check: Hits must be numeric.');
    is_numeric($this->page) or die('Check: Page must be numeric.');
    //is_numeric($this->YEAR) or die('Check: YEAR must be numeric.');
    is_numeric($this->year1) || !isset($this->year1)  or die('Check: Year must be numeric or not set.');
    is_numeric($this->year2) || !isset($this->year2)  or die('Check: Year must be numeric or not set.');
    //is_numeric($this->YEAR)  || !isset($this->YEAR)   or die('Check: YEAR must be numeric or not set.');
    //is_numeric($this->price) || !isset($this->price)  or die('Check: Price must be numeric or not set.');
  } 

  

  private function getQueryString($options, $prepend='?') {
    // parse query string into array
    $query = array();
    parse_str($_SERVER['QUERY_STRING'], $query);

    // Modify the existing query string with new options
    $query = array_merge($query, $options);

    // Return the modified querystring
    return $prepend . htmlentities(http_build_query($query));
  }


  private function getPageNavigation() {

    // Variables
    $min = 1;
    $hits = $this->hits;
    $max = ceil($this->rows/$hits);
    $page = $this->page;


    $nav  = "<a href='" . $this->getQueryString(array('page' => $min)) . "'>&lt;&lt;</a> ";
    $nav .= "<a href='" . $this->getQueryString(array('page' => ($page > $min ? $page - 1 : $min) )) . "'>&lt;</a> ";

    for($i=$min; $i<=$max; $i++) {
      $nav .= "<a href='" . $this->getQueryString(array('page' => $i)) . "'>$i</a> ";
    }

    $nav .= "<a href='" . $this->getQueryString(array('page' => ($page < $max ? $page + 1 : $max) )) . "'>&gt;</a> ";
    $nav .= "<a href='" . $this->getQueryString(array('page' => $max)) . "'>&gt;&gt;</a> ";

    // Create div
    $html = '<div class="overviewPageNavigation">';
    $html .= $nav;
    $html .= '</div>';
    return $html;
  }


  private function getBreadcrumbs() {
    $breadcrumb = "<ul class='breadcrumb'>\n<li><a href='filmer.php'>Alla filmer</a> »</li>\n";
    $breadcrumb .= "</ul>\n";
    return $breadcrumb;
  }


  /**
   * Create links for hits per page.
   *
   * @param array $hits a list of hits-options to display.
   * @return string as a link to this page.
   */
  private function getHits() {
    $html = "<div class='fullWidth'><nav class='galleryDropDown'>
          <form method='get'>
            <label for='input1'>Visa:</label>
            <input type='hidden' name='genre' value='{$this->genre}'>
            <input type='hidden' name='title' value='{$this->title}'>
            <input type='hidden' name='director' value='{$this->director}'>
            <input type='hidden' name='orderby' value='{$this->orderby}'>
            <select id='input1' name='hits' onchange='form.submit();'>";

    foreach($this->hitsOptions as $value=>$name){
        if($value == $this->hits) {
          $html .= "<option selected='selected' value='".$value."'>".$name."</option>";
        }
        else {
          $html .= "<option value='".$value."'>".$name."</option>";
        }
    }
    $html .="</select></form></nav></div>";
    return $html;
  }


  /**
   * Gives array of all categories in database.
   *
   * @return array() of all genres 
   */
  public function getAllCategories() {
    $cats = array();
    $sql = "SELECT DISTINCT G.name
      FROM genre AS G
        INNER JOIN movie2genre AS M2G
        ON G.id = M2G.idGenre
        ORDER BY name ASC";

    $res = $this->ExecuteSelectQueryAndFetchAll($sql,null,false);
  
    foreach($res as $key => $val) {
      $cats[] = $val->name;
    }
    return $cats;
  }

}