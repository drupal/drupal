<?php

namespace Drupal\ckeditor_test\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "Llama" plugin, with a CKEditor "llama" feature.
 *
 * This feature does not correspond to a toolbar button. Because this plugin
 * does not implement the CKEditorPluginContextualInterface nor the
 * CKEditorPluginButtonsInterface interface, there is no way of actually loading
 * this plugin.
 *
 * @see \Drupal\ckeditor_test\Plugin\CKEditorPlugin\LlamaContextual
 * @see \Drupal\ckeditor_test\Plugin\CKEditorPlugin\LlamaButton
 * @see \Drupal\ckeditor_test\Plugin\CKEditorPlugin\LlamaContextualAndButton
 * @see \Drupal\ckeditor_test\Plugin\CKEditorPlugin\LlamaCss
 *
 * @CKEditorPlugin(
 *   id = "llama",
 *   label = @Translation("Llama")
 * )
 */
class Llama extends PluginBase implements CKEditorPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The module list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
    $instance->moduleList = $container->get('extension.list.module');
    return $instance;
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
    return [];
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
  public function getFile() {
    return $this->moduleList->getPath('ckeditor_test') . '/js/llama.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
