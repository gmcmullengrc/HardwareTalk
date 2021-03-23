<?php
/*
 * TODO: make username input sticky
 */
class  Login
{
  private $_dbh;
  private $_f3;

  function __construct($dbh, $f3)
  {
    $this->_dbh = $dbh;
    $this->_f3 = $f3;
  }

  function logInUser()
  {
    //globals
    global $validator;
    global $user;

    //initial variables
    $email = "";
    $password = "";
    
    
    //validate email and then set errors if anything is wrong
    if(empty(trim($_POST['email']))){
      $this->_f3 -> set('errors["loginEmail"]', "Email can not be empty");
    } else if(!($validator->validEmail(trim($_POST['email'])))){
      $this->_f3 -> set('errors["loginEmail"]', "Invalid Email");
    } else {
      $email = trim($_POST['email']);
    }
    
    //set error or trim $_POST and assign to variable
    if(empty(trim($_POST['password']))){
      $this->_f3 -> set('errors["loginPass"]', "Password can not be empty");
    } else {
      $password = trim($_POST['password']);
    }
    
    
    //if errors are empty
    if(empty($this->_f3->get('errors'))) {
      $sql = "SELECT user_id, username, user_ip, user_password, user_role_id  
              FROM users 
              WHERE user_email = :email;";
      
      //if prepare returns true
      if($statement = $this->_dbh->prepare($sql)){
        
        //bind params
        $statement->bindParam(':email', $email, PDO::PARAM_STR);
        
        //if the statement can execute
        if($statement->execute()){
          
          //as long as the statement contains one row only
          if($statement->rowCount() == 1){
            
            //fetch row
            $row = $statement->fetch();

            //assign row columns to variables
            $id = $row['user_id'];
            $username = $row['username'];
            $hashedPassword = $row['user_password'];
            $userRole = $row['user_role_id'];
            $userIP = $row['user_ip'];

            //verify the entered password against the retreived hashed password
            if(password_verify($password, $hashedPassword)){
              //if true then start session and assign logged in data

              $_SESSION['loggedin'] = true;
              /*
              $user = new User($id, $username, $userRole, $userIP);
              $_SESSION['user'] = $user;*/

              if($userRole == 1){
                $user = new Admin($id, $username, $userRole, $userIP);
                $_SESSION['user'] = $user;
              } else {
                $user = new User($id, $username, $userRole, $userIP);
                $_SESSION['user'] = $user;
              }


              //echo '<script>alert("Passwords Match, user logged in")</script>';

              $this->_f3->set(
                'success["loggedin"]',
                "You have been logged in. You will be redirected."
              );
            }
            //else set error of invalid password
            else{
              $this->_f3 -> set('errors["loginPass"]', "Invalid Password");
            }
          }
          //else set error of no email found
          else {
            $this->_f3->set(
              'errors["accountNotFound"]',
              "No account found with that Email Address"
            );
          }
        }
        //unset the statement variable inside the class
        unset($statement);
      }
      else
        echo "statement failed to prepare";
    }
    // echo var_dump($this->_f3->get("success['loggedin']"));
    //unset the database object inside the class
    unset($_dbh);
  }
}