<?php

/**
 * @image
 * Contains \Drupal\image\Plugin\Field\FieldType\ImageItem.
 */

namespace Drupal\image\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin implementation of the 'image' field type.
 *
 * @FieldType(
 *   id = "image",
 *   label = @Translation("Image"),
 *   description = @Translation("This field stores the ID of an image file as an integer value."),
 *   default_widget = "image_image",
 *   default_formatter = "image",
 *   column_groups = {
 *     "file" = {
 *       "label" = @Translation("File"),
 *       "columns" = {
 *         "target_id", "width", "height"
 *       }
 *     },
 *     "alt" = {
 *       "label" = @Translation("Alt"),
 *       "translatable" = TRUE
 *     },
 *     "title" = {
 *       "label" = @Translation("Title"),
 *       "translatable" = TRUE
 *     },
 *   },
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList"
 * )
 */
class ImageItem extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'default_image' => array(
        'fid' => NULL,
        'alt' => '',
        'title' => '',
        'width' => NULL,
        'height' => NULL,
      ),
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = array(
      'file_extensions' => 'png gif jpg jpeg',
      'alt_field' => 0,
      'alt_field_required' => 0,
      'title_field' => 0,
      'title_field_required' => 0,
      'max_resolution' => '',
      'min_resolution' => '',
      'default_image' => array(
        'fid' => NULL,
        'alt' => '',
        'title' => '',
        'width' => NULL,
        'height' => NULL,
      ),
    ) + parent::defaultFieldSettings();

    unset($settings['description_field']);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
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
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['alt'] = DataDefinition::create('string')
      ->setLabel(t('Alternative text'))
      ->setDescription(t("Alternative image text, for the image's 'alt' attribute."));

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t("Image title text, for the image's 'title' attribute."));

    $properties['width'] = DataDefinition::create('integer')
      ->setLabel(t('Width'))
      ->setDescription(t('The width of the image in pixels.'));

    $properties['height'] = DataDefinition::create('integer')
      ->setLabel(t('Height'))
      ->setDescription(t('The height of the image in pixels.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = array();

    // We need the field-level 'default_image' setting, and $this->getSettings()
    // will only provide the instance-level one, so we need to explicitly fetch
    // the field.
    $settings = $this->getFieldDefinition()->getFieldStorageDefinition()->getSettings();

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

    // Add default_image element.
    static::defaultImageForm($element, $settings);
    $element['default_image']['#description'] = t('If no image is uploaded, this image will be shown on display.');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Get base form from FileItem.
    $element = parent::fieldSettingsForm($form, $form_state);

    $settings = $this->getSettings();

    // Add maximum and minimum resolution settings.
    $max_resolution = explode('×', $settings['max_resolution']) + array('', '');
    $element['max_resolution'] = array(
      '#type' => 'item',
      '#title' => t('Maximum image resolution'),
      '#element_validate' => array(array(get_class($this), 'validateResolution')),
      '#weight' => 4.1,
      '#field_prefix' => '<div class="container-inline">',
      '#field_suffix' => '</div>',
      '#description' => t('The maximum allowed image size expressed as WIDTH×HEIGHT (e.g. 640×480). Leave blank for no restriction. If a larger image is uploaded, it will be resized to reflect the given width and height. Resizing images on upload will cause the loss of <a href="@url">EXIF data</a> in the image.', array('@url' => 'http://en.wikipedia.org/wiki/Exchangeable_image_file_format')),
    );
    $element['max_resolution']['x'] = array(
      '#type' => 'number',
      '#title' => t('Maximum width'),
      '#title_display' => 'invisible',
      '#default_value' => $max_resolution[0],
      '#min' => 1,
      '#field_suffix' => ' × ',
    );
    $element['max_resolution']['y'] = array(
      '#type' => 'number',
      '#title' => t('Maximum height'),
      '#title_display' => 'invisible',
      '#default_value' => $max_resolution[1],
      '#min' => 1,
      '#field_suffix' => ' ' . t('pixels'),
    );

    $min_resolution = explode('×', $settings['min_resolution']) + array('', '');
    $element['min_resolution'] = array(
      '#type' => 'item',
      '#title' => t('Minimum image resolution'),
      '#element_validate' => array(array(get_class($this), 'validateResolution')),
      '#weight' => 4.2,
      '#field_prefix' => '<div class="container-inline">',
      '#field_suffix' => '</div>',
      '#description' => t('The minimum allowed image size expressed as WIDTH×HEIGHT (e.g. 640×480). Leave blank for no restriction. If a smaller image is uploaded, it will be rejected.'),
    );
    $element['min_resolution']['x'] = array(
      '#type' => 'number',
      '#title' => t('Minimum width'),
      '#title_display' => 'invisible',
      '#default_value' => $min_resolution[0],
      '#min' => 1,
      '#field_suffix' => ' × ',
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
      '#description' => t('The alt attribute may be used by search engines, screen readers, and when the image cannot be loaded. Enabling this field is recommended'),
      '#weight' => 9,
    );
    $element['alt_field_required'] = array(
      '#type' => 'checkbox',
      '#title' => t('<em>Alt</em> field required'),
      '#default_value' => $settings['alt_field_required'],
      '#description' => t('Making this field required is recommended.'),
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
      '#description' => t('The title attribute is used as a tooltip when the mouse hovers over the image. Enabling this field is not recommended as it can cause problems with screen readers.'),
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

    // Add default_image element.
    static::defaultImageForm($element, $settings);
    $element['default_image']['#description'] = t("If no image is uploaded, this image will be shown on display and will override the field's default image.");

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    $width = $this->width;
    $height = $this->height;

    // Determine the dimensions if necessary.
    if (empty($width) || empty($height)) {
      $image = \Drupal::service('image.factory')->get($this->entity->getFileUri());
      if ($image->isValid()) {
        $this->width = $image->getWidth();
        $this->height =$image->getHeight();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $settings = $field_definition->getSettings();
    static $images = array();

    $min_resolution = empty($settings['min_resolution']) ? '100x100' : $settings['min_resolution'];
    $max_resolution = empty($settings['max_resolution']) ? '600x600' : $settings['max_resolution'];
    $extensions = array_intersect(explode(' ', $settings['file_extensions']), array('png', 'gif', 'jpg', 'jpeg'));
    $extension = array_rand(array_combine($extensions, $extensions));
    // Generate a max of 5 different images.
    if (!isset($images[$extension][$min_resolution][$max_resolution]) || count($images[$extension][$min_resolution][$max_resolution]) <= 5) {
      $tmp_file = drupal_tempnam('temporary://', 'generateImage_');
      $destination = $tmp_file . '.' . $extension;
      file_unmanaged_move($tmp_file, $destination, FILE_CREATE_DIRECTORY);
      if ($path = $random->image(drupal_realpath($destination), $min_resolution, $max_resolution)) {
        $image = File::create();
        $image->setFileUri($path);
        // $image->setOwner($account);
        $image->setMimeType('image/' . pathinfo($path, PATHINFO_EXTENSION));
        $image->setFileName(drupal_basename($path));
        $destination_dir = $settings['uri_scheme'] . '://' . $settings['file_directory'];
        file_prepare_directory($destination_dir, FILE_CREATE_DIRECTORY);
        $destination = $destination_dir . '/' . basename($path);
        $file = file_move($image, $destination, FILE_CREATE_DIRECTORY);
        $images[$extension][$min_resolution][$max_resolution][$file->id()] = $file;
      }
      else {
        return array();
      }
    }
    else {
      // Select one of the images we've already generated for this field.
      $image_index = array_rand($images[$extension][$min_resolution][$max_resolution]);
      $file = $images[$extension][$min_resolution][$max_resolution][$image_index];
    }

    list($width, $height) = getimagesize($file->getFileUri());
    $values = array(
      'target_id' => $file->id(),
      'alt' => $random->sentences(4),
      'title' => $random->sentences(4),
      'width' =>$width,
      'height' => $height,
    );
    return $values;
  }

  /**
   * Element validate function for resolution fields.
   */
  public static function validateResolution($element, FormStateInterface $form_state) {
    if (!empty($element['x']['#value']) || !empty($element['y']['#value'])) {
      foreach (array('x', 'y') as $dimension) {
        if (!$element[$dimension]['#value']) {
          $form_state->setError($element[$dimension], t('Both a height and width value must be specified in the !name field.', array('!name' => $element['#title'])));
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
   * Builds the default_image details element.
   *
   * @param array $element
   *   The form associative array passed by reference.
   * @param array $settings
   *   The field settings array.
   */
  protected function defaultImageForm(array &$element, array $settings) {
    $element['default_image'] = array(
      '#type' => 'details',
      '#title' => t('Default image'),
      '#open' => TRUE,
    );
    $element['default_image']['fid'] = array(
      '#type' => 'managed_file',
      '#title' => t('Image'),
      '#description' => t('Image to be shown if no image is uploaded.'),
      '#default_value' => empty($settings['default_image']['fid']) ? array() : array($settings['default_image']['fid']),
      '#upload_location' => $settings['uri_scheme'] . '://default_images/',
      '#element_validate' => array('file_managed_file_validate', array(get_class($this), 'validateDefaultImageForm')),
      '#upload_validators' => $this->getUploadValidators(),
    );
    $element['default_image']['alt'] = array(
      '#type' => 'textfield',
      '#title' => t('Alternative text'),
      '#description' => t('This text will be used by screen readers, search engines, and when the image cannot be loaded.'),
      '#default_value' => $settings['default_image']['alt'],
      '#maxlength' => 512,
    );
    $element['default_image']['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => t('The title attribute is used as a tooltip when the mouse hovers over the image.'),
      '#default_value' => $settings['default_image']['title'],
      '#maxlength' => 1024,
    );
    $element['default_image']['width'] = array(
      '#type' => 'value',
      '#value' => $settings['default_image']['width'],
    );
    $element['default_image']['height'] = array(
      '#type' => 'value',
      '#value' => $settings['default_image']['height'],
    );
  }

  /**
   * Validates the managed_file element for the default Image form.
   *
   * This function ensures the fid is a scalar value and not an array. It is
   * assigned as a #element_validate callback in
   * \Drupal\image\Plugin\Field\FieldType\ImageItem::defaultImageForm().
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateDefaultImageForm(array &$element, FormStateInterface $form_state) {
    // Consolidate the array value of this field to a single FID as #extended
    // for default image is not TRUE and this is a single value.
    if (isset($element['fids']['#value'][0])) {
      $value = $element['fids']['#value'][0];
    }
    else {
      $value = 0;
    }
    $form_state->setValueForElement($element, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function isDisplayed() {
    // Image items do not have per-item visibility settings.
    return TRUE;
  }

}
