<?php

$x = array(new Foo, array());
$y = new Foo;
$z = new $class;
$bar = new $foo[$x + 1];

$obj1 = new DateTime;
$obj1 = new \DateTime;
$obj2 = $obj1->add(new \DateTime);
$obj2 = $obj1->add(new Vendor);
$obj2 = $obj1->add(new Vendor\DateTools);
$obj2 = $obj1->add(new Vendor\DateTools\DateInterval);
$obj2 = $obj1->add(new \Vendor\DateTools\DateInterval);
