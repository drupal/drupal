<?php

/**
 * @file
 * Definition of Drupal\Core\Annotation\Plugin.
 */

namespace Drupal\Core\Annotation;

use Drupal\Core\Annotation\AnnotationInterface;

/**
 * Defines a Plugin annotation object.
 *
 * Annotations in plugin classes can utilize this class in order to pass
 * various metadata about the plugin through the parser to
 * DiscoveryInterface::getDefinitions() calls. This allows the metadata
 * of a class to be located with the class itself, rather than in module-based
 * info hooks.
 *
 * @Annotation
 */
class Plugin implements AnnotationInterface {

  /**
   * The plugin definiton read from the class annotation.
   *
   * @var array
   */
  protected $definition;

  /**
   * Constructs a Plugin object.
   *
   * Builds up the plugin definition and invokes the get() method for any
   * classed annotations that were used.
   */
  public function __construct($values) {
    foreach ($values as $key => $value) {
      if ($value instanceof AnnotationInterface) {
        $this->definition[$key] = $value->get();
      }
      else {
        $this->definition[$key] = $value;
      }
    }
  }

  /**
   * Implements Drupal\Core\Annotation\AnnotationInterface::get().
   */
  public function get() {
    return $this->definition;
  }

}
