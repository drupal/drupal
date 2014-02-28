<?php

/**
 * @file
 * Contains \Drupal\Core\Archiver\Annotation\Archiver.
 */

namespace Drupal\Core\Archiver\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an archiver annotation object.
 *
 * @see \Drupal\Core\Archiver\ArchiverManager
 * @see hook_archiver_info_alter()
 *
 * @Annotation
 */
class Archiver extends Plugin {

  /**
   * The archiver plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the archiver plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * The description of the archiver plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * An array of valid extensions for this archiver.
   *
   * @var array
   */
  public $extensions;

}
