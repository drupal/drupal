<?php

/**
 * @file
 * Contains \Drupal\Component\Annotation\Reflection\MockFileFinder.
 */

namespace Drupal\Component\Annotation\Reflection;

use Doctrine\Common\Reflection\ClassFinderInterface;

/**
 * Defines a mock file finder that only returns a single filename.
 *
 * This can be used with Doctrine\Common\Reflection\StaticReflectionParser if
 * the filename is known and inheritance is not a concern (for example, if
 * only the class annotation is needed).
 */
class MockFileFinder implements ClassFinderInterface {

  /**
   * The only filename this finder ever returns.
   *
   * @var string
   */
  protected $filename;

  /**
   * Implements Doctrine\Common\Reflection\ClassFinderInterface::findFile().
   */
  public function findFile($class) {
    return $this->filename;
  }

  /**
   * Creates new mock file finder objects.
   */
  static public function create($filename) {
    $object = new static();
    $object->filename = $filename;
    return $object;
  }

}
