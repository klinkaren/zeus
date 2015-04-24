<?php
/**
 * Config-file for Zeus. Change settings here to affect installation.
 *
 */
 
/**
 * Set the error reporting.
 *
 */
error_reporting(-1);              // Report all type of errors
ini_set('display_errors', 1);     // Display all errors 
ini_set('output_buffering', 0);   // Do not buffer outputs, write directly
 
 
/**
 * Define Zeus paths.
 *
 */
define('ZEUS_INSTALL_PATH', __DIR__ . '/..');
define('ZEUS_THEME_PATH', ZEUS_INSTALL_PATH . '/theme/render.php');
 
 
/**
 * Include bootstrapping functions.
 *
 */
include(ZEUS_INSTALL_PATH . '/src/bootstrap.php');
 
 
/**
 * Start the session.
 *
 */
session_name(preg_replace('/[^a-z\d]/i', '', __DIR__));
session_start();
 
 
/**
 * Create the Zeus variable.
 *
 */
$zeus = array();
 
 
/**
 * Site wide settings.
 *
 */
$zeus['lang']         = 'sv';
$zeus['title_append'] = ' - RM';

/*$zeus['header'] = <<<EOD
<img class='sitelogo' src='img/oophp.png' alt='Zeus Logo'/>
<span class='sitetitle'>OOPHP</span>
<span class='siteslogan'>Min Me-sida i kursen Databaser och Objektorienterad PHP-programmering</span>
EOD;*/

/**
 * Searchbox in menu
 *
 */
//$zeus['searchbox'] = 'Sökning';


$zeus['navlogo'] = 'Logo';

$zeus['footer'] = <<<EOD
<footer><span class='sitefooter'><div class="center">Zeus is php framework made by <a href="http://www.viktorkjellberg.com">Viktor Kjellberg</a> as a project at <a href="http://www.bth.se/eng">BTH</a>.<br>It build on the framework <a href="https://github.com/mosbth/Anax-oophp">Anax-oophp</a> made by <a href="https://github.com/mosbth">Mikael Roos</a></div><div class="center">View <a href="https://github.com/klinkaren/zeus">Zeus on Github</a>.</div></span></footer>
EOD;



/**
 * Define the menu as an array
 *
 */
$zeus['navbar'] = array(
  // Use for styling the menu
  'class' => 'navbar',
 
  // Here comes the menu strcture
  'items' => array(
    'readme'      => array('text'=>'Readme',           'url'=>'readme.php',       'title' => 'About the Zeus framework'),
  ),
 
  // This is the callback tracing the current selected menu item base on scriptname
  'callback' => function($url) {
    if(basename($_SERVER['SCRIPT_FILENAME']) == $url) {
      return true;
    }
  }
);

// Dropdown menu part
$zeus['navbar']['items']['user'] = array('text'=>'Dropdown', 'url'=>'#',  'title' => 'A dropdown',
        'submenu' => array(

      'items' => array(

        // This is a menu item of the submenu
        'user_page'  => array(
          'text'  => 'Test 1',   
          'url'   => '#',  
          'title' => 'Test 1.',
          'class' => 'submenu'
        ),

        // This is a menu item of the submenu
        'edit_user'  => array(
          'text'  => 'Test 2',   
          'url'   => '#',  
          'title' => 'Test 2.',
          'class' => 'submenu'
        ),

      ),
    ),);


/**
 * Database connection
 *
 */
/*
define('DB_USER', 'root'); // The database username
define('DB_PASSWORD', ''); // The database password

$zeus['database']['dsn']            = 'mysql:host=localhost;dbname=databasename;';
$zeus['database']['username']       = DB_USER;
$zeus['database']['password']       = DB_PASSWORD;
$zeus['database']['driver_options'] = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'");
*/


/**
 * Theme related settings.
 *
 */
$zeus['stylesheets'] = array('css/style.css');
$zeus['favicon']    = 'img/favicon.ico';



/**
 * Settings for JavaScript.
 *
 */
$zeus['modernizr'] = 'js/modernizr.js';
$zeus['jquery'] = '//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js';
$zeus['jquery_src'] = '//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js';
$zeus['javascript_include'] = array();
$zeus['javascript_include'][] = 'js/inputBox.js';



/**
 * Google analytics.
 *
 */
$zeus['google_analytics'] = 'UA-22093351-1'; // Set to null to disable google analytics

