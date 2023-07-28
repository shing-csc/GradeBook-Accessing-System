<?php 
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    session_start();    
    start();
    //Managing the token 
    
    
   


    //The reason that these code are written outside is beacause i think these funtions need to wait to run
    //Instead of being called directly, but am I correct

    list($check_bool, $check_uid) = check_token();
    if ($check_bool){

            $_SESSION['login'] = true;
            $_SESSION['time'] = time();
            $_SESSION['uid'] = $check_uid;

            //echo$check_uid."<br>";
            //IMPORTANT NOTE: WE CANT USE ECHO/VAR_DUMP BEFORE THE HEADER() CUZ IT WILL SEND THINGS
            //TO THE BROWSER BEFORE THE CHANGE IN PAGE AND RESULTS IN PROBLEM

            // Redirecting the user to index.php
            header('location: courseinfo/index.php');
            
        } 
    
    /**The program will directly run into the if statement if i didnt write the isset(...)
       as the program will aassume it is NULL, while NULL is being evaluated to be false
       So we its important to have the isset($_SESSION['login']) there
    */
    if (($_SESSION['login'] == false) and (isset($_SESSION['login']))){
            display_wrong_content(5);
            session_destroy();
    }
    
    if (($_SESSION['login'] == true) and (isset($_SESSION['login']))){
        header('location: courseinfo/index.php');
    }


    //The main function of the login.php
    function start(){
        if (isset($_POST['login'])){
            if (authenticate()){
                
                display_correct_content();
                send_authentication_email();
                
                //The problem right now is the check_token function runs before the user click the link
                //So even the link is clicked, the check_token() will not be called
                
            }
            else{
                //If the inputted email is not in the database 
                display_wrong_content(1);
                
            }
        }
        
        //Do we need the case of logging out because of timeout?
        
        
    }

    function authenticate(){
        
        if (isset($_SESSION['email'])){
            return true;
        }
        if (isset($_POST['email'])){
            $email = $_POST['email'];

            //var_dump($email);
            $db_conn = mysqli_connect("mydb", "dummy", "c3322b", "db3322") 
            or die("Connection Error! ".mysqli_connect_error());

            $query = "SELECT * FROM user WHERE email = '$email'";

            $users = mysqli_query($db_conn, $query) 
            or die("Query Error! ".mysqli_error($db_conn));
            //var_dump($users);

            $row = mysqli_fetch_array($users);
            //echo "uid".$row['uid']."<br>";

            if (mysqli_num_rows($users) > 0){
                
                mysqli_free_result($users);
                mysqli_close($db_conn);
                return true;
            }else{

                mysqli_free_result($users);
                mysqli_close($db_conn);
                return false;
            }

        }
       
        

    }
    
    function display_correct_content(){
        //Html and css content shown when the email exist in the database
        ?> 
        <!-- HTML code -->
        <div class="correct"> <div class = "text">Please check your email for the authentication URL. </div> </div>

        <?php
    }

    function display_wrong_content($type){
        //Html and css content shown when the email not exist in the database
        if ($type == 1){
        ?>
            <div class="wrong"> <div class = "text">Unknown user - we don't have the records for  <?php echo $_POST['email']; ?> in the system</div> </div>

        <?php }
        else if ($type == 2){
        ?>
            <div class="wrong"> <div class = "text">Fail to authenticate - OTP expired!</div> </div>

        <?php }
        else if ($type == 3){
        ?>
            <div class="wrong"> <div class = "text">Fail to authenticate - incorrect secret!</div> </div>
        <?php }
        else if ($type == 4){
        ?>
            <div class="wrong"> <div class = "text">Unknown user - cannot identify the student.</div> </div>
        <?php }

        else if ($type == 5){
        ?>
            <div class="wrong"> <div class = "text">Session expired. Please login again. </div> </div>
        <?php }
    }

    function send_authentication_email(){
        require 'PHPMailer/src/Exception.php';
        require 'PHPMailer/src/PHPMailer.php';
        require 'PHPMailer/src/SMTP.php';
        
        //Create an instance; passing `true` enables exceptions
        $mail = new PHPMailer(true);
        
        if (isset($_POST['email'])) {
        
          try {
              //Server settings
              //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
              $mail->isSMTP();                                            //Send using SMTP
              $mail->Host       = 'testmail.cs.hku.hk';                     //Set the SMTP server to send through
              $mail->SMTPAuth   = false;                                   //Enable SMTP authentication
          
              $mail->Port       = 25;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
          
              //Sender
              $mail->setFrom('c3322@cs.hku.hk', 'COMP3322');
              //******** Add a recipient to receive your email *************
              $mail->addAddress($_POST['email']);     
          
              //Content
              $mail->isHTML(true);                                  //Set email format to HTML
              $mail->Subject = 'Send by PHPMailer';

            //BELOW ARE THE CODE I WROTE
            $one_time_secret = bin2hex(random_bytes(8));

                $email = $_POST['email'];

                //Code from auth function
                $db_conn = mysqli_connect("mydb", "dummy", "c3322b", "db3322") 
                or die("Connection Error! ".mysqli_connect_error());

                // 1. First query: selecting the row 
                $select_query = "SELECT * FROM user WHERE email = '$email'";
                
                $users = mysqli_query($db_conn, $select_query) 
                or die("Query Error! ".mysqli_error($db_conn));
                
                $uid = mysqli_fetch_array($users)['uid']; // Main purpose is to get the uid from the database
                //var_dump($uid);

                //2. Second query: inserting the data
                $expiry_time = time() + 60;
                $update_query = "UPDATE user SET secret='$one_time_secret',timestamp='$expiry_time' WHERE uid='$uid'";
                
                mysqli_query($db_conn, $update_query);
                /** 
                 * if (mysqli_query($db_conn, $update_query)) {
                 *  echo "Record updated successfully".$uid;
                 *  } else {
                 *   echo "Error updating record: " . mysqli_error($db_conn);
                 *  }
                 */

                mysqli_free_result($users);
                mysqli_close($db_conn);
               
                //End of code from the auth function

            
            $associative_array = array("uid" => $uid, "secret" => $one_time_secret);
            $token = bin2hex(json_encode($associative_array));
                

            $url = "http://localhost:9080/login.php?token=".$token;
            //$mail->Body    = 'Dear Student, <br>You can log on to the system via the following link: <br> <a href="https://moodle.hku.hk/">https://moodle.hku.hk/</a>';
            $mail->Body    = "Dear Student, <br>You can log on to the system via the following link: <br> <a href='".$url."'>".$url."</a>";
              
              
            //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
          
            $mail->send();
            //echo 'Message has been sent';
            //echo $uid;
            //return $uid;

          } catch (Exception $e) {
              echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
          }
          
        } else {
          echo "Please specify the recipent's email and name.";
          //return 0;
        }


    }
    function check_token(){
        
        if (isset($_GET['token'])){

            //Something have problem
            $clicked_token = $_GET['token'];
            //echo "1 <br>";
            //var_dump($clicked_token)."<br>";
            
            $clicked_array = json_decode(hex2bin($clicked_token));
            //echo "2 <br>";
            //var_dump($clicked_array)."<br>";
            
            $clicked_secret = ($clicked_array->secret);
            $clicked_uid = $clicked_array->uid;

            //echo var_dump($clicked_secret);
            //echo $clicked_uid;
            //echo $clicked_array."<br>";

            

            //Connect the database
            $db_conn = mysqli_connect("mydb", "dummy", "c3322b", "db3322") 
            or die("Connection Error! ".mysqli_connect_error());

            //Connect the data/row
            

            $current_time = time();
            //echo $current_time."<br>";

            $clicked_query = "SELECT * FROM user WHERE uid =".$clicked_uid;
            
            $compare_data = mysqli_query($db_conn, $clicked_query)
            or die("Query Error! ".mysqli_error($db_conn));

            //echo mysqli_fetch_array($compare_data)['timestamp'];

            if (mysqli_num_rows($compare_data) > 0){

                $row = mysqli_fetch_array($compare_data); 
                // We cannot call the mysqli_fetch_array() for more than once, the best option is to assign the value to a $row. then we access the row

                if (($row['secret'] == $clicked_secret) ){
                    if ($row['timestamp'] >= $current_time){
                        
                        mysqli_free_result($compare_data);
                        

                        $update_query = "UPDATE user SET secret = NULL, timestamp = NULL WHERE uid=".$clicked_uid;
                        mysqli_query($db_conn, $update_query);


                        mysqli_close($db_conn);
                        return array(true, $clicked_uid);

                    }else{
                        $db_conn = mysqli_connect("mydb", "dummy", "c3322b", "db3322") 
                        or die("Connection Error! ".mysqli_connect_error());

                        $update_query = "UPDATE user SET secret = NULL, timestamp = NULL WHERE uid=".$clicked_uid;
                        mysqli_query($db_conn, $update_query);


                        mysqli_close($db_conn);
                        display_wrong_content(2); //OTP expired
                        session_destroy();
                    }
                }
                else{
                    //var_dump(mysqli_fetch_array($compare_data)['secret']);
                    //echo "1";
                    display_wrong_content(3); //Incorrect secret 
                    
                }
            }
            else{
                //echo "2";
                display_wrong_content(4); //cant identify the student 
            }

        }

        
        return array(false, 0);
    }

