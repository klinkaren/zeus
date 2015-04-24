<?php
class CPageNavigation extends CDatabase{

  /**
  * Members
  */



  /**
   * CONSTRUCTOR
   *
   */
  public function __construct($options) {
    parent::__construct($options);
  }

  public function getPageNavigation($rows, $page, $hits, $side = null) {
    
    // Variables
    $min = 1;
    $max = ceil($rows/$hits);


    $nav  = "<a href='" . $this->getQueryString(array('page' => $min)) . "'>&lt;&lt;</a> ";
    $nav .= "<a href='" . $this->getQueryString(array('page' => ($page > $min ? $page - 1 : $min) )) . "'>&lt;</a> ";

    for($i=$min; $i<=$max; $i++) {
      $nav .= "<a href='" . $this->getQueryString(array('page' => $i)) . "'>$i</a> ";
    }

    $nav .= "<a href='" . $this->getQueryString(array('page' => ($page < $max ? $page + 1 : $max) )) . "'>&gt;</a> ";
    $nav .= "<a href='" . $this->getQueryString(array('page' => $max)) . "'>&gt;&gt;</a> ";

    //
    $side = $side ? $side." hasSide" : null;

    // Create div
    $html = '<div class="PageNavigation '.$side.'">';
    $html .= $nav;
    $html .= "<div class='totalResults smallText'> Totalt antal träffar: ".$rows."</div>";
    $html .= '</div>';

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
}