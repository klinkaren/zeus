<?php
/**
 * Database wrapper, provides a database API for the framework but hides details of implementation.
 *
 */
class CUsers extends CUser{
 
  /**
   * Members
   */
  private $db      = null;
  private $edit    = false;
  private $orderby;
  private $order;
  private $hits    = 8; 
  private $page    = 1;
  private $rows;
  private $query;
  private $htmlTable;
  private $name;
  private $acronym;
  private $email;
  private $website;



  /**
   * Constructor creating a user object.
   *
   * @param array $options containing details for connecting to the database.
   *
   */
  public function __construct($db) {
    $this->db = $db;
    $this->htmlTable = new CHTMLTable();
    $this->edit = CUser::authenticatedAsAdmin();
  }


  /**
   * The main function of this class
   *
   * @return string htmlcode
   */
  public function __toString(){
    $html = null;
    $this->setParams();

    if(!empty($_GET['edit'])){
      $html = $this->editUser($_GET['edit']);
      // Get edit form for the specified user
    }else{

      // delete or undelete users
      if(!empty($_GET['delete'])){
        $html .= $this->deleteUser($_GET['delete']);
      }elseif(!empty($_GET['undelete'])){
        $html .= $this->undeleteUser($_GET['undelete']);
      }

      // Show all users

      // Variables
      $table  = null;
      $pageNav = null;
      $hitsOptions = null;

      // Get all users
      $res = $this->getAllUsers();

      // Declare array of what to get and get table
      $values       = array('id' => 'Id', 'acronym' => 'Alias', 'name' => 'Namn', 'type' => 'Typ', 'email' => 'E-post', 'website' => 'Webbsida');
      $table       .= $this->htmlTable->getTableAsHtml($res, $values, $this->edit);

      // Get page navigation
      $pageNav     .= $this->getPageNavigation();

      // Declare array of pageHitsOptions and get dropdown
      $dropOptions =array(4 => 4,8 => 8,16 =>16, 32 =>32);
      $hitsOptions .= $this->getDropdown($dropOptions, $this->hits);
      
      // Save to string
      $html .= "<h1>Alla medlemmar</h1>";
      $html .= $hitsOptions;
      $html .= $table;
      $html .= $pageNav;
      $html .= '<p><a href="new_user.php">Skapa ny medlem</a></p>';
      //$html .= $this->getAllUsersAsHtml(true);;
    }
    return $html;
  }

  private function deleteUser($id){
    $sql = 'UPDATE user SET deleted = NOW() WHERE id = ?';
    $params = array($id);
    $res = $this->db->ExecuteQuery($sql, $params);
  }


  private function undeleteUser($id){
    $sql = 'UPDATE user SET deleted = NULL WHERE id = ?';
    $params = array($id);
    $res = $this->db->ExecuteQuery($sql, $params);
  }


  private function setPostInfo(){
    $this->name = !empty($_POST['name']) ? $_POST['name'] : null;
    $this->acronym = !empty($_POST['acronym']) ? $_POST['acronym'] : null;
    $this->email = !empty($_POST['email']) ? $_POST['email'] : null;
    $this->website = !empty($_POST['website']) ? $_POST['website'] : null;
  }



  private function setDbInfo($id){
    $sql = "SELECT name, acronym, website, email FROM user WHERE id = ?;";
    $params = array($id);
    $res = $this->db->ExecuteSelectQueryAndFetchAll($sql,$params);
    foreach ($res as $val) {
      $this->name = $val->name;
      $this->acronym = $val->acronym;
      $this->email = $val->email;
      $this->website = $val->website;
    }
  }

