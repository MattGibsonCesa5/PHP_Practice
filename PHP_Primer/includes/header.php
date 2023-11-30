<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- declare a $title variable on each individual page -->
    <title>PHP Primer - <?php echo $title ?></title>
</head>

<body>
    <div class="container">
        <h2>Follow each link to the page example</h2>
        <ul class="nav">
            <li><a class="nav-link" href="index.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="array.php">Arrays</a></li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Loops
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="forloop.php">For Loop</a></li>
                    <li><a class="dropdown-item" href="dowhileloop.php">Do While Loop</a></li>
                    <li><a class="dropdown-item" href="whileloop.php">While Loop</a></li>
                </ul>
            </li>
            <li class="nav-item"><a class="nav-link" href="ifstatement.php">If Statement</a></li>
            <li class="nav-item"><a class="nav-link" href="swithchstatement.php">Switch Statement</a></li>
            <li class="nav-item"><a class="nav-link" href=" userdefinedfunctions.php">Functions</a></li>
            <li class="nav-item"><a class="nav-link" href=" datetimemanipulation.php">date time</a></li>
            <li class="nav-item"><a class="nav-link" href=" stringmanipulation.php">String manipulation</a></li>
            <li class="nav-item"><a class="nav-link" href=" includeandrequire.php">include and require</a></li>
        </ul>
        <?php echo "<h1>$h1<h1>" ?>