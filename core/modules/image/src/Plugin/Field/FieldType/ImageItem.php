<?php

namespace Drupal\image\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Plugin implementation of the 'image' field type.
 *
 * @FieldType(
 *   id = "image",
 *   label = @Translation("Image"),
 *   description = @Translation("This field stores the ID of an image file as an integer value."),
 *   category = @Translation("Reference"),
 *   default_widget = "image_image",
 *   default_formatter = "image",
 *   column_groups = {
 *     "file" = {
 *       "label" = @Translation("File"),
 *       "columns" = {
 *         "target_id", "width", "height"
 *       },
 *       "require_all_groups_for_translation" = TRUE
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
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class ImageItem extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'default_image' => [
        'uuid' => NULL,
        'alt' => '',
        'title' => '',
        'width' => NULL,
        'height' => NULL,
      ],
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = [
      'file_extensions' => 'png gif jpg jpeg',
      'alt_field' => 1,
      'alt_field_required' => 1,
      'title_field' => 0,
      'title_field_required' => 0,
      'max_resolution' => '',
      'min_resolution' => '',
      'default_image' => [
        'uuid' => NULL,
        'alt' => '',
        'title' => '',
        'width' => NULL,
        'height' => NULL,
      ],
    ] + parent::defaultFieldSettings();

    unset($settings['description_field']);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'target_id' => [
          'description' => 'The ID of the file entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'alt' => [
          'description' => "Alternative image text, for the image's 'alt' attribute.",
          'type' => 'varchar',
          'length' => 512,
        ],
        'title' => [
          'description' => "Image title text, for the image's 'title' attribute.",
          'type' => 'varchar',
          'length' => 1024,
        ],
        'width' => [
          'description' => 'The width of the image in pixels.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'height' => [
          'description' => 'The height of the image in pixels.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
      ],
      'foreign keys' => [
        'target_id' => [
          'table' => 'file_managed',
          'columns' => ['target_id' => 'fid'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    unset($properties['display']);
    unset($properties['description']);

    $properties['alt'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Alternative text'))
      ->setDescription(new TranslatableMarkup("Alternative image text, for the image's 'alt' attribute."));

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setDescription(new TranslatableMarkup("Image title text, for the image's 'title' attribute."));

    $properties['width'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Width'))
      ->setDescription(new TranslatableMarkup('The width of the image in pixels.'));

    $properties['height'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Height'))
      ->setDescription(new TranslatableMarkup('The height of the image in pixels.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    // We need the field-level 'default_image' setting, and $this->getSettings()
    // will only provide the instance-level one, so we need to explicitly fetch
    // the field.
    $settings = $this->getFieldDefinition()->getFieldStorageDefinition()->getSettings();

    $scheme_options = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    $element['uri_scheme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Upload destination'),
      '#options' => $scheme_options,
      '#default_value' => $settings['uri_scheme'],
      '#description' => $this->t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
    ];

    // Add default_image element.
    static::defaultImageForm($element, $settings);
    $element['default_image']['#description'] = $this->t('If no image is uploaded, this image will be shown on display.');

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
    $max_resolution = explode('x', $settings['max_resolution']) + ['', ''];
    $element['max_resolution'] = [
      '#type' => 'item',
      '#title' => $this->t('Maximum image resolution'),
      '#element_validate' => [[static::class, 'validateResolution']],
      '#weight' => 4.1,
      '#description' => $this->t('The maximum allowed image size expressed as WIDTH×HEIGHT (e.g. 640×480). Leave blank for no restriction. If a larger image is uploaded, it will be resized to reflect the given width and height. Resizing images on upload will cause the loss of <a href="http://wikipedia.org/wiki/Exchangeable_image_file_format">EXIF data</a> in the image.'),
    ];
    $element['max_resolution']['x'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum width'),
      '#title_display' => 'invisible',
      '#default_value' => $max_resolution[0],
      '#min' => 1,
      '#field_suffix' => ' × ',
      '#prefix' => '<div class="form--inline clearfix">',
    ];
    $element['max_resolution']['y'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum height'),
      '#title_display' => 'invisible',
      '#default_value' => $max_resolution[1],
      '#min' => 1,
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#suffix' => '</div>',
    ];

    $min_resolution = explode('x', $settings['min_resolution']) + ['', ''];
    $element['min_resolution'] = [
      '#type' => 'item',
      '#title' => $this->t('Minimum image resolution'),
      '#element_validate' => [[static::class, 'validateResolution']],
      '#weight' => 4.2,
      '#description' => $this->t('The minimum allowed image size expressed as WIDTH×HEIGHT (e.g. 640×480). Leave blank for no restriction. If a smaller image is uploaded, it will be rejected.'),
    ];
    $element['min_resolution']['x'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum width'),
      '#title_display' => 'invisible',
      '#default_value' => $min_resolution[0],
      '#min' => 1,
      '#field_suffix' => ' × ',
      '#prefix' => '<div class="form--inline clearfix">',
    ];
    $element['min_resolution']['y'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum height'),
      '#title_display' => 'invisible',
      '#default_value' => $min_resolution[1],
      '#min' => 1,
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#suffix' => '</div>',
    ];

    // Remove the description option.
    unset($element['description_field']);

    // Add title and alt configuration options.
    $element['alt_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable <em>Alt</em> field'),
      '#default_value' => $settings['alt_field'],
      '#description' => $this->t('Short description of the image used by screen readers and displayed when the image is not loaded. Enabling this field is recommended.'),
      '#weight' => 9,
    ];
    $element['alt_field_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<em>Alt</em> field required'),
      '#default_value' => $settings['alt_field_required'],
      '#description' => $this->t('Making this field required is recommended.'),
      '#weight' => 10,
      '#states' => [
        'visible' => [
          ':input[name="settings[alt_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['title_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable <em>Title</em> field'),
      '#default_value' => $settings['title_field'],
      '#description' => $this->t('The title attribute is used as a tooltip when the mouse hovers over the image. Enabling this field is not recommended as it can cause problems with screen readers.'),
      '#weight' => 11,
    ];
    $element['title_field_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<em>Title</em> field required'),
      '#default_value' => $settings['title_field_required'],
      '#weight' => 12,
      '#states' => [
        'visible' => [
          ':input[name="settings[title_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Add default_image element.
    static::defaultImageForm($element, $settings);
    $element['default_image']['#description'] = $this->t("If no image is uploaded, this image will be shown on display and will override the field's default image.");

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
    if ($this->entity && $this->entity instanceof EntityInterface) {
      if (empty($width) || empty($height)) {
        $image = \Drupal::service('image.factory')->get($this->entity->getFileUri());
        if ($image->isValid()) {
          $this->width = $image->getWidth();
          $this->height = $image->getHeight();
        }
      }
    }
    else {
      trigger_error(sprintf("Missing file with ID %s.", $this->target_id), E_USER_WARNING);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $settings = $field_definition->getSettings();
    static $images = [];

    $min_resolution = empty($settings['min_resolution']) ? '100x100' : $settings['min_resolution'];
    $max_resolution = empty($settings['max_resolution']) ? '600x600' : $settings['max_resolution'];
    $extensions = array_intersect(explode(' ', $settings['file_extensions']), ['png', 'gif', 'jpg', 'jpeg']);
    $extension = array_rand(array_combine($extensions, $extensions));
    // Generate a max of 5 different images.
    if (!isset($images[$extension][$min_resolution][$max_resolution]) || count($images[$extension][$min_resolution][$max_resolution]) <= 5) {
      /** @var \Drupal\Core\File\FileSystemInterface $file_system */
      $file_system = \Drupal::service('file_system');
      $tmp_file = $file_system->tempnam('temporary://', 'generateImage_');
      $destination = $tmp_file . '.' . $extension;
      try {
        $file_system->move($tmp_file, $destination);
      }
      catch (FileException $e) {
        // Ignore failed move.
      }
      if ($path = $random->image($file_system->realpath($destination), $min_resolution, $max_resolution)) {
        $image = File::create();
        $image->setFileUri($path);
        $image->setOwnerId(\Drupal::currentUser()->id());
        $guesser = \Drupal::service('file.mime_type.guesser');
        if ($guesser instanceof MimeTypeGuesserInterface) {
          $image->setMimeType($guesser->guessMimeType($path));
        }
        else {
          $image->setMimeType($guesser->guess($path));
          @trigger_error('\Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Implement \Symfony\Component\Mime\MimeTypeGuesserInterface instead. See https://www.drupal.org/node/3133341', E_USER_DEPRECATED);
        }
        $image->setFileName($file_system->basename($path));
        $destination_dir = static::doGetUploadLocation($settings);
        $file_system->prepareDirectory($destination_dir, FileSystemInterface::CREATE_DIRECTORY);
        $destination = $destination_dir . '/' . basename($path);
        $file = \Drupal::service('file.repository')->move($image, $destination);
        $images[$extension][$min_resolution][$max_resolution][$file->id()] = $file;
      }
      else {
        return [];
      }
    }
    else {
      // Select one of the images we've already generated for this field.
      $image_index = array_rand($images[$extension][$min_resolution][$max_resolution]);
      $file = $images[$extension][$min_resolution][$max_resolution][$image_index];
    }

    [$width, $height] = getimagesize($file->getFileUri());
    $values = [
      'target_id' => $file->id(),
      'alt' => $random->sentences(4),
      'title' => $random->sentences(4),
      'width' => $width,
      'height' => $height,
    ];
    return $values;
  }

  /**
   * Element validate function for resolution fields.
   */
  public static function validateResolution($element, FormStateInterface $form_state) {
    if (!empty($element['x']['#value']) || !empty($element['y']['#value'])) {
      foreach (['x', 'y'] as $dimension) {
        if (!$element[$dimension]['#value']) {
          // We expect the field name placeholder value to be wrapped in $this->t()
          // here, so it won't be escaped again as it's already marked safe.
          $form_state->setError($element[$dimension], new TranslatableMarkup('Both a height and width value must be specified in the @name field.', ['@name' => $element['#title']]));
          return;
        }
      }
      $form_state->setValueForElement($element, $element['x']['#value'] . 'x' . $element['y']['#value']);
    }
    else {
      $form_state->setValueForElement($element, '');
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
    $element['default_image'] = [
      '#type' => 'details',
      '#title' => $this->t('Default image'),
      '#open' => TRUE,
    ];
    // Convert the stored UUID to a FID.
    $fids = [];
    $uuid = $settings['default_image']['uuid'];
    if ($uuid && ($file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $uuid))) {
      $fids[0] = $file->id();
    }
    $element['default_image']['uuid'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image'),
      '#description' => $this->t('Image to be shown if no image is uploaded.'),
      '#default_value' => $fids,
      '#upload_location' => $settings['uri_scheme'] . '://default_images/',
      '#element_validate' => [
        '\Drupal\file\Element\ManagedFile::validateManagedFile',
        [static::class, 'validateDefaultImageForm'],
      ],
      '#upload_validators' => $this->getUploadValidators(),
    ];
    $element['default_image']['alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alternative text'),
      '#description' => $this->t('Short description of the image used by screen readers and displayed when the image is not loaded. This is important for accessibility.'),
      '#default_value' => $settings['default_image']['alt'],
      '#maxlength' => 512,
    ];
    $element['default_image']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The title attribute is used as a tooltip when the mouse hovers over the image.'),
      '#default_value' => $settings['default_image']['title'],
      '#maxlength' => 1024,
    ];
    $element['default_image']['width'] = [
      '#type' => 'value',
      '#value' => $settings['default_image']['width'],
    ];
    $element['default_image']['height'] = [
      '#type' => 'value',
      '#value' => $settings['default_image']['height'],
    ];
  }

  /**
   * Validates the managed_file element for the default Image form.
   *
   * This function ensures the fid is a scalar value and not an array. It is
   * assigned as an #element_validate callback in
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
      // Convert the file ID to a uuid.
      if ($file = \Drupal::entityTypeManager()->getStorage('file')->load($value)) {
        $value = $file->uuid();
      }
    }
    else {
      $value = '';
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
