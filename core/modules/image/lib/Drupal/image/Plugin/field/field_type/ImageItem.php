<?php

/**
 * @image
 * Contains \Drupal\image\Plugin\field\field_type\ImageItem.
 */

namespace Drupal\image\Plugin\field\field_type;

use Drupal\field\FieldInterface;
use Drupal\file\Plugin\field\field_type\FileItem;

/**
 * Plugin implementation of the 'image' field type.
 *
 * @FieldType(
 *   id = "image",
 *   label = @Translation("Image"),
 *   description = @Translation("This field stores the ID of an image file as an integer value."),
 *   settings = {
 *     "uri_scheme" = "",
 *     "default_image" = "0",
 *     "column_groups" = {
 *       "file" = {
 *         "label" = @Translation("File"),
 *         "columns" = { "target_id", "width", "height" }
 *       },
 *       "alt" = {
 *         "label" = @Translation("Alt"),
 *         "translatable" = TRUE
 *       },
 *       "title" = {
 *         "label" = @Translation("Title"),
 *         "translatable" = TRUE
 *       }
 *     }
 *   },
 *   instance_settings = {
 *     "file_extensions" = "png gif jpg jpeg",
 *     "file_directory" = "",
 *     "max_filesize" = "",
 *     "alt_field" = "0",
 *     "alt_field_required" = "0",
 *     "title_field" = "0",
 *     "title_field_required" = "0",
 *     "max_resolution" = "",
 *     "min_resolution" = "",
 *     "default_image" = "0"
 *   },
 *   default_widget = "image_image",
 *   default_formatter = "image",
 *   list_class = "\Drupal\file\Plugin\field\field_type\FileFieldItemList"
 * )
 */
