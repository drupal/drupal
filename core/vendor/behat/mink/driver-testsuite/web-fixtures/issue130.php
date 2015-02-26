<!DOCTYPE html>
<html>
<body>
    <?php
    if ('1' === $_GET['p']) {
        echo '<a href="issue130.php?p=2">Go to 2</a>';
    } else {
        echo '<strong>'.$_SERVER['HTTP_REFERER'].'</strong>';
    }
    ?>
</body>
