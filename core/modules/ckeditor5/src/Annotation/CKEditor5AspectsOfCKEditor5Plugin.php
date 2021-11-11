<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the "CKEditor 5 aspects of a CKEditor5Plugin" annotation object.
 *
 * Plugin Namespace: Plugin\CKEditor5Plugin.
 *
 * @see \Drupal\ckeditor5\Plugin\CKEditorPluginInterface
 * @see \Drupal\ckeditor5\Plugin\CKEditorPluginBase
 * @see \Drupal\ckeditor5\Plugin\CKEditorPluginManager
 * @see plugin_api
 *
 * @Annotation
 * @see \Drupal\ckeditor5\Annotation\CKEditor5Plugin
 * @see \Drupal\ckeditor5\Annotation\DrupalPartsOfCKEditor5Plugin
 */
class CKEditor5AspectsOfCKEditor5Plugin extends Plugin {

  /**
   * The CKEditor5 plugin classes provided.
   *
   * Found in the CKEditor5 global js object as {package.Class}.
   *
   * @var string[]
   */
  public $plugins;

  /**
   * A keyed array of additional values for the CKEditor5 constructor config.
   *
   * This property is optional and it does not need to be declared.
   *
   * @var array[]
   */
  public $config = [];

}
