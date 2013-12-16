<?php

/**
 * @file
 * Contains \Drupal\migrate\Annotation\MigrateProcessPlugin.
 */

namespace Drupal\migrate\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a migration process plugin annotation object.
 *
 * @Annotation
 */
class MigrateProcessPlugin extends Plugin {

  /**
   * A unique identifier for the process plugin.
   *
   * @var string
   */
  public $id;

  /**
   * Whether the plugin handles multiples itself.
   *
   * Typically these plugins will expect an array as input and iterate over it
   * themselves, changing the whole array. For example the 'iterator' and the
   * 'flatten' plugins. If the plugin only need to change a single value it
   * can skip setting this attribute and let
   * \Drupal\migrate\MigrateExecutable::processRow() handle the iteration.
   *
   * @var bool (optional)
   */
  public $handle_multiples = FALSE;
}
