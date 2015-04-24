<?php 
/**
 * This is a PHP skript to process images using PHP GD.
 *
 */



// Include the CImage class
require_once('../src/CImage/CImage.php');



// Settings for image object
$options =  array(
  'imageDir' => __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR , 
  'cacheDir' => __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR ,
  'maxWidth' => 2000,
  'maxHeight' => 2000,
  );

// Create the image object
$cimg = new CImage($options);


// Display the image
$cimg->showImage(); 
