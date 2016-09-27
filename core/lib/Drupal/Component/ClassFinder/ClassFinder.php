<?php

namespace Drupal\Component\ClassFinder;

use Doctrine\Common\Reflection\ClassFinderInterface;

/**
 * A Utility class that uses active autoloaders to find a file for a class.
 */
class ClassFinder implements ClassFinderInterface {

  /**
   * {@inheritdoc}
   */
  public function findFile($class) {
    $loaders = spl_autoload_functions();
    foreach ($loaders as $loader) {
      if (is_array($loader) && isset($loader[0]) && is_object($loader[0]) && method_exists($loader[0], 'findFile')) {
        $file = call_user_func_array([$loader[0], 'findFile'], [$class]);
        // Different implementations return different empty values. For example,
        // \Composer\Autoload\ClassLoader::findFile() returns FALSE whilst
        // \Doctrine\Common\Reflection\ClassFinderInterface::findFile()
        // documents that a NULL should be returned.
        if (!empty($file)) {
          return $file;
        }
      }
    }
    return NULL;
  }

}
