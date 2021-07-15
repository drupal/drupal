<?php

namespace Drupal\ckeditor\Plugin\CKEditorPlugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginCssInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "drupalimagecaption" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupalimagecaption",
 *   label = @Translation("Drupal image caption widget"),
 *   module = "ckeditor"
 * )
 */
class DrupalImageCaption extends PluginBase implements CKEditorPluginInterface, CKEditorPluginContextualInterface, CKEditorPluginCssInterface, ContainerFactoryPluginInterface {

  /**
   * The module extension list.
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
      'ckeditor/drupal.ckeditor.plugins.drupalimagecaption',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->moduleList->getPath('ckeditor') . '/js/plugins/drupalimagecaption/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $format = $editor->getFilterFormat();
    return [
      'image2_captionedClass' => 'caption caption-img',
      'image2_alignClasses' => ['align-left', 'align-center', 'align-right'],
      'drupalImageCaption_captionPlaceholderText' => $this->t('Enter caption here'),
      // Only enable those parts of DrupalImageCaption for which the
      // corresponding Drupal text filters are enabled.
      'drupalImageCaption_captionFilterEnabled' => $format->filters('filter_caption')->status,
      'drupalImageCaption_alignFilterEnabled' => $format->filters('filter_align')->status,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCssFiles(Editor $editor) {
    return [
      $this->moduleList->getPath('ckeditor') . '/css/plugins/drupalimagecaption/ckeditor.drupalimagecaption.css',
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
    // text editor uses the filter_align or filter_caption filter and the
    // DrupalImage button is enabled.
    $format = $editor->getFilterFormat();
    if ($format->filters('filter_align')->status || $format->filters('filter_caption')->status) {
      $enabled = FALSE;
      $settings = $editor->getSettings();
      foreach ($settings['toolbar']['rows'] as $row) {
        foreach ($row as $group) {
          foreach ($group['items'] as $button) {
            if ($button === 'DrupalImage') {
              $enabled = TRUE;
            }
          }
        }
      }
      return $enabled;
    }

    return FALSE;
  }

}
