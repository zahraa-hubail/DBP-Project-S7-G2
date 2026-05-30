<?php

function getConnection()
{
    $dbc = mysqli_connect("localhost", "u202301089", "asdASD123!", "db202301089");

    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        die('b0ther');
    }

    return $dbc;
}
    //dbpr2group
?>