?>

<!DOCTYPE html>
<html>
<head>
	<title>Login Project Webpage</title>
    <link rel='stylesheet' type="text/css" href='styles/styles.css'>
</head>

<body>

	<h1>Gradebook Accessing Page </h1>

    <fieldset>
        
        <legend> My Gradebooks </legend>
        <!-- Always remember we need to use the action = "login.php" to pass the form to the page-->
        <form id = "form" action = "login.php" method = "post">

            <label for = "email"> Email:&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp </label>

                <input id = "input" type = "text" name = "email" size = "30" maxlength = "50"  oninput="passwordCheck();" required>
                
                <input type="submit" name = "login" value = "Login" style ="position: absolute; left: 55%; top: 50px;">
        
            </form>

            <!-- Javascript code do not needed to be embedded directly after calling the javascript method -->
            <script>
                function passwordCheck(){
                    var email_input = document.getElementById("input");
                    var regular_expression = /@(connect|cs)\.hku\.hk$/;

                    // We need to use the .value to refer we want to have its value
                    // Otherwise, we will got the object but not its value

                    if (!email_input.value.match(regular_expression)){
                        email_input.setCustomValidity("Must be an email address with @cs.hku.hk or @connect.hku.hk");
                    }
                    else{
                        //Reset the error message, problems will be caused if we dont have this "else" code
                        email_input.setCustomValidity("");
                    }
                    
                }

            </script>

        
    </fieldset>

</body>
</html>
