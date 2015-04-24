<?php
/**
 * Database wrapper, provides a database API for the framework but hides details of implementation.
 *
 */
class CUser {
 
  /**
   * Members
   */
  private $db   = null;                 // The PDO object
  private $acronym = null;              // User acronym
  private $name = null;                 // User name
  private $type = null;
  private $id = null;
  private $save;
  private $email = null;
  private $website;
  private $created;



  /**
   * Constructor creating a user object.
   *
   * @param array $options containing details for connecting to the database.
   *
   */
  public function __construct($db) {
    $this->db = $db;
  }









  private function saveUserToDb($password){
    // Prepare salt and password
    $salt = md5($password);
    $password = md5($password.$salt);
    $sql = "INSERT INTO user(acronym, name, password, salt, type, email, website, created) VALUES(?, ?, ?, ?, ?, ?, ?, NOW())";
    $params = array($this->acronym, $this->name, $password, $salt, "user", $this->email, $this->website);
    $res = $this->db->ExecuteQuery($sql, $params);
    if($this->db->RowCount() == 1){
      return true;
    }else{
      return false;
    }
  }


  private function createPassword(){
    $html = null;
    $output = null;
    $saved = false;

    if(isset($_POST['savePassword'])){
      // If form was submittet

      if( !empty($_POST['newPassword']) && !empty($_POST['newPasswordAgain']) ){
        // If all passwords are set

        if($_POST['newPassword'] == $_POST['newPasswordAgain']){
          // If new password has been entered the same way twice.

          // Save user
          ### fortsätt här
          $this->name = $_POST['name'];
          $this->acronym = $_POST['acronym'];
          $this->email = !empty($_POST['email']) ? $_POST['email'] : null;
          $this->website = !empty($_POST['website']) ? $_POST['website'] : null;
          if($this->saveUserToDb($_POST['newPassword'])){
            $saved = true;
          }


        } else {
          $output = "<span class=failure>Det nya lösenordet har inte fyllts i likadant i båda fälten. Vänligen fyll i ditt nya lösenord likadant.</span>";
        }

      } else {
        $output = "<span class=failure>Vänligen fyll i all nödvändig information.</span>";
      }
    }

    if($saved){
      $html  = "Medlemmen skapades";
      $html .= $this->authenticatedAsAdmin() ? "<p><a href=new_user.php>Skapa en till medlem</a></p>" : "<p><a href=loginout.php>Logga in</a></p>";
      $html .= $this->authenticatedAsAdmin() ? "<p><a href=admin_users.php>Administrera medlemmar</a></p>" : "";
    }else{
      // Show form
      $html =  "<h1>Byt lösenord</h1>
                <form method=post>
                  <fieldset>
                    <legend>Lösenordsinformation</legend>
                    <p><i>Fält märkta med * måste fyllas i.</i></p>
                    <p><label>*Nytt lösenord:<br/><input type='password' name='newPassword' value=''/></label></p>
                    <p><label>*Nytt lösenord igen:<br/><input type='password' name='newPasswordAgain' value=''/></label></p>
                    <input type='hidden' name='name' value='$this->name'/>
                    <input type='hidden' name='acronym' value='$this->acronym'/>
                    <input type='hidden' name='email' value='$this->email'/>
                    <input type='hidden' name='website' value='$this->website'/>

                    <p class=buttons><input type='submit' name='savePassword' value='Spara'/> <input type='reset' value='Återställ'/></p>
                    <output>{$output}</output>
                  </fieldset>
                </form>";
      }
    return $html;
  }

  public function signUp(){
    $output = null;
    $html = null;

    // Log out
    if(isset($_POST['logout'])) {
      $this->logout();
      header("Location:new_user.php");

    }elseif($this->authenticated() && !$this->authenticatedAsAdmin()){
      $html .= "<p>You are already logged in. You need to log out to be able to create a new user.</p>";
      $html .= $this->getLogoutForm();

    }elseif(isset($_POST['createMember'])){

      // Check that all required fields are filled.
      if( !empty($_POST['name']) && !empty($_POST['acronym'])  ){

          // Update variabels of module
          $this->name = $_POST['name'];
          $this->acronym = $_POST['acronym'];
          $this->email = !empty($_POST['email']) ? $_POST['email'] : null;
          $this->website = !empty($_POST['website']) ? $_POST['website'] : null;

        if($this->acronymExists($_POST['acronym']) ){
          $output .= "<span class=failure>Aliaset '<i>".$_POST['acronym']."</i>' används redan. Vänligen välj ett annat alias.</span>";
          $html .= $this->createUserForm($output);
        }else{

          // Go to password creation
          $html .= $this->createPassword();
        }
      }else{
        $output .= "<span class=failure>Nödvändig information saknas. Vänligen fyll i alla fält!<span>";  
      }
    }elseif(isset($_POST['savePassword'])){
      $html .= $this->createPassword();
    }else{
      $html .= $this->authenticatedAsAdmin() ? "" : '<p>Redan medlem? <a href="loginout.php">Logga in</a></p>';
      $html .= $this->createUserForm($output);
    }
    return $html;
  }

