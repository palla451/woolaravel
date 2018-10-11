<?php
// we connect to example.com and port 3307
$link = mysql_connect('142.93.49.84:3306', 'root', 'root');
if (!$link) {
    die('Could not connect: ' . mysql_error());
}
echo 'Connected successfully';
mysql_close($link);

?>


