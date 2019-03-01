<?php

namespace Drupal\file\Plugin\Field\FieldType;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Environment;
use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'file' field type.
 *
 * @FieldType(
 *   id = "file",
 *   label = @Translation("File"),
 *   description = @Translation("This field stores the ID of a file as an integer value."),
 *   category = @Translation("Reference"),
 *   default_widget = "file_generic",
 *   default_formatter = "file_default",
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class FileItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => 'file',
      'display_field' => FALSE,
      'display_default' => FALSE,
      'uri_scheme' => file_default_scheme(),
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'file_extensions' => 'txt',
      'file_directory' => '[date:custom:Y]-[date:custom:m]',
      'max_filesize' => '',
      'description_field' => 0,
    ] + parent::defaultFieldSettings();
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
        'display' => [
          'description' => 'Flag to control whether this file should be displayed when viewing content.',
          'type' => 'int',
          'size' => 'tiny',
          'unsigned' => TRUE,
          'default' => 1,
        ],
        'description' => [
          'description' => 'A description of the file.',
          'type' => 'text',
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

    $properties['display'] = DataDefinition::create('boolean')
      ->setLabel(t('Display'))
      ->setDescription(t('Flag to control whether this file should be displayed when viewing content'));

    $properties['description'] = DataDefinition::create('string')
      ->setLabel(t('Description'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    $element['#attached']['library'][] = 'file/drupal.file';

    $element['display_field'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable <em>Display</em> field'),
      '#default_value' => $this->getSetting('display_field'),
      '#description' => t('The display option allows users to choose if a file should be shown when viewing the content.'),
    ];
    $element['display_default'] = [
      '#type' => 'checkbox',
      '#title' => t('Files displayed by default'),
      '#default_value' => $this->getSetting('display_default'),
      '#description' => t('This setting only has an effect if the display option is enabled.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[display_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $scheme_options = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    $element['uri_scheme'] = [
      '#type' => 'radios',
      '#title' => t('Upload destination'),
      '#options' => $scheme_options,
      '#default_value' => $this->getSetting('uri_scheme'),
      '#description' => t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
      '#disabled' => $has_data,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $settings = $this->getSettings();

    $element['file_directory'] = [
      '#type' => 'textfield',
      '#title' => t('File directory'),
      '#default_value' => $settings['file_directory'],
      '#description' => t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes.'),
      '#element_validate' => [[get_class($this), 'validateDirectory']],
      '#weight' => 3,
    ];

    // Make the extension list a little more human-friendly by comma-separation.
    $extensions = str_replace(' ', ', ', $settings['file_extensions']);
    $element['file_extensions'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions'),
      '#default_value' => $extensions,
      '#description' => t('Separate extensions with a space or comma and do not include the leading dot.'),
      '#element_validate' => [[get_class($this), 'validateExtensions']],
      '#weight' => 1,
      '#maxlength' => 256,
      // By making this field required, we prevent a potential security issue
      // that would allow files of any type to be uploaded.
      '#required' => TRUE,
    ];

    $element['max_filesize'] = [
      '#type' => 'textfield',
      '#title' => t('Maximum upload size'),
      '#default_value' => $settings['max_filesize'],
      '#description' => t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes will be limited only by PHP\'s maximum post and file upload sizes (current limit <strong>%limit</strong>).', ['%limit' => format_size(Environment::getUploadMaxSize())]),
      '#size' => 10,
      '#element_validate' => [[get_class($this), 'validateMaxFilesize']],
      '#weight' => 5,
    ];

    $element['description_field'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable <em>Description</em> field'),
      '#default_value' => isset($settings['description_field']) ? $settings['description_field'] : '',
      '#description' => t('The description field allows users to enter a description about the uploaded file.'),
      '#weight' => 11,
    ];

    return $element;
  }

  /**
   * Form API callback
   *
   * Removes slashes from the beginning and end of the destination value and
   * ensures that the file directory path is not included at the beginning of the
   * value.
   *
   * This function is assigned as an #element_validate callback in
   * fieldSettingsForm().
   */
  public static function validateDirectory($element, FormStateInterface $form_state) {
    // Strip slashes from the beginning and end of $element['file_directory'].
    $value = trim($element['#value'], '\\/');
    $form_state->setValueForElement($element, $value);
  }

  /**
   * Form API callback.
   *
   * This function is assigned as an #element_validate callback in
   * fieldSettingsForm().
   *
   * This doubles as a convenience clean-up function and a validation routine.
   * Commas are allowed by the end-user, but ultimately the value will be stored
   * as a space-separated list for compatibility with file_validate_extensions().
   */
  public static function validateExtensions($element, FormStateInterface $form_state) {
    if (!empty($element['#value'])) {
      $extensions = preg_replace('/([, ]+\.?)/', ' ', trim(strtolower($element['#value'])));
      $extensions = array_filter(explode(' ', $extensions));
      $extensions = implode(' ', array_unique($extensions));
      if (!preg_match('/^([a-z0-9]+([.][a-z0-9])* ?)+$/', $extensions)) {
        $form_state->setError($element, t('The list of allowed extensions is not valid, be sure to exclude leading dots and to separate extensions with a comma or space.'));
      }
      else {
        $form_state->setValueForElement($element, $extensions);
      }
    }
  }

  /**
   * Form API callback.
   *
   * Ensures that a size has been entered and that it can be parsed by
   * \Drupal\Component\Utility\Bytes::toInt().
   *
   * This function is assigned as an #element_validate callback in
   * fieldSettingsForm().
   */
  public static function validateMaxFilesize($element, FormStateInterface $form_state) {
    if (!empty($element['#value']) && !is_numeric(Bytes::toInt($element['#value']))) {
      $form_state->setError($element, t('The "@name" option must contain a valid value. You may either leave the text field empty or enter a string like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes).', ['@name' => $element['title']]));
    }
  }

  /**
   * Determines the URI for a file field.
   *
   * @param array $data
   *   An array of token objects to pass to Token::replace().
   *
   * @return string
   *   An unsanitized file directory URI with tokens replaced. The result of
   *   the token replacement is then converted to plain text and returned.
   *
   * @see \Drupal\Core\Utility\Token::replace()
   */
  public function getUploadLocation($data = []) {
    return static::doGetUploadLocation($this->getSettings(), $data);
  }

  /**
   * Determines the URI for a file field.
   *
   * @param array $settings
   *   The array of field settings.
   * @param array $data
   *   An array of token objects to pass to Token::replace().
   *
   * @return string
   *   An unsanitized file directory URI with tokens replaced. The result of
   *   the token replacement is then converted to plain text and returned.
   *
   * @see \Drupal\Core\Utility\Token::replace()
   */
  protected static function doGetUploadLocation(array $settings, $data = []) {
    $destination = trim($settings['file_directory'], '/');

    // Replace tokens. As the tokens might contain HTML we convert it to plain
    // text.
    $destination = PlainTextOutput::renderFromHtml(\Drupal::token()->replace($destination, $data));
    return $settings['uri_scheme'] . '://' . $destination;
  }

  /**
   * Retrieves the upload validators for a file field.
   *
   * @return array
   *   An array suitable for passing to file_save_upload() or the file field
   *   element's '#upload_validators' property.
   */
  public function getUploadValidators() {
    $validators = [];
    $settings = $this->getSettings();

    // Cap the upload size according to the PHP limit.
    $max_filesize = Bytes::toInt(Environment::getUploadMaxSize());
    if (!empty($settings['max_filesize'])) {
      $max_filesize = min($max_filesize, Bytes::toInt($settings['max_filesize']));
    }

    // There is always a file size limit due to the PHP server limit.
    $validators['file_validate_size'] = [$max_filesize];

    // Add the extension check if necessary.
    if (!empty($settings['file_extensions'])) {
      $validators['file_validate_extensions'] = [$settings['file_extensions']];
    }

    return $validators;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $settings = $field_definition->getSettings();

    // Prepare destination.
    $dirname = static::doGetUploadLocation($settings);
    \Drupal::service('file_system')->prepareDirectory($dirname, FileSystemInterface::CREATE_DIRECTORY);

    // Generate a file entity.
    $destination = $dirname . '/' . $random->name(10, TRUE) . '.txt';
    $data = $random->paragraphs(3);
    $file = file_save_data($data, $destination, FileSystemInterface::EXISTS_ERROR);
    $values = [
      'target_id' => $file->id(),
      'display' => (int) $settings['display_default'],
      'description' => $random->sentences(10),
    ];
    return $values;
  }

  /**
   * Determines whether an item should be displayed when rendering the field.
   *
   * @return bool
   *   TRUE if the item should be displayed, FALSE if not.
   */
  public function isDisplayed() {
    if ($this->getSetting('display_field')) {
      return (bool) $this->display;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    return [];
  }

}