  ### Needs work
  private function createUserForm($output){

    $html =  "<p><i>Fält märkta med * måste fyllas i.</i></p>
              <form method=post>
                <fieldset>
                  <legend>Skapa medlem</legend>
                  <p><label>*Namn:<br/><input type='text' name='name' value='{$this->name}'required/></label></p>
                  <p><label>*Alias:<br/><input type='text' name='acronym' value='{$this->acronym}' required/></label></p>
                  <p><label>E-post:<br/><input type='text' name='email' value='{$this->email}'/></label></p>
                  <p><label>Webb:<br/><input type='text' name='website' value='{$this->website}'/></label></p>

                  <p class=buttons><input type='submit' name='createMember' value='Skapa medlem'/> <input type='reset' value='Återställ'/></p>
                  <output>{$output}</output>
                </fieldset>
              </form>";

    return $html;
  }


  ### FORTSÄTT HÄR. Checka om acronymen finns eller ej.
  private function acronymExists($acronym){
    $sql = "SELECT count(id) as count FROM user where acronym = ? ";
    $params = array($acronym);
    $res = $this->db->ExecuteSelectQueryAndFetchAll($sql, $params);
    if($res[0]->count == 1){
      return true;
    }else{
      return false;
    }

  }





















  private function setParameters(){
    $this->acronym = isset($_SESSION['user']->acronym)  ? $_SESSION['user']->acronym : null;
    $this->id      = isset($_SESSION['user']->id)       ? $_SESSION['user']->id      : null;
    $this->name    = isset($_SESSION['user']->name)     ? $_SESSION['user']->name    : null;
    $this->email   = isset($_SESSION['user']->email)    ? $_SESSION['user']->email   : null;
    $this->website = isset($_SESSION['user']->website)  ? $_SESSION['user']->website : null;
    $this->created = isset($_SESSION['user']->created)  ? $_SESSION['user']->created : null;
    $this->type    = isset($_SESSION['user']->type)     ? $_SESSION['user']->type    : null;
    $this->save    = isset($_POST['save'])              ? true                       : false;

    $this->removeEmptyValues();
  }


  private function removeEmptyValues(){
    // Remove empty values
    $this->acronym = empty($this->acronym) ? null : $this->acronym;
    $this->name    = empty($this->name)    ? null : $this->name;
    $this->email   = empty($this->email)   ? null : $this->email;
    $this->website = empty($this->website) ? null : $this->website;
  }




  /**
   * Takes in user id and returns array of values for specified user
   *
   * @param int $id representing user.
   * @return array $user with user values.
   */
  private function getUser($acronym){
    $user = array();
    $sql = "SELECT acronym, name, type, created, email, website FROM user WHERE deleted IS NULL AND acronym = ?";
    $params = array($acronym);
    $res = $this->db->ExecuteSelectQueryAndFetchAll($sql, $params);
    if(isset($res[0])) {
      $user = $res[0];
    }
    else {
      die('Misslyckades: det finns inget innehåll med sådant id.');
    }

    // Sanitize content before using it.
    # borde inte det här vara $this->title etc?
    $user->acronym = htmlentities($user->acronym, null, 'UTF-8');
    $user->name    = htmlentities($user->name, null, 'UTF-8');
    $user->type    = htmlentities($user->type, null, 'UTF-8');
    $user->created = htmlentities($user->created, null, 'UTF-8');
    $user->email   = htmlentities($user->email, null, 'UTF-8');
    $user->website = htmlentities($user->website, null, 'UTF-8');

    return $user;
  }




  public function newUser(){
    $html = null;
    $html .= "create new user";
    !$this->authenticated() or die('Check: You must logout to create a new user.');
    return $html;
  }



