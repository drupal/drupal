<?php

namespace Drupal\Core\Archiver\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an archiver annotation object.
 *
 * Plugin Namespace: Plugin\Archiver
 *
 * For a working example, see \Drupal\system\Plugin\Archiver\Zip
 *
 * @see \Drupal\Core\Archiver\ArchiverManager
 * @see \Drupal\Core\Archiver\ArchiverInterface
 * @see plugin_api
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
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the archiver plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * An array of valid extensions for this archiver.
   *
   * @var array
   */
  public $extensions;

}
