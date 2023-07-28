<?php 
    session_start();
    $current_time_3 = time();

    //Redirecting the page if the user access the page simply by typing the url 
    if (!isset($_SESSION['login']) or ($_SESSION['login'] !== true) or ($_SESSION['time'] + 300 < $current_time_3)){
        
        header('location: http://localhost:9080/login.php'); //Relative root
        $_SESSION['login'] = false;
        //$_SESSION['uid'] = NULL;
        exit;
    }

    
    getscore_start();
    

    function getscore_start(){
        $db_conn = mysqli_connect("mydb", "dummy", "c3322b", "db3322") 
        or die("Connection Error! ".mysqli_connect_error());
       
        //var_dump($_SESSION['uid']);
        //var_dump($_SESSION['course']);

        //We need to assign the superglobal variables before inserting them inside the php query
        // We access the course from the link, as the link have question mark and course = .....
        $getscore_course = $_GET['course']; 
        $getscore_uid = $_SESSION['uid'];

        //We cannot directly write/insert $_SESSEION to SQL query, same concept as $_GET/$_POST variables
        $select_getscore_query = "SELECT * FROM courseinfo WHERE uid = $getscore_uid AND course = '$getscore_course'";

        $filtered_data = mysqli_query($db_conn, $select_getscore_query)
        or die("Query Error! ".mysqli_error($db_conn));
        

        if (mysqli_num_rows($filtered_data) > 0 ){
            
            ?>
            
            <h1 style="font-family: 'Times New Roman', Times, serif;"> <?php echo $getscore_course ?> - Gradebook </h1>
            <table style="position: absolute; left: 30%; top: 100px;">
            <tr>
                <th style = "font-family: 'Times New Roman', Times, serif; background-color: #dddddd; font-size: large; border: 1px solid black; width: 150px; height: 50px; "> Item </th>
                <th style = "font-family: 'Times New Roman', Times, serif; background-color: #dddddd; font-size: large; border: 1px solid black; width: 150px; height: 50px;"> Score </th>
            </tr>

            <?php
            $sum = 0;
            while($row = mysqli_fetch_array($filtered_data)){
                if ($row['course'] == $getscore_course){
                    $sum += $row['score'];

                    //echo $row['assign']." ".$row['score'];
                    ?>
                    <tr>
                        <td style = "font-family: 'Times New Roman', Times, serif; font-size: large; border: 1px solid black; text-align:center; height: 50px;"> <?php echo $row['assign']; ?> </td>
                        <td style = "font-family: 'Times New Roman', Times, serif; font-size: large; border: 1px solid black; text-align:center; height: 50px;"> <?php echo $row['score']; ?></td>
                    </tr>
                    <?php
                }
            }
            ?>
                <td style = "font-family: 'Times New Roman', Times, serif; font-size: large; border: 1px solid black; text-align:center; height: 50px;"> Total </td>
                <td style = "font-family: 'Times New Roman', Times, serif; font-size: large; border: 1px solid black; text-align:center; height: 50px;"> <?php echo $sum; ?></td>

            </table>
            <?php
        }
        else{
            ?>
            
            <h1 style="font-family: 'Times New Roman', Times, serif;"> <?php echo $getscore_course ?> - Gradebook </h1>
            <h1 style="font-family: 'Times New Roman', Times, serif;"> You do not have the gradebook for the course: <?php echo $getscore_course ?> </h1>
            <?php
        }
        


        mysqli_free_result($filtered_data);
        mysqli_close($db_conn);


    }


?>