  /**
   * 
   *
   * @return string: HTML-code with userform
   */
  public function getUserAsHtml() {


    // Set parameters from $_POST
    $this->setParameters();

    if(isset($_GET['editUser'])){
      // Check if user is logged in
      $this->authenticated() or die('Check: You must login to edit your profile.');

      $html = $this->editUser();

    }elseif(isset($_GET['editPassword'])){

      // Check if user is logged in
      $this->authenticated() or die('Check: You must login to edit your password.');

      $html = $this->editPassword();
    
    }else { 

      if(isset($_GET['acronym'])){

        // Get user details
        $user = $this->getUser($_GET['acronym']);

        // Convert $user->created to correct format
        $created = new DateTime(htmlentities($user->created, null, 'UTF-8'));
        $created = $created->format('Y-m-d');

        // Return member page
        $html = "<h1>{$user->name}</h1>
                 <p>Alias: {$user->acronym}</p>
                 <p>Medlem sedan: {$created}</p>
                 <p>E-post: {$user->email}</p>
                 <p>Webbsida: <a href={$user->website} target=_bland>{$user->website}</a></p>
                 <p>Medlemstyp: {$user->type}</p>
                ";

      } else {
        $this->authenticated() or die('Check: You must login to edit your profile.');
        $created = new DateTime(htmlentities($this->created, null, 'UTF-8'));
        $created = $created->format('Y-m-d');
        $html = "<h1>{$this->name}</h1>
                 <p>Alias: {$this->acronym}</p>
                 <p>Medlem sedan: {$created}</p>
                 <p>E-post: {$this->email}</p>
                 <p>Webbsida: {$this->website}</p>
                 <p>Medlemstyp: {$this->type}</p>
                 <br/>
                 <p><a href=?editUser>Redigera informationen</a></p>
                 <p><a href=?editPassword>Byt lösenord</a></p>
                 <p><a href=?acronym={$this->acronym}>Min sida</a></p>
                ";
      }
    }

    return $html;
  }

  private function editPassword(){
    $html = null;
    $output = null;

    if(isset($_POST['savePassword'])){
      // If form was submittet

      if( !empty($_POST['newPassword']) && !empty($_POST['newPasswordAgain']) && !empty($_POST['oldPassword'])){
        // If all passwords are set

        if($_POST['newPassword'] == $_POST['newPasswordAgain']){
          // If new password has been entered the same way twice.

          // Save the new password (if oldPassword is correct)
          $output = $this->verifyAndSaveNewPassword($this->id, $_POST['oldPassword'], $_POST['newPassword']);

        } else {
          $output = "<span class=failure>Det nya lösenordet har inte fyllts i likadant i båda fälten. Vänligen fyll i ditt nya lösenord likadant.</span>";
        }

      } else {
        $output = "<span class=failure>Vänligen fyll i all nödvändig information.</span>";
      }
    }

    // Show form
    $html =  "<h1>Byt lösenord</h1>
              <form method=post>
                <fieldset>
                  <legend>Lösenordsinformation</legend>
                  <p><i>Fält märkta med * måste fyllas i.</i></p>
                  <p><label>*Nuvarande lösenord:<br/><input type='password' name='oldPassword' value=''/></label></p>
                  <p><label>*Nytt lösenord:<br/><input type='password' name='newPassword' value=''/></label></p>
                  <p><label>*Nytt lösenord igen:<br/><input type='password' name='newPasswordAgain' value=''/></label></p>

                  <p class=buttons><input type='submit' name='savePassword' value='Spara'/> <input type='reset' value='Återställ'/></p>
                  <output>{$output}</output>
                </fieldset>
              </form>";
    return $html;
  }

  private function verifyAndSaveNewPassword($id, $oldPassword, $newPassword){

    $sql = "SELECT id, acronym, name, type, created, website, email FROM user WHERE id = ? AND password = md5(concat(?, salt))";
    $params = array($id, $oldPassword);
    $res = $this->db->ExecuteSelectQueryAndFetchAll($sql,$params);
    
    // Save new password if user found
    if (isset($res[0])){

      // Prepare salt and password
      $salt = md5($newPassword);
      $password = md5($newPassword.$salt);

      // Update password
      $sql = "UPDATE user SET password = ?, salt = ? WHERE id = $id";
      $params = array($password, $salt);
      $res = $this->db->ExecuteQuery($sql,$params);
      $output = "<span class=success>Lösenordet uppdaterades<span>";

    }else{
      $output ="<span class=failure>Angivet lösenord stämmer ej. Vänligen försök igen.</span>";
    }
    //$this->setSessionParams($res);
    return $output;

  }

  private function setPostInfo(){
    $this->name = isset($_POST['name']) ? $_POST['name'] : null;
    $this->acronym = isset($_POST['acronym']) ? $_POST['acronym'] : null;
    $this->email = isset($_POST['email']) ? $_POST['email'] : null;
    $this->website = isset($_POST['website']) ? $_POST['website'] : null;
  }

