<?php
/**
* Database wrapper, provides a database API for the framework but hides details of implementation.
*
*/
class CHTMLTable extends CMovieSearch{

  /**
  * Members
  */
  private $htmlTable = null; 			// The HTML table
  private $res; 						// The returning data from a sql-query that should be turned into an html table
  private $paging;						// ?? array containing paging options ??
  private $sorting = array();						// which column and ASC or DESC 





  /**
   * CONSTRUCTOR
   *
   */
  public function __construct() {

  } 



  protected function getTable($sql) {

    // Put results into a HTML-table
    $tr = "<table><tr><th>Rad</th><th>Id</th><th>Bild</th><th>Titel</th><th>År</th><th>Kategorier</th></tr>";
    foreach($sql AS $key => $val) {
      $tr .= "<tr><td>{$key}</td><td>{$val->id}</td><td><img width='80' height='40' src='{$val->image}' alt='{$val->title}' /></td><td>{$val->title}</td><td>{$val->YEAR}</td><td>{$val->genre}</td></tr>";
    }
    $tr .= "</table>";

    return $tr;

  }

  protected function getEditTable($sql) {

    // Put results into a HTML-table
    $tr = "<table class=sqlTable><tr>
    <th>ID<a href=?orderby=id&order=asc>&darr;</a><a href=?orderby=id&order=desc>&uarr;</a></th>
    <th>Titel<a href=?orderby=title&order=asc>&darr;</a><a href=?orderby=title&order=desc>&uarr;</a></th>
    <th>År<a href=?orderby=YEAR&order=asc>&darr;</a><a href=?orderby=YEAR&order=desc>&uarr;</a></th>
    <th>Kategorier</th>
    <th>Publisering<a href=?orderby=published&order=asc>&darr;</a><a href=?orderby=published&order=desc>&uarr;</a></th>
    <th></th><th></th></tr>";
    foreach($sql AS $key => $val) {
    // set values for delete/undelete
    if(isset($val->deleted)){
      $un = "un";
      $do = "Återskapa";
      $class = " class=deleted";
    }else{
      $un = "";
      $do = "Ta bort";
      $class = "";
    }
      $tr .= "<tr{$class}><td>{$val->id}</td><td>{$val->title}</td><td>{$val->YEAR}</td><td>{$val->genre}</td><td>{$val->published}</td><td><a href=?editMovie={$val->id}><img title='Redigera {$val->title}' src=img.php?src=edit.png&width=20&height=20&crop-to-fit alt='Redigera'/><a/></td>";
      $tr .= "<td><a href=?{$un}deleteMovie={$val->id}><img title='{$do} {$val->title}' src=img.php?src={$un}delete.png&width=20&height=20&crop-to-fit alt='{$do}'/><a/></td>";   
      $tr .= "</tr>";
    }
    $tr .= "</table>";

    return $tr;

  }

  private function getNav() {

    $html = <<<EOD
            <nav class="galleryDropDown">
              <form method="get">
                <label for="input1">Visa:</label>
                <select id='input1' name='view' onchange='form.submit();'>
EOD;
    foreach($this->viewOptions as $value=>$name){
        if($value == $this->view) {
          $html .= "<option selected='selected' value='".$value."'>".$name."</option>";
        }
        else {
          $html .= "<option value='".$value."'>".$name."</option>";
        }
    }
    $html .="</select></form></nav>";
    return $html;
  }

  protected function overview($sql, $moviesPerRow = 4) {

    $html = "<div id='movieOverview'>";
    foreach($sql AS $key => $val) {

      // Add a movie
      $html .= '<div class="movie"><a href="film.php?id='.$val->id.'"><img class="shadow" title="'.$val->plot.'" src=img.php?src='.$val->image.'&width=200&height=300&crop-to-fit alt='.$val->title.'/></a><div class="aboutMovie"><span class="title">'.$val->title.'</span><span class="director" title="'.$val->director.'">'.$val->director.'</span><span class="year">'.$val->YEAR.'</span></div></div>';

    }
    $html .= "</div>";

    //$tabortmig .= "<tr><td>{$val->id}</td><td><img width='80' height='40' src='{$val->image}' alt='{$val->title}' /></td><td>{$val->title}</td><td>{$val->YEAR}</td><td>{$val->genre}</td></tr>";
    return $html;

  }





  /**
   * Creates an html table from db-query result.
   *
   * @param $sql object created from sql-query asked through CDatabase::ExecuteSelectQueryAndFetchAll()
   * @param $values associative array with sql column name and the name to show as header
   * @param $edit boolean: if edit options should be shown
   * @return string with html for table based on given values.
   */
  public function getTableAsHtml($sql, $values = array('id' =>'Id'), $edit = false) {

    $table = '<table class="sqlTable">';

    // Get heading
    $table .= "<tr>";
    foreach ($values as $key => $val) {
      $table .= '<th>'.$val.'<a href='.$this->getQueryString(array('orderby' => $key, 'order' => 'asc')).'>&darr;</a><a href='.$this->getQueryString(array('orderby' => $key, 'order' => 'desc')).'>&uarr;</a></th>';
    }
    if($edit){
      $table .= '<th></th><th></th>';
    }
    $table .= "</tr>";

    // Get rows
    foreach ($sql as $sqlVal) {
      $deleted = !empty($sqlVal->deleted) ? " class=deleted" : "";
      $table .= '<tr'.$deleted.'>';

      // Set values for delete/undelete
      if(isset($sqlVal->deleted)){
        $un = "un";
        $do = "Återskapa";
        $class = " class=deleted";
      }else{
        $un = "";
        $do = "Ta bort";
        $class = "";
      }

      // Get data for row
      foreach ($values as $key => $val) {
        $table .= '<td>'.$sqlVal->$key.'</td>';
      }
      if($edit){
        // Get edit links
        $table .= "<td><a href=?edit={$sqlVal->id}><img title='Redigera' src=img.php?src=edit.png&width=20&height=20&crop-to-fit alt='Redigera'/><a/></td>";
        $table .= "<td><a href=?{$un}delete={$sqlVal->id}><img title='{$do}' src=img.php?src={$un}delete.png&width=20&height=20&crop-to-fit alt='{$do}'/><a/></td>";
      }

      $table .= '</tr>';
    }

    $table .= "</table>";


    return $table;

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

}