<?php
// phpcs:ignoreFile

namespace Drupal\Component\Annotation\Doctrine\Compatibility;

use const PHP_VERSION_ID;
use function class_alias;

if (PHP_VERSION_ID >= 80000) {
    class_alias('Drupal\Component\Annotation\Doctrine\Compatibility\Php8\ReflectionClass', 'Drupal\Component\Annotation\Doctrine\Compatibility\ReflectionClass');
} else {
    class_alias('Drupal\Component\Annotation\Doctrine\Compatibility\Php7\ReflectionClass', 'Drupal\Component\Annotation\Doctrine\Compatibility\ReflectionClass');
}

if (false) {
    class ReflectionClass
    {
    }
}