  private function editUser(){
    $html = null;
    $output = null;

    if(isset($_POST['saveUserInformation'])){

      // Get info from $_POST
      $this->setPostInfo();

      // Change empty values to null 
      $this->removeEmptyValues();

      // Save info to db if name and acronym are set.
      if(isset($this->name) && isset($this->acronym)){

        // Save to db
        $sql = "UPDATE user SET name=?, acronym = ?, website = ?, email = ?, updated = NOW() WHERE id = ?;";
        $params = array($this->name, $this->acronym, $this->website, $this->email, $this->id);
        $res = $this->db->ExecuteQuery($sql,$params);

        // Update session data
        $_SESSION['user']->name = $this->name;
        $_SESSION['user']->acronym = $this->acronym;
        $_SESSION['user']->website = $this->website;
        $_SESSION['user']->email = $this->email;


        header("Location:user.php");
      } else {
        $output = "<i>Nödvändig information saknas. Informationen sparades EJ!</i>";
      }

    }

    $html =  "<h1>Redigera informationen</h1>
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






  /**
   *  Checks if logged in.
   *
   *  @return true or false
   *
   */
  public static function authenticated(){
    return isset($_SESSION['user']) ? true : false;
  }


  /**
   *  Checks if user is logged in and of type admin.
   *
   *  @return true or false
   *
   */
  public static function authenticatedAsAdmin(){

    return isset($_SESSION['user']) ? ($_SESSION['user']->type == "admin" ? true : false) : false;
  }


  /**
   * If logged in gives message saying log out and vice versa.
   *
   * @return string saying Login or Logout
   *
   */
  public static function logOption(){
        if(self::authenticated()){
            $msg = "Logout";
        } else { 
            $msg = "Login";
        }
        return $msg;
  }


  /**
   *  Login user if user exist and password is correct.
   * 
   * @param string $user 
   * @param string $password
   */
  public function Login($user, $password){
    if (!self::authenticated()){
        $debug = false;
        $sql = "SELECT id, acronym, name, type, created, website, email FROM user WHERE acronym = ? AND password = md5(concat(?, salt)) AND deleted IS NULL";
        $params = array($user, $password);
        $res = $this->db->ExecuteSelectQueryAndFetchAll($sql,$params,$debug);
        $this->setSessionParams($res);

        // Go to user page if the user was logged in. 
        $this->authenticated() ? header("Location: user.php?acronym={$user}") : null ;
    }
  }

  private function setSessionParams($res){
            if(isset($res[0])) {
            $_SESSION['user'] = $res[0];
            $this->id      = isset($_SESSION['user']->id)       ? htmlentities($_SESSION['user']->id)       : $this->id;
            $this->acronym = isset($_SESSION['user']->acronym)  ? htmlentities($_SESSION['user']->acronym)  : $this->acronym;
            $this->name    = isset($_SESSION['user']->name)     ? htmlentities($_SESSION['user']->name)     : $this->name;
            $this->type    = isset($_SESSION['user']->type)     ? htmlentities($_SESSION['user']->type)     : $this->type;
            $this->created = htmlentities($_SESSION['user']->created);
            $this->website = htmlentities($_SESSION['user']->website);
            $this->email   = htmlentities($_SESSION['user']->email);
        }

  }

  /**
   *  Logout user
   * 
   * @param string $user 
   */
  public function Logout(){
    unset($_SESSION['user']);
    $this->acronym = null;
    $this->name = null;
    $this->type = null;

    // Unset game (from tavling.php)
    unset($_SESSION['game']);

    // Go to main page 
    header("Location: index.php");
  }



   /**
   * Gives info about status 
   * 
   * @return details about status
   */
  public static function getStatusText(){
        if (self::authenticated()) {
            $res = "Du är inloggad som: {$_SESSION['user']->acronym} ({$_SESSION['user']->name})";
        }
        else {
              $res = "Du är INTE inloggad.";
        }
        return $res;
  } 



  /**
   *  Hämta rätt formulär
   * 
   */
  public function getForm(){
    //Bygg logout-formulär
    if (self::authenticated()) {
      $res = $this->getLogoutForm();
    } else {
      //Bygg login-formulär
      $res = $this->getLoginForm();
    }
    return $res;
  } 




  /**
   * Gives the login form
   * 
   * @return a form for the user to log in with
   */
  private function getLoginForm(){
      $statusText=self::getStatusText();
        $loginform = <<<EOD
        <form method='post'><fieldset><legend>Login</legend>
        <p><strong>{$statusText}</strong></p>
        <p><label>Användare :</label><br>
        <input type='text' name='acronym' value=''></p>
        <p><label>Lösenord:</label><br>
            <input type='password' name='password' value=''></p>
        <p><button type='submit' name='login'>Logga in</button></p>
        </fieldset></form>
EOD;
    return $loginform;
  } 



   /**
   * Gives the logout form
   * 
   * @return a form for the user to log out with
   */
  private function getLogoutForm(){
          $statusText=self::getStatusText();
        $logoutform = <<<EOD
        <form method='post'><fieldset><legend>Logout</legend>
        <p><strong>{$statusText}</strong></p>
        <p><button type='submit' name='logout'>Logga ut</button></p>
        </fieldset></form>
EOD;
    return $logoutform;
  } 

}