
<?php
$title = "Arrays";

 $h1 = "Arrays";
 include "./includes/header.php";?>

<?php
$numArray = [1,2,39,4,5,66,7,83,91];
echo $numArray[0];
echo "</br>";
$myArraysLength = count($numArray);
echo "$myArraysLength";
echo "</br>";
echo "</br>";
echo "</br>";

for ($i=0; $i <$myArraysLength ; $i++) { 
   echo " $numArray[$i]";
   echo "</br>";
}

?>

<?php require "./includes/footer.php"?>
