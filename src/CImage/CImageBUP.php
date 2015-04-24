<?php

/**
* ===== CImage =====
* - Modifies, caches och outputs images
* 
* === HOW ===
* Takes in array containing:
*  - imageDir (path to image directory)
*  - cacheDir (path to cache)
* ...and optionally:
*  - maxWidth and maxHeight (of images)
*
* === EXAMPLE ===
* $options =  array(
*  'imageDir' => __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR , 
*  'cacheDir' => __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR ,
*  'maxWidth' => 2000,
*  'maxHeight' => 2000,
*  );
* $exampleImage = new CImage($options);
*
*/
class CImage {

  /**
  * Members
  */

  // Options
    private $imageDir;    // string: instead of IMG_PATH
    private $cacheDir;    // string: instead of CACHE_PATH
    private $maxWidth;    // int
    private $maxHeight;   // int

  // Parameters
    private $src;         // string: path to image
    private $verbose;     // boolean
    private $saveAs;      // string: image format
    private $quality;     // int, 1-100
    private $ignoreCache; // boolean
    private $newWidth;    // int
    private $newHeight;   // int
    private $cropToFit;   // boolean
    private $sharpen;     // boolean
    private $pathToImage; // string: full path to image

    // array of supported images
    private $extensions = array('png', 'jpg', 'jpeg'); 

    // String containing image info
    private $verboseLog;  

    // Orinal image info
    private $filesize;    // Filesize
    private $height;      // Height
    private $width;       // Width

  /**
   * CONSTRUCTOR
   * Creates an instans of CDatabase, that connects to a MySQL database using PHP PDO.
   *
   */
  public function __construct($options) {
    $this->imageDir = $options['imageDir'];
    $this->cacheDir = $options['cacheDir'];
    $this->maxWidth = isset($options['maxWidth']) ? $options['maxWidth'] : 2000;
    $this->maxHeight = isset($options['maxHeight']) ? $options['maxHeight'] : 2000;

    $this->getParams();
  } 


  /**
   * Show image
   *
   */
  public function showImage() {
    // Create path to image file
    $this->pathToImage = realpath($this->imageDir . $this->src);
    !isset($this->verbose) or $this->startVerboseLog();

    // Validate incoming arguments
    $this->validate();

    // Get image info
    $this->getImageInfo();

    // Open up original image from file
    $image = $this->openOrginalImage();

    // Resize and apply filters if requested
    if (isset($this->cropToFit) || isset($this->newHeight) || isset($this->newWidth)) {
        $image = $this->resizeImage($this->width, $this->height, $image);
    }

    // Create cache filename
    $cacheFileName = $this->createCache();

    // Check if file already exists, if so output image
    $this->checkForCache($cacheFileName);

    // Save file
    $this->saveImage($cacheFileName, $image);

    // Output image
    $this->outputImage($cacheFileName);

  }


  /**
   * Get the incoming arguments
   *
   */
  private function getParams() {

    $this->src         = isset($_GET['src'])         ? $_GET['src']      : null;
    $this->verbose     = isset($_GET['verbose'])     ? true              : null;
    $this->saveAs      = isset($_GET['save-as'])     ? $_GET['save-as']  : null;
    $this->quality     = isset($_GET['quality'])     ? $_GET['quality']  : 60;
    $this->ignoreCache = isset($_GET['no-cache'])    ? true              : null;
    $this->newWidth    = isset($_GET['width'])       ? $_GET['width']    : null;
    $this->newHeight   = isset($_GET['height'])      ? $_GET['height']   : null;
    $this->cropToFit   = isset($_GET['crop-to-fit']) ? true              : null;
    $this->sharpen     = isset($_GET['sharpen'])     ? true              : null;

    $this->pathToImage = realpath($this->imageDir . $this->src);
  }


  /**
   * Sharpen image as http://php.net/manual/en/ref.image.php#56144
   * http://loriweb.pair.com/8udf-sharpen.html
   *
   * @param resource $image the image to apply this filter on.
   * @return resource $image as the processed image.
   */
  private function sharpenImage($image) {
    $matrix = array(
      array(-1,-1,-1,),
      array(-1,16,-1,),
      array(-1,-1,-1,)
    );
    $divisor = 8;
    $offset = 0;
    imageconvolution($image, $matrix, $divisor, $offset);
    return $image;
  }

