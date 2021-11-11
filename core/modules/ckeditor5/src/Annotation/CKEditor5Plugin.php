<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Annotation;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Component\Annotation\Plugin;

/**
 * Defines a CKEditor5Plugin annotation object.
 *
 * Plugin Namespace: Plugin\CKEditor5Plugin.
 *
 * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginInterface
 * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface
 * @see plugin_api
 *
 * @Annotation
 * @see \Drupal\ckeditor5\Annotation\CKEditor5AspectsOfCKEditor5Plugin
 * @see \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin
 */
class CKEditor5Plugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The CKEditor 5 aspects of the plugin definition.
   *
   * @var \Drupal\ckeditor5\Annotation\CKEditor5AspectsOfCKEditor5Plugin
   */
  public $ckeditor5;

  /**
   * The Drupal aspects of the plugin definition.
   *
   * @var \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin
   */
  public $drupal;

  /**
   * {@inheritdoc}
   *
   * Overridden for compatibility with the AnnotationBridgeDecorator, which
   * ensures YAML-defined CKEditor 5 plugin definitions are also processed by
   * annotations. Unfortunately it does not (yet) support nested annotations.
   * Force YAML-defined plugin definitions to be parsed by the
   * annotations, to ensure consistent handling of defaults.
   *
   * @see \Drupal\Component\Annotation\Plugin\Discovery\AnnotationBridgeDecorator::getDefinitions()
   */
  public function __construct($values) {
    if (isset($values['ckeditor5']) && is_array($values['ckeditor5'])) {
      $values['ckeditor5'] = new CKEditor5AspectsOfCKEditor5Plugin($values['ckeditor5']);
    }
    if (isset($values['drupal']) && is_array($values['drupal'])) {
      $values['drupal'] = new DrupalAspectsOfCKEditor5Plugin($values['drupal']);
    }
    parent::__construct($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    return $this->definition['drupal']['class'];
  }

  /**
   * {@inheritdoc}
   */
  public function setClass($class) {
    $this->definition['drupal']['class'] = $class;
  }

  /**
   * {@inheritdoc}
   */
  public function get(): CKEditor5PluginDefinition {
    return new CKEditor5PluginDefinition($this->definition);
  }

}
