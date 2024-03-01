<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 Image plugin.
 *
 * @internal
 *   Plugin classes are internal.
 */
class Image extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {

  use CKEditor5PluginConfigurableTrait;
  use DynamicPluginConfigWithCsrfTokenUrlTrait;

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $config = $static_plugin_config;
    if ($editor->getImageUploadSettings()['status'] === TRUE) {
      $config += [
        'drupalImageUpload' => [
          'uploadUrl' => self::getUrlWithReplacedCsrfTokenPlaceholder(
            Url::fromRoute('ckeditor5.upload_image')
              ->setRouteParameter('editor', $editor->getFilterFormat()->id())
          ),
          'withCredentials' => TRUE,
          'headers' => ['Accept' => 'application/json', 'text/javascript'],
        ],
      ];
      $config['image']['insert']['integrations'][] = 'upload';
    }
    else {
      $config['image']['insert']['integrations'][] = 'url';
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\editor\Form\EditorImageDialog
   * @see editor_image_upload_settings_form()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form_state->loadInclude('editor', 'admin.inc');
    return editor_image_upload_settings_form($form_state->get('editor'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('status', (bool) $form_state->getValue('status'));
    $directory = $form_state->getValue(['directory']);
    $form_state->setValue(['directory'], trim($directory) === '' ? NULL : $directory);
    $max_size = $form_state->getValue(['max_size']);
    $form_state->setValue(['max_size'], trim($max_size) === '' ? NULL : $max_size);
    $max_width = $form_state->getValue(['max_dimensions', 'width']);
    $form_state->setValue(['max_dimensions', 'width'], trim($max_width) === '' ? NULL : (int) $max_width);
    $max_height = $form_state->getValue(['max_dimensions', 'height']);
    $form_state->setValue(['max_dimensions', 'height'], trim($max_height) === '' ? NULL : (int) $max_height);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValues();
    if (!$settings['status']) {
      // Remove all other settings to comply with config schema.
      $settings = ['status' => FALSE];
    }
    // Store this configuration in its out-of-band location.
    $form_state->get('editor')->setImageUploadSettings($settings);
  }

  /**
   * {@inheritdoc}
   *
   * This returns an empty array as image upload config is stored out of band.
   */
  public function defaultConfiguration() {
    return [];
  }

}