  /**
   * Resize image
   *
   */
  private function resizeImage($width, $height, $image) {
  
    // Calculate new width and height for the image    
    $aspectRatio = $width / $height;

    if($this->cropToFit && $this->newWidth && $this->newHeight) {
      $targetRatio = $this->newWidth / $this->newHeight;
      $cropWidth   = $targetRatio > $aspectRatio ? $width : round($height * $targetRatio);
      $cropHeight  = $targetRatio > $aspectRatio ? round($width  / $targetRatio) : $height;
      if($this->verbose) { $this->verbose("Crop to fit into box of {$newWidth}x{$newHeight}. Cropping dimensions: {$cropWidth}x{$cropHeight}."); }
    }
    else if($this->newWidth && !$this->newHeight) {
      $this->newHeight = round($this->newWidth / $aspectRatio);
      if($this->verbose) { $this->verbose("New width is known {$newWidth}, height is calculated to {$newHeight}."); }
    }
    else if(!$this->newWidth && $this->newHeight) {
      $this->newWidth = round($this->newHeight * $aspectRatio);
      if($this->verbose) { $this->verbose("New height is known {$this->newHeight}, width is calculated to {$this->newWidth}."); }
    }
    else if($this->newWidth && $this->newHeight) {
      $ratioWidth  = $width  / $this->newWidth;
      $ratioHeight = $height / $this->newHeight;
      $ratio = ($ratioWidth > $ratioHeight) ? $ratioWidth : $ratioHeight;
      $this->newWidth  = round($width  / $ratio);
      $this->newHeight = round($height / $ratio);
      if($this->verbose) { $this->verbose("New width & height is requested, keeping aspect ratio results in {$newWidth}x{$newHeight}."); }
    }
    else {
      $this->newWidth = $width;
      $this->newHeight = $height;
      if($this->verbose) { $this->verbose("Keeping original width & heigth."); }
    }

// Resize the image if needed
    if($this->cropToFit) {
      if($this->verbose) { $this->verbose("Resizing, crop to fit."); }
      $cropX = round(($width - $cropWidth) / 2);  
      $cropY = round(($height - $cropHeight) / 2);    
      $imageResized = imagecreatetruecolor($this->newWidth, $this->newHeight);
      imagecopyresampled($imageResized, $image, 0, 0, $cropX, $cropY, $this->newWidth, $this->newHeight, $cropWidth, $cropHeight);
      $image = $imageResized;
      $width = $this->newWidth;
      $height = $this->newHeight;
    }
    else if(!($this->newWidth == $width && $this->newHeight == $height)) {
      if($this->verbose) { $this->verbose("Resizing, new height and/or width."); }
      $imageResized = imagecreatetruecolor($this->newWidth, $this->newHeight);
      imagecopyresampled($imageResized, $image, 0, 0, 0, 0, $this->newWidth, $this->newHeight, $width, $height);
      $image  = $imageResized;
      $width  = $this->newWidth;
      $height = $this->newHeight;
    }
    return $image;

  }


  /**
   * Create cache
   *
   */
  private function createCache() {
    // Creating a filename for the cache
    $parts          = pathinfo($this->pathToImage);
    $fileExtension  = $parts['extension'];
    $this->saveAs   = is_null($this->saveAs) ? $fileExtension : $this->saveAs;
    $quality_       = is_null($this->quality) ? null : "_q{$this->quality}";
    $cropToFit_     = is_null($this->cropToFit) ? null : "_cf";
    $sharpen_       = is_null($this->sharpen) ? null : "_s";
    $dirName        = preg_replace('/\//', '-', dirname($this->src));
    $cacheFileName = $this->cacheDir . "-{$dirName}-{$parts['filename']}_{$this->newWidth}_{$this->newHeight}{$quality_}{$cropToFit_}{$sharpen_}.{$this->saveAs}";
    $cacheFileName = preg_replace('/^a-zA-Z0-9\.-_/', '', $cacheFileName);

    if($this->verbose) { $this->verbose("Cache file is: {$cacheFileName}"); }
    return $cacheFileName;
  }

  /**
   * Save Image
   *
   */
  private function saveImage($cacheFileName, $image) {
    // Save the image
    switch($this->saveAs) {
      case 'jpeg':
      case 'jpg':
        if($this->verbose) { $this->verbose("Saving image as JPEG to cache using quality = {$this->quality}."); }
        imagejpeg($image, $cacheFileName, $this->quality);
        break;  

      case 'png':  
        if($this->verbose) { $this->verbose("Saving image as PNG to cache."); }
        imagepng($image, $cacheFileName);  
        break;  

      default:
        $this->errorMessage('No support to save as this file extension.');
        break;
    }

    if($this->verbose) { 
      clearstatcache();
      $cacheFilesize = filesize($cacheFileName);
      $this->verbose("File size of cached file: {$cacheFilesize} bytes."); 
      $this->verbose("Cache file has a file size of " . round($cacheFilesize/$this->filesize*100) . "% of the original size.");
    }


  }

