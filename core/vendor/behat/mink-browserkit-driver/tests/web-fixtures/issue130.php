<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<html>
<body>
    <?php
    if ('1' === $app['request']->query->get('p')) {
        echo '<a href="/issue130.php?p=2">Go to 2</a>';
    } else {
        echo '<strong>'.$app['request']->headers->get('referer').'</strong>';
    }
    ?>
</body>
