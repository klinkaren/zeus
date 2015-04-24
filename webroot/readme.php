<?php
/**
 * This is a Zeus pagecontroller.
 *
 */
// Include the essential config-file which also creates the $zeus variable with its defaults.
include(__DIR__.'/config.php');



$zeus['stylesheets'][] = 'css/om.css';

// Create instance of CContent
$filter = new CTextFilter();


$file = file_get_contents('../README.md', true);

$text = $filter->doFilter($file, "markdown");

// Put everything in Zeus container.
$zeus['title'] = "Readme";

$zeus['main'] = $text;



// Finally, leave it all to the rendering phase of Zeus.
include(ZEUS_THEME_PATH);

