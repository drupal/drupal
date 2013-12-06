<?php

/**
 * @file
 * Contains \Drupal\block\Annotation\Block.
 */

namespace Drupal\block\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Block annotation object.
 *
 * @Annotation
 */
class Block extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the block.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $admin_label = '';

}
