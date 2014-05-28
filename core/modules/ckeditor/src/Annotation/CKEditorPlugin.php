<?php

/**
 * @file
 * Contains Drupal\ckeditor\Annotation\CKEditorPlugin.
 */

namespace Drupal\ckeditor\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a CKEditorPlugin annotation object.
 *
 * @Annotation
 */
class CKEditorPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the CKEditor plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
