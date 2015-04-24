<?php 
/**
 * This is a Zeus pagecontroller.
 *
 */
// Include the essential config-file which also creates the $zeus variable with its defaults.
include(__DIR__.'/config.php'); 



// Add stylesheets
$zeus['stylesheets'][] = 'css/filmer.css';
$zeus['stylesheets'][] = 'css/hem.css';


// Send all to Zeus to render
$zeus['title'] = 'First page';
$zeus['main'] = '<h1>Zeus</h1><p>A php framework. See readme for more information.</p>';

	
// Finally, leave it all to the rendering phase of Zeus.
include(ZEUS_THEME_PATH);