  private function editUser($id){
    $html = null;
    $output = null;

    if(isset($_POST['saveUserInformation'])){

      // Get info from $_POST
      $this->setPostInfo();

      // Save info to db if name and acronym are set.
      if(isset($this->name) && isset($this->acronym)){

        // Save to db
        $sql = "UPDATE user SET name=?, acronym = ?, website = ?, email = ?, updated = NOW() WHERE id = ?;";
        $params = array($this->name, $this->acronym, $this->website, $this->email, $id);
        $res = $this->db->ExecuteQuery($sql,$params);

        $output = "<i><span class=success>Informationen uppdaterades</span></i>";
      } else {
        $output = "<i><span class=failure>Nödvändig information saknas. Informationen sparades EJ!</span></i>";
      }

    }else{
      $this->setDbInfo($id);
    }

    $html =  "<h1>Redigera medlem</h1>
              <form method=post>
                <fieldset>
                <legend>Redigera</legend>
                <p><i>Fält märkta med * måste fyllas i.</i></p>
                <p><label>*Namn:<br/><input type='text' name='name' value='{$this->name}'/></label></p>
                <p><label>*Alias:<br/><input type='text' name='acronym' value='{$this->acronym}'/></label></p>
                <p><label>E-post:<br/><input type='email' name='email' value='{$this->email}'/></label></p>
                <p><label>Webbplats:<br/><input type='url' name='website' value='{$this->website}'/></label></p>
                <p class=buttons><input type='submit' name='saveUserInformation' value='Spara'/> <input type='reset' value='Återställ'/></p>
                <output>{$output}</output>
                </fieldset>
              </form>";
    return $html;
  }

  private function setParams(){
    $this->orderby = !empty($_GET['orderby']) ? $_GET['orderby'] : 'id';
    $this->order   = !empty($_GET['order'])   ? $_GET['order']   : 'asc';
    $this->hits    = !empty($_GET['hits'])    ? $_GET['hits']    : $this->hits;
    $this->page    = !empty($_GET['page'])    ? $_GET['page']    : $this->page;
  }


  /**
   * Hits per page dropdown 
   *
   * @param  $hitsOptions -  associative array with hits-options i.e. array(4 => 'four',8 => 'eight', 16 => 'sixteen')
   * @param  $current     -  the current value if any
   * @return $html:       -  string with html dropdown for hits per page
   */
  private function getDropdown($hitsOptions = array(8 => 8, 16 => 16), $current = null){
    $html = null;


    $html = "<nav class='dropDown'>
          <form method='get'>
            <input type=hidden name='orderby' value='$this->orderby'>
            <input type=hidden name='order' value='$this->order'>
            <label for='input1'>Visa:</label>
            <select id='input1' name='hits' onchange='form.submit();'>";

    foreach($hitsOptions as $value => $name){
        if($value == $current) {
          $html .= "<option selected='selected' value='".$value."'>".$name."</option>";
        }
        else {
          $html .= "<option value='".$value."'>".$name."</option>";
        }
    }
    $html .="</select></form></nav>";
    return $html;
  }



  /**
   * DELETE ME!?
   * Gives html-code for all users with paging
   *
   * @param $edit boolean if edit options or not 
   * @return string:  $html with all users
   */ 
  private function getAllUsersAsHtml($edit = false){
    $html = null;
    $members = null;
    $res = $this->getAllUsers();
    $members = "<table id=allUsers><tr><th>Id</th><th>Namn</th></tr>";
    foreach ($res as $key => $val) {
      $members .= "<tr><td>{$val->id}</td><td>{$val->name}</td></tr>";
    }
    $members .= "</table>";

    $html .= "<h1>Alla medlemmar</h1>";
    $html .= $members;
    $html .= $this->getPageNavigation();

    return $html;

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
    $html = '<div class="PageNavigation">';
    $html .= $nav;
    $html .= '</div>';
    return $html;
  }


  /**
   * Get data from database
   *
   * @return string html for search result.
   */
  private function getAllUsers() {
    $limit = "";

    // Pagination
    if($this->hits && $this->page) {
      $limit = " LIMIT $this->hits OFFSET " . (($this->page - 1) * $this->hits);
    }

    // Create sql-query (only show movies that are published and not deleted)
    $this->query = "SELECT * FROM user";

    // Set how many rows
    $rowsQuery = $this->db->ExecuteSelectQueryAndFetchAll($this->query);
    $this->rows = $this->setSqlRows($rowsQuery);

    $this->query .= " GROUP BY $this->orderby $this->order";
    $this->query .= " $limit";

    // Get data
    return $this->db->ExecuteSelectQueryAndFetchAll($this->query);

  }

  private function setSqlRows($sql) {
    $i = 0;
    foreach ($sql as $key => $val) {
      $i+=1;
    }
    return $i;
  }





}
