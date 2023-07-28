<?php 

    session_start();
    $current_time_2 = time();


    //Redirecting the page if the user access the page simply by typing the url 
    if (!isset($_SESSION['login']) or ($_SESSION['login'] !== true) or ($_SESSION['time'] + 300 < $current_time_2)){
        header('location: http://localhost:9080/login.php'); //Relative root
        $_SESSION['login'] = false;
        exit;
    }

    
    //echo($uid);
    //echo($_SESSION['time']);
    //var_dump($uid);
    $uid = $_SESSION['uid'];

    index_start($uid);
    
    if (check_index()){
        //var_dump($_SESSION['course']);
        header("location: http://localhost:9080/courseinfo/getscore.php?course=".$_GET['course']);
    }



    function index_start($uid){
        
        $db_conn = mysqli_connect("mydb", "dummy", "c3322b", "db3322") 
        or die("Connection Error! ".mysqli_connect_error());

        $select_index_query = "SELECT course FROM courseinfo WHERE uid = '$uid' GROUP BY course";

        $course_data = mysqli_query($db_conn, $select_index_query) 
        or die("Query Error! ".mysqli_error($db_conn));

        if (mysqli_num_rows($course_data) > 0){
            ?>
            <h1> Course Information</h1>
                <h3 style="font-family:'Times New Roman', Times, serif;"> Retrieve continuous assessment scores for: </h3>
            <?php

            while ($row = mysqli_fetch_array($course_data)){
                ?>
                
                <a href="http://localhost:9080/courseinfo/getscore.php?course=<?php echo $row['course']; ?>" style="font-family:'Times New Roman', Times, serif; color:blue; position: relative; left: 50px;"><?php echo $row['course']; ?></a>
                <br>
                <br>
                <?php
                
            }
        }
        
    }

    function check_index(){
        if (isset($_GET['course'])){
            return true;
        }
        else{
            return false;
        }
    }
    

?>


