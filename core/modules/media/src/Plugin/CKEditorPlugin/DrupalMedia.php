<?php

namespace Drupal\media\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginCssInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "drupalmedia" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupalmedia",
 *   label = @Translation("Media Embed"),
 * )
 *
 * @internal
 *   This is an internal part of the media system in Drupal core and may be
 *   subject to change in minor releases. This class should not be
 *   instantiated or extended by external code.
 */
class DrupalMedia extends PluginBase implements ContainerFactoryPluginInterface, CKEditorPluginContextualInterface, CKEditorPluginCssInterface {

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs a new DrupalMedia plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleExtensionList $extension_list_module) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleExtensionList = $extension_list_module;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('extension.list.module')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'core/jquery',
      'core/drupal',
      'core/drupal.ajax',
      'media/media_embed_ckeditor_theme',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->moduleExtensionList->getPath('media') . '/js/plugins/drupalmedia/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'drupalMedia_previewCsrfToken' => \Drupal::csrfToken()->get('X-Drupal-MediaPreview-CSRF-Token'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    if (!$editor->hasAssociatedFilterFormat()) {
      return FALSE;
    }

    // Automatically enable this plugin if the text format associated with this
    // text editor uses the media_embed filter.
    $filters = $editor->getFilterFormat()->filters();
    return $filters->has('media_embed') && $filters->get('media_embed')->status;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Improve this in https://www.drupal.org/project/drupal/issues/3072063
   */
  public function getCssFiles(Editor $editor) {
    return [
      $this->moduleExtensionList->getPath('media') . '/css/plugins/drupalmedia/ckeditor.drupalmedia.css',
      $this->moduleExtensionList->getPath('system') . '/css/components/hidden.module.css',
    ];
  }

}
