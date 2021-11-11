<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\editor\EditorInterface;

// cspell:ignore imageupload

/**
 * CKEditor 5 Image plugin.
 *
 * @CKEditor5Plugin(
 *   id = "ckeditor5_imageUpload",
 *   ckeditor5 = @CKEditor5AspectsOfCKEditor5Plugin(
 *     plugins = {"image.ImageUpload", "drupalImage.DrupalImageUpload"},
 *     config = {
 *       "image" = {
 *         "upload" = {
 *           "types" = { "jpeg", "png", "gif" }
 *         }
 *       }
 *     },
 *   ),
 *   drupal = @DrupalAspectsOfCKEditor5Plugin(
 *     label = @Translation("Image Upload"),
 *     elements = false,
 *     admin_library = "ckeditor5/admin.imageupload",
 *     toolbar_items = {
 *       "uploadImage" = { "label" = "Image upload" }
 *     },
 *     conditions = {
 *       "toolbarItem" = "uploadImage",
 *       "imageUploadStatus" = true,
 *     }
 *   )
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class ImageUpload extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {

  use CKEditor5PluginConfigurableTrait;
  use DynamicPluginConfigWithCsrfTokenUrlTrait;

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    return $static_plugin_config + [
      'drupalImageUpload' => [
        'uploadUrl' => self::getUrlWithReplacedCsrfTokenPlaceholder(
          Url::fromRoute('ckeditor5.upload_image')
            ->setRouteParameter('editor', $editor->getFilterFormat()->id())
        ),
        'withCredentials' => TRUE,
        'headers' => ['Accept' => 'application/json', 'text/javascript'],
      ],
    ];
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
    $form_state->setValue(['max_dimensions', 'width'], (int) $form_state->getValue(['max_dimensions', 'width']));
    $form_state->setValue(['max_dimensions', 'height'], (int) $form_state->getValue(['max_dimensions', 'height']));
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Store this configuration in its out-of-band location.
    $form_state->get('editor')->setImageUploadSettings($form_state->getValues());
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
