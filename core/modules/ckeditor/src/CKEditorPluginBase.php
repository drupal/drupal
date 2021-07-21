<?php

namespace Drupal\ckeditor;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines a base CKEditor plugin implementation.
 *
 * No other CKEditor plugins can be internal, unless a different CKEditor build
 * than the one provided by Drupal core is used. Most CKEditor plugins don't
 * need to provide additional settings forms.
 *
 * This base class assumes that your plugin has buttons that you want to be
 * enabled through the toolbar builder UI. It is still possible to also
 * implement the CKEditorPluginContextualInterface (for contextual enabling) and
 * CKEditorPluginConfigurableInterface interfaces (for configuring plugin
 * settings).
 *
 * NOTE: the Drupal plugin ID should correspond to the CKEditor plugin name.
 *
 * @see \Drupal\ckeditor\CKEditorPluginInterface
 * @see \Drupal\ckeditor\CKEditorPluginButtonsInterface
 * @see \Drupal\ckeditor\CKEditorPluginContextualInterface
 * @see \Drupal\ckeditor\CKEditorPluginConfigurableInterface
 * @see \Drupal\ckeditor\CKEditorPluginManager
 * @see \Drupal\ckeditor\Annotation\CKEditorPlugin
 * @see plugin_api
 */
abstract class CKEditorPluginBase extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * The module list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * Gets the module list service.
   *
   * @return \Drupal\Core\Extension\ModuleExtensionList
   *   The module extension list service.
   */
  protected function getModuleList(): ModuleExtensionList {
    if (!$this->moduleList) {
      $this->moduleList = \Drupal::service('extension.list.module');
    }
    return $this->moduleList;
  }

  /**
   * Gets the Drupal-root relative installation directory of a module.
   *
   * @param string $module_name
   *   The machine name of the module.
   *
   * @return string
   *   The module installation directory.
   *
   * @throws \InvalidArgumentException
   *   If there is no extension with the supplied machine name.
   *
   * @see \Drupal\Core\Extension\ExtensionList::getPath()
   */
  protected function getModulePath(string $module_name): string {
    return $this->getModuleList()->getPath($module_name);
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
    return [];
  }

}
