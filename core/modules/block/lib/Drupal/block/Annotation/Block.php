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
 * @ingroup block_api
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

  /**
   * The category in the admin UI where the block will be listed.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category = '';

  /**
   * Class used to retrieve derivative definitions of the block.
   *
   * @var string
   */
  public $derivative = '';

}
