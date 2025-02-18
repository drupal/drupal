<?php

namespace Drupal\image\Hook;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\file\FileInterface;
use Drupal\image\Controller\ImageStyleDownloadController;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for image.
 */
class ImageHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.image':
        $field_ui_url = \Drupal::moduleHandler()->moduleExists('field_ui') ? Url::fromRoute('help.page', ['name' => 'field_ui'])->toString() : '#';
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Image module allows you to create fields that contain image files and to configure <a href=":image_styles">Image styles</a> that can be used to manipulate the display of images. See the <a href=":field">Field module help</a> and the <a href=":field_ui">Field UI help</a> pages for terminology and general information on entities, fields, and how to create and manage fields. For more information, see the <a href=":image_documentation">online documentation for the Image module</a>.', [
          ':image_styles' => Url::fromRoute('entity.image_style.collection')->toString(),
          ':field' => Url::fromRoute('help.page', [
            'name' => 'field',
          ])->toString(),
          ':field_ui' => $field_ui_url,
          ':image_documentation' => 'https://www.drupal.org/docs/core-modules-and-themes/core-modules/image-module/working-with-images',
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dt>' . $this->t('Defining image styles') . '</dt>';
        $output .= '<dd>' . $this->t('The concept of image styles is that you can upload a single image but display it in several ways; each display variation, or <em>image style</em>, is the result of applying one or more <em>effects</em> to the original image. As an example, you might upload a high-resolution image with a 4:3 aspect ratio, and display it scaled down, square cropped, or black-and-white (or any combination of these effects). The Image module provides a way to do this efficiently: you configure an image style with the desired effects on the <a href=":image">Image styles page</a>, and the first time a particular image is requested in that style, the effects are applied. The resulting image is saved, and the next time that same style is requested, the saved image is retrieved without the need to recalculate the effects. Drupal core provides several effects that you can use to define styles; others may be provided by contributed modules.', [
          ':image' => Url::fromRoute('entity.image_style.collection')->toString(),
        ]);
        $output .= '<dt>' . $this->t('Naming image styles') . '</dt>';
        $output .= '<dd>' . $this->t('When you define an image style, you will need to choose a displayed name and a machine name. The displayed name is shown in administrative pages, and the machine name is used to generate the URL for accessing an image processed in that style. There are two common approaches to naming image styles: either based on the effects being applied (for example, <em>Square 85x85</em>), or based on where you plan to use it (for example, <em>Profile picture</em>).') . '</dd>';
        $output .= '<dt>' . $this->t('Configuring image fields') . '</dt>';
        $output .= '<dd>' . $this->t('A few of the settings for image fields are defined once when you create the field and cannot be changed later; these include the choice of public or private file storage and the number of images that can be stored in the field. The rest of the settings can be edited later; these settings include the field label, help text, allowed file extensions, image dimensions restrictions, and the subdirectory in the public or private file storage where the images will be stored. The editable settings can also have different values for different entity sub-types; for instance, if your image field is used on both Page and Article content types, you can store the files in a different subdirectory for the two content types.') . '</dd>';
        $output .= '<dd>' . $this->t('For accessibility and search engine optimization, all images that convey meaning on websites should have alternate text. Drupal also allows entry of title text for images, but it can lead to confusion for screen reader users and its use is not recommended. Image fields can be configured so that alternate and title text fields are enabled or disabled; if enabled, the fields can be set to be required. The recommended setting is to enable and require alternate text and disable title text.') . '</dd>';
        $output .= '<dd>' . $this->t('When you create an image field, you will need to choose whether the uploaded images will be stored in the public or private file directory defined in your settings.php file and shown on the <a href=":file-system">File system page</a>. This choice cannot be changed later. You can also configure your field to store files in a subdirectory of the public or private directory; this setting can be changed later and can be different for each entity sub-type using the field. For more information on file storage, see the <a href=":system-help">System module help page</a>.', [
          ':file-system' => Url::fromRoute('system.file_system_settings')->toString(),
          ':system-help' => Url::fromRoute('help.page', [
            'name' => 'system',
          ])->toString(),
        ]) . '</dd>';
        $output .= '<dd>' . $this->t('The maximum file size that can be uploaded is limited by PHP settings of the server, but you can restrict it further by configuring a <em>Maximum upload size</em> in the field settings (this setting can be changed later). The maximum file size, either from PHP server settings or field configuration, is automatically displayed to users in the help text of the image field.') . '</dd>';
        $output .= '<dd>' . $this->t('You can also configure a minimum and/or maximum dimensions for uploaded images. Images that are too small will be rejected. Images that are to large will be resized. During the resizing the <a href="http://wikipedia.org/wiki/Exchangeable_image_file_format">EXIF data</a> in the image will be lost.') . '</dd>';
        $output .= '<dd>' . $this->t('You can also configure a default image that will be used if no image is uploaded in an image field. This default can be defined for all instances of the field in the field storage settings when you create a field, and the setting can be overridden for each entity sub-type that uses the field.') . '</dd>';
        $output .= '<dt>' . $this->t('Configuring displays and form displays') . '</dt>';
        $output .= '<dd>' . $this->t('On the <em>Manage display</em> page, you can choose the image formatter, which determines the image style used to display the image in each display mode and whether or not to display the image as a link. On the <em>Manage form display</em> page, you can configure the image upload widget, including setting the preview image style shown on the entity edit form.') . '</dd>';
        $output .= '</dl>';
        return $output;

      case 'entity.image_style.collection':
        return '<p>' . $this->t('Image styles commonly provide thumbnail sizes by scaling and cropping images, but can also add various effects before an image is displayed. When an image is displayed with a style, a new file is created and the original image is left unchanged.') . '</p>';

      case 'image.effect_add_form':
        $effect = \Drupal::service('plugin.manager.image.effect')->getDefinition($route_match->getParameter('image_effect'));
        return isset($effect['description']) ? '<p>' . $effect['description'] . '</p>' : NULL;

      case 'image.effect_edit_form':
        $effect = $route_match->getParameter('image_style')->getEffect($route_match->getParameter('image_effect'));
        $effect_definition = $effect->getPluginDefinition();
        return isset($effect_definition['description']) ? '<p>' . $effect_definition['description'] . '</p>' : NULL;
    }
    return NULL;
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
          // Theme functions in image.module.
      'image_style' => [
      // HTML 4 and XHTML 1.0 always require an alt attribute. The HTML 5 draft
      // allows the alt attribute to be omitted in some cases. Therefore,
      // default the alt attribute to an empty string, but allow code using
      // '#theme' => 'image_style' to pass explicit NULL for it to be omitted.
      // Usually, neither omission nor an empty string satisfies accessibility
      // requirements, so it is strongly encouraged for code using '#theme' =>
      // 'image_style' to pass a meaningful value for the alt variable.
      // - https://www.w3.org/TR/REC-html40/struct/objects.html#h-13.8
      // - https://www.w3.org/TR/xhtml1/dtds.html
      // - http://dev.w3.org/html5/spec/Overview.html#alt
      // The title attribute is optional in all cases, so it is omitted by
      // default.
        'variables' => [
          'style_name' => NULL,
          'uri' => NULL,
          'width' => NULL,
          'height' => NULL,
          'alt' => '',
          'title' => NULL,
          'attributes' => [],
        ],
      ],
          // Theme functions in image.admin.inc.
      'image_style_preview' => [
        'variables' => [
          'style' => NULL,
        ],
        'file' => 'image.admin.inc',
      ],
      'image_anchor' => [
        'render element' => 'element',
        'file' => 'image.admin.inc',
      ],
      'image_resize_summary' => [
        'variables' => [
          'data' => NULL,
          'effect' => [],
        ],
      ],
      'image_scale_summary' => [
        'variables' => [
          'data' => NULL,
          'effect' => [],
        ],
      ],
      'image_crop_summary' => [
        'variables' => [
          'data' => NULL,
          'effect' => [],
        ],
      ],
      'image_scale_and_crop_summary' => [
        'variables' => [
          'data' => NULL,
          'effect' => [],
        ],
      ],
      'image_rotate_summary' => [
        'variables' => [
          'data' => NULL,
          'effect' => [],
        ],
      ],
          // Theme functions in image.field.inc.
      'image_widget' => [
        'render element' => 'element',
        'file' => 'image.field.inc',
      ],
      'image_formatter' => [
        'variables' => [
          'item' => NULL,
          'item_attributes' => NULL,
          'url' => NULL,
          'image_style' => NULL,
        ],
        'file' => 'image.field.inc',
      ],
    ];
  }

  /**
   * Implements hook_file_download().
   *
   * Control the access to files underneath the styles directory.
   */
  #[Hook('file_download')]
  public function fileDownload($uri): array|int|null {
    $path = StreamWrapperManager::getTarget($uri);
    // Private file access for image style derivatives.
    if (str_starts_with($path, 'styles/')) {
      $args = explode('/', $path);
      // Discard "styles", style name, and scheme from the path
      $args = array_slice($args, 3);
      // Then the remaining parts are the path to the image.
      $original_uri = StreamWrapperManager::getScheme($uri) . '://' . implode('/', $args);
      // Check that the file exists and is an image.
      $image = \Drupal::service('image.factory')->get($uri);
      if ($image->isValid()) {
        // If the image style converted the extension, it has been added to the
        // original file, resulting in filenames like image.png.jpeg. So to find
        // the actual source image, we remove the extension and check if that
        // image exists.
        if (!file_exists($original_uri)) {
          $converted_original_uri = ImageStyleDownloadController::getUriWithoutConvertedExtension($original_uri);
          if ($converted_original_uri !== $original_uri && file_exists($converted_original_uri)) {
            // The converted file does exist, use it as the source.
            $original_uri = $converted_original_uri;
          }
        }
        // Check the permissions of the original to grant access to this image.
        $headers = \Drupal::moduleHandler()->invokeAll('file_download', [$original_uri]);
        // Confirm there's at least one module granting access and none denying
        // access.
        if (!empty($headers) && !in_array(-1, $headers)) {
          return [
                // Send headers describing the image's size, and MIME-type.
            'Content-Type' => $image->getMimeType(),
            'Content-Length' => $image->getFileSize(),
          ];
        }
      }
      return -1;
    }
    // If it is the sample image we need to grant access.
    $samplePath = \Drupal::config('image.settings')->get('preview_image');
    if ($path === $samplePath) {
      $image = \Drupal::service('image.factory')->get($samplePath);
      return [
            // Send headers describing the image's size, and MIME-type.
        'Content-Type' => $image->getMimeType(),
        'Content-Length' => $image->getFileSize(),
      ];
    }
    return NULL;
  }

  /**
   * Implements hook_file_move().
   */
  #[Hook('file_move')]
  public function fileMove(FileInterface $file, FileInterface $source): void {
    // Delete any image derivatives at the original image path.
    image_path_flush($source->getFileUri());
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete() for file entities.
   */
  #[Hook('file_predelete')]
  public function filePredelete(FileInterface $file): void {
    // Delete any image derivatives of this image.
    image_path_flush($file->getFileUri());
  }

  /**
   * Implements hook_entity_presave().
   *
   * Transforms default image of image field from array into single value at
   * save.
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity): void {
    // Get the default image settings, return if not saving an image field
    // storage or image field entity.
    $default_image = [];
    if (($entity instanceof FieldStorageConfigInterface || $entity instanceof FieldConfigInterface) && $entity->getType() == 'image') {
      $default_image = $entity->getSetting('default_image');
    }
    else {
      return;
    }
    if ($entity->isSyncing()) {
      return;
    }
    $uuid = $default_image['uuid'];
    if ($uuid) {
      $original_uuid = $entity->getOriginal()?->getSetting('default_image')['uuid'];
      if ($uuid != $original_uuid) {
        $file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $uuid);
        if ($file) {
          $image = \Drupal::service('image.factory')->get($file->getFileUri());
          $default_image['width'] = $image->getWidth();
          $default_image['height'] = $image->getHeight();
        }
        else {
          $default_image['uuid'] = NULL;
        }
      }
    }
    // Both FieldStorageConfigInterface and FieldConfigInterface have a
    // setSetting() method.
    $entity->setSetting('default_image', $default_image);
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for 'field_storage_config'.
   */
  #[Hook('field_storage_config_update')]
  public function fieldStorageConfigUpdate(FieldStorageConfigInterface $field_storage): void {
    if ($field_storage->getType() != 'image') {
      // Only act on image fields.
      return;
    }
    $prior_field_storage = $field_storage->getOriginal();
    // The value of a managed_file element can be an array if #extended == TRUE.
    $uuid_new = $field_storage->getSetting('default_image')['uuid'];
    $uuid_old = $prior_field_storage->getSetting('default_image')['uuid'];
    $file_new = $uuid_new ? \Drupal::service('entity.repository')->loadEntityByUuid('file', $uuid_new) : FALSE;
    if ($uuid_new != $uuid_old) {
      // Is there a new file?
      if ($file_new) {
        $file_new->setPermanent();
        $file_new->save();
        \Drupal::service('file.usage')->add($file_new, 'image', 'default_image', $field_storage->uuid());
      }
      // Is there an old file?
      if ($uuid_old && ($file_old = \Drupal::service('entity.repository')->loadEntityByUuid('file', $uuid_old))) {
        \Drupal::service('file.usage')->delete($file_old, 'image', 'default_image', $field_storage->uuid());
      }
    }
    // If the upload destination changed, then move the file.
    if ($file_new && StreamWrapperManager::getScheme($file_new->getFileUri()) != $field_storage->getSetting('uri_scheme')) {
      $directory = $field_storage->getSetting('uri_scheme') . '://default_images/';
      \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
      \Drupal::service('file.repository')->move($file_new, $directory . $file_new->getFilename());
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for 'field_config'.
   */
  #[Hook('field_config_update')]
  public function fieldConfigUpdate(FieldConfigInterface $field): void {
    $field_storage = $field->getFieldStorageDefinition();
    if ($field_storage->getType() != 'image') {
      // Only act on image fields.
      return;
    }
    $prior_instance = $field->getOriginal();
    $uuid_new = $field->getSetting('default_image')['uuid'];
    $uuid_old = $prior_instance->getSetting('default_image')['uuid'];
    // If the old and new files do not match, update the default accordingly.
    $file_new = $uuid_new ? \Drupal::service('entity.repository')->loadEntityByUuid('file', $uuid_new) : FALSE;
    if ($uuid_new != $uuid_old) {
      // Save the new file, if present.
      if ($file_new) {
        $file_new->setPermanent();
        $file_new->save();
        \Drupal::service('file.usage')->add($file_new, 'image', 'default_image', $field->uuid());
      }
      // Delete the old file, if present.
      if ($uuid_old && ($file_old = \Drupal::service('entity.repository')->loadEntityByUuid('file', $uuid_old))) {
        \Drupal::service('file.usage')->delete($file_old, 'image', 'default_image', $field->uuid());
      }
    }
    // If the upload destination changed, then move the file.
    if ($file_new && StreamWrapperManager::getScheme($file_new->getFileUri()) != $field_storage->getSetting('uri_scheme')) {
      $directory = $field_storage->getSetting('uri_scheme') . '://default_images/';
      \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
      \Drupal::service('file.repository')->move($file_new, $directory . $file_new->getFilename());
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for 'field_storage_config'.
   */
  #[Hook('field_storage_config_delete')]
  public function fieldStorageConfigDelete(FieldStorageConfigInterface $field): void {
    if ($field->getType() != 'image') {
      // Only act on image fields.
      return;
    }
    // The value of a managed_file element can be an array if #extended == TRUE.
    $uuid = $field->getSetting('default_image')['uuid'];
    if ($uuid && ($file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $uuid))) {
      \Drupal::service('file.usage')->delete($file, 'image', 'default_image', $field->uuid());
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for 'field_config'.
   */
  #[Hook('field_config_delete')]
  public function fieldConfigDelete(FieldConfigInterface $field): void {
    $field_storage = $field->getFieldStorageDefinition();
    if ($field_storage->getType() != 'image') {
      // Only act on image fields.
      return;
    }
    // The value of a managed_file element can be an array if #extended == TRUE.
    $uuid = $field->getSetting('default_image')['uuid'];
    // Remove the default image when the instance is deleted.
    if ($uuid && ($file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $uuid))) {
      \Drupal::service('file.usage')->delete($file, 'image', 'default_image', $field->uuid());
    }
  }

}