  /**
   * Output an image together with last modified header.
   *
   * @param string $file as path to the image.
   */
  private function outputImage($file) {
    $info = getimagesize($file);
    !empty($info) or $this->errorMessage("The file doesn't seem to be an image.");
    $mime   = $info['mime'];

    $lastModified = filemtime($file);  
    $gmdate = gmdate("D, d M Y H:i:s", $lastModified);

    if($this->verbose) {
      $this->verbose("Memory peak: " . round(memory_get_peak_usage() /1024/1024) . "M");
      $this->verbose("Memory limit: " . ini_get('memory_limit'));
      $this->verbose("Time is {$gmdate} GMT.");
    }

    if(!$this->verbose) header('Last-Modified: ' . $gmdate . ' GMT');
    if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModified){
      if($this->verbose) { $this->verbose("Would send header 304 Not Modified, but its verbose mode."); echo $this->verboseLog; exit; }
      header('HTTP/1.0 304 Not Modified');
    } else {  
      if($this->verbose) { $this->verbose("Would send header to deliver image with modified time: {$gmdate} GMT, but its verbose mode."); echo $this->verboseLog; exit; }
      header('Content-type: ' . $mime);  
      readfile($file);
    }
    exit;
  }

  /**
   * Display error message.
   *
   * @param string $message the error message to display.
   */
  private function errorMessage($message) {
    header("Status: 404 Not Found");
    die('img.php says 404 - ' . htmlentities($message));
  }


  /**
   * Display log message.
   *
   * @param string $message the log message to display.
   */
  private function verbose($message) {
    $this->verboseLog .= "<p>" . htmlentities($message) . "</p>";
  }


  /**
   * Start verbose log
   *
   */
  private function startVerboseLog() {
    // Start displaying log if verbose mode & create url to current image

    if($this->verbose) {
      $query = array();
      parse_str($_SERVER['QUERY_STRING'], $query);
      unset($query['verbose']);
      $url = '?' . http_build_query($query);


    $this->verboseLog = <<<EOD
    <html lang='en'>
    <meta charset='UTF-8'/>
    <title>img.php verbose mode</title>
    <h1>Verbose mode</h1>
    <p><a href=$url><code>$url</code></a><br>
    <img src='{$url}' /></p>
EOD;
    }
  }

  /**
   * Start verbose log
   *
   */
  private function checkForCache($cacheFileName) {
    $imageModifiedTime = filemtime($this->pathToImage);
    $cacheModifiedTime = is_file($cacheFileName) ? filemtime($cacheFileName) : null;
    // If cached image is valid, output it.
    if(!$this->ignoreCache && is_file($cacheFileName) && $imageModifiedTime < $cacheModifiedTime) {
      if($this->verbose) { $this->verbose("Cache file is valid, output it."); }
        $this->outputImage($cacheFileName);
    }
    if($this->verbose) { $this->verbose("Cache is not valid, process image and create a cached version of it."); }
    }

    /**
     * Get image info
     *
     */
    private function getImageInfo() {
    $imgInfo = list($this->width, $this->height, $type, $attr) = getimagesize($this->pathToImage);
    !empty($imgInfo) or errorMessage("The file doesn't seem to be an image.");
    $mime = $imgInfo['mime'];

    if($this->verbose) {
      $this->filesize = filesize($this->pathToImage);
      $this->verbose("Image file: {$this->pathToImage}");
      $this->verbose("Image information: " . print_r($imgInfo, true));
      $this->verbose("Image width x height (type): {$this->width} x {$this->height} ({$type}).");
      $this->verbose("Image file size: {$this->filesize} bytes.");
      $this->verbose("Image mime type: {$mime}.");
    }
  }

  /**
   * Get image info
   *
   */
  private function openOrginalImage() {
    $parts          = pathinfo($this->pathToImage);
    $fileExtension  = $parts['extension'];
    switch($fileExtension) {  
      case 'jpg':
      case 'jpeg': 
        $image = imagecreatefromjpeg($this->pathToImage);
        if($this->verbose) { $this->verbose("Opened the image as a JPEG image."); }
        break;  
      case 'png':  
        $image = imagecreatefrompng($this->pathToImage); 
        if($this->verbose) { $this->verbose("Opened the image as a PNG image."); }
        break;  
      default: $this->errorMessage('No support for this file extension.');
    }
    if($this->verbose) { $this->verbose("File extension is: {$fileExtension}"); }
    return $image;
  }



  /**
   * Validate incoming arguments
   *
   */
  private function validate() {
    is_dir($this->imageDir) or $this->errorMessage('The image dir is not a valid directory.');
    is_writable($this->cacheDir) or $this->errorMessage('The cache dir is not a writable directory.');
    isset($this->src) or $this->errorMessage('Must set src-attribute.');
    preg_match('#^[a-z0-9A-Z-_\.\/]+$#', $this->src) or $this->errorMessage('Filename contains invalid characters.');
    substr_compare($this->imageDir, $this->pathToImage, 0, strlen($this->imageDir)) == 0 or $this->errorMessage('Security constraint: Source image is not directly below the directory IMG_PATH.');
    is_null($this->saveAs) or in_array($this->saveAs, array('png', 'jpg', 'jpeg')) or $this->errorMessage('Not a valid extension to save image as');
    is_null($this->quality) or (is_numeric($this->quality) and $this->quality > 0 and $this->quality <= 100) or $this->errorMessage('Quality out of range');
    is_null($this->newWidth) or (is_numeric($this->newWidth) and $this->newWidth > 0 and $this->newWidth <= $this->maxWidth) or $this->errorMessage('Width out of range');
    is_null($this->newHeight) or (is_numeric($this->newHeight) and $this->newHeight > 0 and $this->newHeight <= $this->maxHeight) or $this->errorMessage('Height out of range');
    is_null($this->cropToFit) or ($this->cropToFit and $this->newWidth and $this->newHeight) or $this->errorMessage('Crop to fit needs both width and height to work');
  }
}
  