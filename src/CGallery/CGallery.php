<?php

/**
* ===== CGallery =====
* - 
* 
* === HOW ===
*
*
* === EXAMPLE ===
*
*
*/
class CGallery {

  /**
  * Members
  */
  private $galleryPath;
  private $galleryBaseURL;
  private $path;
  private $pathToGallery;
  private $gallery;
  private $errorMessage;    // string

  private $validImages = array('png', 'jpg', 'jpeg');


  /**
   * CONSTRUCTOR
   * Creates an instans of CDatabase, that connects to a MySQL database using PHP PDO.
   *
   */
  public function __construct($options) {
    $this->galleryPath = $options['galleryPath'];
    $this->galleryBaseURL = $options['galleryBaseURL'];
    $this->getParams();
  } 


  public function getGallery(){

    // Get breadcrumbs
    $html = $this->getBreadcrumbs();

    // Read and present images in the current directory
    if(is_dir($this->pathToGallery)) {
      $html .= $this->readAllItemsInDir();
    }
    else if(is_file($this->pathToGallery)) {
      $html .= $this->readItem();
    }


    return $html;
  }


  /**
   * Create a breadcrumb of the gallery query path.
   *
   * @param string $path to the current gallery directory.
   * @return string html with ul/li to display the thumbnail.
   */
  private function getBreadcrumbs() {
    $parts = explode('/', trim(substr($this->path, strlen($this->galleryPath) + 1), '/'));
    $breadcrumb = "<ul class='breadcrumb'>\n<li><a href='?'>Hem</a> »</li>\n";
   
    if(!empty($parts[0])) {
      $combine = null;
      foreach($parts as $part) {
        $combine .= ($combine ? '/' : null) . $part;
        $breadcrumb .= "<li><a href='?path={$combine}'>$part</a> » </li>\n";
      }
    }
   
    $breadcrumb .= "</ul>\n";
    return $breadcrumb;
  }




  private function getParams(){
    // Get incoming parameters
    $this->path = isset($_GET['path']) ? $_GET['path'] : null;
    $this->pathToGallery = realpath($this->galleryPath . DIRECTORY_SEPARATOR . $this->path);
    $this->path = realpath($this->galleryPath . DIRECTORY_SEPARATOR . $this->path);

    // Validate incoming arguments
    is_dir($this->galleryPath) or $this->errorMessage('The gallery dir is not a valid directory.');
    substr_compare($this->galleryPath, $this->pathToGallery, 0, strlen($this->galleryPath)) == 0 or $this->errorMessage('Security constraint: Source gallery is not directly below the directory $this->galleryPath.');
 
  }


  /**
   * Read directory and return all items in a ul/li list.
   *
   * @param string $path to the current gallery directory.
   * @param array $this->validImages to define extensions on what are considered to be valid images.
   * @return string html with ul/li to display the gallery.
   */
  private function readAllItemsInDir() {


    $files = glob($this->path . '/*'); 
    $this->gallery = "<ul class='gallery'>\n";
    $len = strlen($this->galleryPath);
   
    foreach($files as $file) {
      $parts = pathinfo($file);
      $href  = str_replace('\\', '/', substr($file, $len + 1));
   
      // Is this an image or a directory
      if(is_file($file) && in_array($parts['extension'], $this->validImages)) {
        $item    = "<img src='img.php?src=" 
          . $this->galleryBaseURL 
          . $href 
          . "&amp;width=128&amp;height=128&amp;crop-to-fit' alt=''/>";
        $caption = basename($file); 
      }
      elseif(is_dir($file)) {
        $item    = "<img src='img/folder.png' alt=''/>";
        $caption = basename($file) . '/';
      }
      else {
        continue;
      }
   
      // Avoid to long captions breaking layout
      $fullCaption = $caption;
      if(strlen($caption) > 18) {
        $caption = substr($caption, 0, 10) . '…' . substr($caption, -5);
      }
   
      $this->gallery .= "<li><a href='?path={$href}' title='{$fullCaption}'><figure class='figure overview'>{$item}<figcaption>{$caption}</figcaption></figure></a></li>\n";
    }
    $this->gallery .= "</ul>\n";
   
    return $this->gallery;
  }

  /**
   * Read and return info on choosen item.
   *
   * @param string $path to the current gallery item.
   * @param array $this->validImages to define extensions on what are considered to be valid images.
   * @return string html to display the gallery item.
   */
  private function readItem() {
    $parts = pathinfo($this->path);
    if(!(is_file($this->path) && in_array($parts['extension'], $this->validImages))) {
      return "<p>This is not a valid image for this gallery.";
    }
   
    // Get info on image
    $imgInfo = list($width, $height, $type, $attr) = getimagesize($this->path);
    $mime = $imgInfo['mime'];
    $gmdate = gmdate("D, d M Y H:i:s", filemtime($this->path));
    $filesize = round(filesize($this->path) / 1024); 
   
    // Get constraints to display original image
    $displayWidth  = $width > 800 ? "&amp;width=800" : null;
    $displayHeight = $height > 600 ? "&amp;height=600" : null;
   
    // Display details on image
    $len = strlen($this->galleryPath);
    $href = $this->galleryBaseURL . str_replace('\\', '/', substr($this->path, $len + 1));
    $item = <<<EOD
  <p><img src='img.php?src={$href}{$displayWidth}{$displayHeight}' alt=''/></p>
  <p>Original image dimensions are {$width}x{$height} pixels. <a href='img.php?src={$href}'>View original image</a>.</p>
  <p>File size is {$filesize}KBytes.</p>
  <p>Image has mimetype: {$mime}.</p>
  <p>Image was last modified: {$gmdate} GMT.</p>
EOD;
   
    return $item;
  }






}
  