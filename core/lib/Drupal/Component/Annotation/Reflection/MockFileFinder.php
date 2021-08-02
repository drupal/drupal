<?php

namespace Drupal\Component\Annotation\Reflection;

use Drupal\Component\ClassFinder\ClassFinderInterface;

/**
 * Defines a mock file finder that only returns a single filename.
 *
 * This can be used with
 * Drupal\Component\Annotation\Doctrine\StaticReflectionParser if the filename
 * is known and inheritance is not a concern (for example, if only the class
 * annotation is needed).
 */
class MockFileFinder implements ClassFinderInterface {

  /**
   * The only filename this finder ever returns.
   *
   * @var string
   */
  protected $filename;

  /**
   * {@inheritdoc}
   */
  public function findFile($class) {
    return $this->filename;
  }

  /**
   * Creates new mock file finder objects.
   */
  public static function create($filename) {
    $object = new static();
    $object->filename = $filename;
    return $object;
  }

}