class ImageItem extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldInterface $field) {
    return array(
      'columns' => array(
        'target_id' => array(
          'description' => 'The ID of the file entity.',
          'type' => 'int',
          'not null' => TRUE,
          'unsigned' => TRUE,
        ),
        'alt' => array(
          'description' => "Alternative image text, for the image's 'alt' attribute.",
          'type' => 'varchar',
          'length' => 512,
          'not null' => FALSE,
        ),
        'title' => array(
          'description' => "Image title text, for the image's 'title' attribute.",
          'type' => 'varchar',
          'length' => 1024,
          'not null' => FALSE,
        ),
        'width' => array(
          'description' => 'The width of the image in pixels.',
          'type' => 'int',
          'unsigned' => TRUE,
        ),
        'height' => array(
          'description' => 'The height of the image in pixels.',
          'type' => 'int',
          'unsigned' => TRUE,
        ),
      ),
      'indexes' => array(
        'target_id' => array('target_id'),
      ),
      'foreign keys' => array(
        'target_id' => array(
          'table' => 'file_managed',
          'columns' => array('target_id' => 'fid'),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    $this->definition['settings']['target_type'] = 'file';

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions = parent::getPropertyDefinitions();

      static::$propertyDefinitions['alt'] = array(
        'type' => 'string',
        'label' => t("Alternative image text, for the image's 'alt' attribute."),
      );
      static::$propertyDefinitions['title'] = array(
        'type' => 'string',
        'label' => t("Image title text, for the image's 'title' attribute."),
      );
      static::$propertyDefinitions['width'] = array(
        'type' => 'integer',
        'label' => t('The width of the image in pixels.'),
      );
      static::$propertyDefinitions['height'] = array(
        'type' => 'integer',
        'label' => t('The height of the image in pixels.'),
      );
    }
    return static::$propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state, $has_data) {
    $element = array();

    // We need the field-level 'default_image' setting, and $this->getSettings()
    // will only provide the instance-level one, so we need to explicitly fetch
    // the field.
    $settings = $this->getFieldDefinition()->getField()->getFieldSettings();

    $scheme_options = array();
    foreach (file_get_stream_wrappers(STREAM_WRAPPERS_WRITE_VISIBLE) as $scheme => $stream_wrapper) {
      $scheme_options[$scheme] = $stream_wrapper['name'];
    }
    $element['uri_scheme'] = array(
      '#type' => 'radios',
      '#title' => t('Upload destination'),
      '#options' => $scheme_options,
      '#default_value' => $settings['uri_scheme'],
      '#description' => t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
    );

    $element['default_image'] = array(
      '#title' => t('Default image'),
      '#type' => 'managed_file',
      '#description' => t('If no image is uploaded, this image will be shown on display.'),
      '#default_value' => empty($settings['default_image']) ? array() : array($settings['default_image']),
      '#upload_location' => $settings['uri_scheme'] . '://default_images/',
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, array &$form_state) {
    // Get base form from FileItem::instanceSettingsForm().
    $element = parent::instanceSettingsForm($form, $form_state);

    $settings = $this->getFieldSettings();

    // Add maximum and minimum resolution settings.
    $max_resolution = explode('x', $settings['max_resolution']) + array('', '');
    $element['max_resolution'] = array(
      '#type' => 'item',
      '#title' => t('Maximum image resolution'),
      '#element_validate' => array(array(get_class($this), 'validateResolution')),
      '#weight' => 4.1,
      '#field_prefix' => '<div class="container-inline">',
      '#field_suffix' => '</div>',
      '#description' => t('The maximum allowed image size expressed as WIDTHxHEIGHT (e.g. 640x480). Leave blank for no restriction. If a larger image is uploaded, it will be resized to reflect the given width and height. Resizing images on upload will cause the loss of <a href="@url">EXIF data</a> in the image.', array('@url' => 'http://en.wikipedia.org/wiki/Exchangeable_image_file_format')),
    );
    $element['max_resolution']['x'] = array(
      '#type' => 'number',
      '#title' => t('Maximum width'),
      '#title_display' => 'invisible',
      '#default_value' => $max_resolution[0],
      '#min' => 1,
      '#field_suffix' => ' x ',
    );
    $element['max_resolution']['y'] = array(
      '#type' => 'number',
      '#title' => t('Maximum height'),
      '#title_display' => 'invisible',
      '#default_value' => $max_resolution[1],
      '#min' => 1,
      '#field_suffix' => ' ' . t('pixels'),
    );

    $min_resolution = explode('x', $settings['min_resolution']) + array('', '');
    $element['min_resolution'] = array(
      '#type' => 'item',
      '#title' => t('Minimum image resolution'),
      '#element_validate' => array(array(get_class($this), 'validateResolution')),
      '#weight' => 4.2,
      '#field_prefix' => '<div class="container-inline">',
      '#field_suffix' => '</div>',
      '#description' => t('The minimum allowed image size expressed as WIDTHxHEIGHT (e.g. 640x480). Leave blank for no restriction. If a smaller image is uploaded, it will be rejected.'),
    );
    $element['min_resolution']['x'] = array(
      '#type' => 'number',
      '#title' => t('Minimum width'),
      '#title_display' => 'invisible',
      '#default_value' => $min_resolution[0],
      '#min' => 1,
      '#field_suffix' => ' x ',
    );
    $element['min_resolution']['y'] = array(
      '#type' => 'number',
      '#title' => t('Minimum height'),
      '#title_display' => 'invisible',
      '#default_value' => $min_resolution[1],
      '#min' => 1,
      '#field_suffix' => ' ' . t('pixels'),
    );

    // Remove the description option.
    unset($element['description_field']);

    // Add title and alt configuration options.
    $element['alt_field'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable <em>Alt</em> field'),
      '#default_value' => $settings['alt_field'],
      '#description' => t('The alt attribute may be used by search engines, screen readers, and when the image cannot be loaded.'),
      '#weight' => 9,
    );
    $element['alt_field_required'] = array(
      '#type' => 'checkbox',
      '#title' => t('<em>Alt</em> field required'),
      '#default_value' => $settings['alt_field_required'],
      '#weight' => 10,
      '#states' => array(
        'visible' => array(
          ':input[name="instance[settings][alt_field]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $element['title_field'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable <em>Title</em> field'),
      '#default_value' => $settings['title_field'],
      '#description' => t('The title attribute is used as a tooltip when the mouse hovers over the image.'),
      '#weight' => 11,
    );
    $element['title_field_required'] = array(
      '#type' => 'checkbox',
      '#title' => t('<em>Title</em> field required'),
      '#default_value' => $settings['title_field_required'],
      '#weight' => 12,
      '#states' => array(
        'visible' => array(
          ':input[name="instance[settings][title_field]"]' => array('checked' => TRUE),
        ),
      ),
    );

    // Add the default image to the instance.
    $element['default_image'] = array(
      '#title' => t('Default image'),
      '#type' => 'managed_file',
      '#description' => t("If no image is uploaded, this image will be shown on display and will override the field's default image."),
      '#default_value' => empty($settings['default_image']) ? array() : array($settings['default_image']),
      '#upload_location' => $settings['uri_scheme'] . '://default_images/',
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $width = $this->width;
    $height = $this->height;

    // Determine the dimensions if necessary.
    if (empty($width) || empty($height)) {
      $image = \Drupal::service('image.factory')->get($this->entity->getFileUri());
      if ($image->isSupported()) {
        $this->width = $image->getWidth();
        $this->height =$image->getHeight();
      }
    }
  }

  /**
   * Element validate function for resolution fields.
   */
  public static function validateResolution($element, &$form_state) {
    if (!empty($element['x']['#value']) || !empty($element['y']['#value'])) {
      foreach (array('x', 'y') as $dimension) {
        if (!$element[$dimension]['#value']) {
          form_error($element[$dimension], t('Both a height and width value must be specified in the !name field.', array('!name' => $element['#title'])));
          return;
        }
      }
      form_set_value($element, $element['x']['#value'] . 'x' . $element['y']['#value'], $form_state);
    }
    else {
      form_set_value($element, '', $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isDisplayed() {
    // Image items do not have per-item visibility settings.
    return TRUE;
  }

}
