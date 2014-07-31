<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Field\FieldType\FileItem.
 */

namespace Drupal\file\Plugin\Field\FieldType;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'file' field type.
 *
 * @FieldType(
 *   id = "file",
 *   label = @Translation("File"),
 *   description = @Translation("This field stores the ID of a file as an integer value."),
 *   default_widget = "file_generic",
 *   default_formatter = "file_default",
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList"
 * )
 */
class FileItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'target_type' => 'file',
      'display_field' => FALSE,
      'display_default' => FALSE,
      'uri_scheme' => file_default_scheme(),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultInstanceSettings() {
    return array(
      'file_extensions' => 'txt',
      'file_directory' => '',
      'max_filesize' => '',
      'description_field' => 0,
    ) + parent::defaultInstanceSettings();
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
        'display' => array(
          'description' => 'Flag to control whether this file should be displayed when viewing content.',
          'type' => 'int',
          'size' => 'tiny',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 1,
        ),
        'description' => array(
          'description' => 'A description of the file.',
          'type' => 'text',
          'not null' => FALSE,
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

    $properties['display'] = DataDefinition::create('boolean')
      ->setLabel(t('Flag to control whether this file should be displayed when viewing content'));

    $properties['description'] = DataDefinition::create('string')
      ->setLabel(t('A description of the file'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = array();

    $element['#attached']['library'][] = 'file/drupal.file';

    $element['display_field'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable <em>Display</em> field'),
      '#default_value' => $this->getSetting('display_field'),
      '#description' => t('The display option allows users to choose if a file should be shown when viewing the content.'),
    );
    $element['display_default'] = array(
      '#type' => 'checkbox',
      '#title' => t('Files displayed by default'),
      '#default_value' => $this->getSetting('display_default'),
      '#description' => t('This setting only has an effect if the display option is enabled.'),
      '#states' => array(
        'visible' => array(
          ':input[name="field[settings][display_field]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $scheme_options = array();
    foreach (file_get_stream_wrappers(STREAM_WRAPPERS_WRITE_VISIBLE) as $scheme => $stream_wrapper) {
      $scheme_options[$scheme] = $stream_wrapper['name'];
    }
    $element['uri_scheme'] = array(
      '#type' => 'radios',
      '#title' => t('Upload destination'),
      '#options' => $scheme_options,
      '#default_value' => $this->getSetting('uri_scheme'),
      '#description' => t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
      '#disabled' => $has_data,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, FormStateInterface $form_state) {
    $element = array();
    $settings = $this->getSettings();

    $element['file_directory'] = array(
      '#type' => 'textfield',
      '#title' => t('File directory'),
      '#default_value' => $settings['file_directory'],
      '#description' => t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes.'),
      '#element_validate' => array(array(get_class($this), 'validateDirectory')),
      '#weight' => 3,
    );

    // Make the extension list a little more human-friendly by comma-separation.
    $extensions = str_replace(' ', ', ', $settings['file_extensions']);
    $element['file_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions'),
      '#default_value' => $extensions,
      '#description' => t('Separate extensions with a space or comma and do not include the leading dot.'),
      '#element_validate' => array(array(get_class($this), 'validateExtensions')),
      '#weight' => 1,
      '#maxlength' => 256,
      // By making this field required, we prevent a potential security issue
      // that would allow files of any type to be uploaded.
      '#required' => TRUE,
    );

    $element['max_filesize'] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum upload size'),
      '#default_value' => $settings['max_filesize'],
      '#description' => t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes will be limited only by PHP\'s maximum post and file upload sizes (current limit <strong>%limit</strong>).', array('%limit' => format_size(file_upload_max_size()))),
      '#size' => 10,
      '#element_validate' => array(array(get_class($this), 'validateMaxFilesize')),
      '#weight' => 5,
    );

    $element['description_field'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable <em>Description</em> field'),
      '#default_value' => isset($settings['description_field']) ? $settings['description_field'] : '',
      '#description' => t('The description field allows users to enter a description about the uploaded file.'),
      '#parents' => array('instance', 'settings', 'description_field'),
      '#weight' => 11,
    );

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
   * instanceSettingsForm().
   */
  public static function validateDirectory($element, FormStateInterface $form_state) {
    // Strip slashes from the beginning and end of $element['file_directory'].
    $value = trim($element['#value'], '\\/');
    form_set_value($element, $value, $form_state);
  }

  /**
   * Form API callback.
   *
   * This function is assigned as an #element_validate callback in
   * instanceSettingsForm().
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
        form_error($element, $form_state, t('The list of allowed extensions is not valid, be sure to exclude leading dots and to separate extensions with a comma or space.'));
      }
      else {
        form_set_value($element, $extensions, $form_state);
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
   * instanceSettingsForm().
   */
  public static function validateMaxFilesize($element, FormStateInterface $form_state) {
    if (!empty($element['#value']) && !is_numeric(Bytes::toInt($element['#value']))) {
      form_error($element, $form_state, t('The "!name" option must contain a valid value. You may either leave the text field empty or enter a string like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes).', array('!name' => t($element['title']))));
    }
  }

  /**
   * Determines the URI for a file field instance.
   *
   * @param $data
   *   An array of token objects to pass to token_replace().
   *
   * @return
   *   A file directory URI with tokens replaced.
   *
   * @see token_replace()
   */
  public function getUploadLocation($data = array()) {
    $settings = $this->getSettings();
    $destination = trim($settings['file_directory'], '/');

    // Replace tokens.
    $destination = \Drupal::token()->replace($destination, $data);

    return $settings['uri_scheme'] . '://' . $destination;
  }

  /**
   * Retrieves the upload validators for a file field.
   *
   * @return
   *   An array suitable for passing to file_save_upload() or the file field
   *   element's '#upload_validators' property.
   */
  public function getUploadValidators() {
    $validators = array();
    $settings = $this->getSettings();

    // Cap the upload size according to the PHP limit.
    $max_filesize = Bytes::toInt(file_upload_max_size());
    if (!empty($settings['max_filesize'])) {
      $max_filesize = min($max_filesize, Bytes::toInt($settings['max_filesize']));
    }

    // There is always a file size limit due to the PHP server limit.
    $validators['file_validate_size'] = array($max_filesize);

    // Add the extension check if necessary.
    if (!empty($settings['file_extensions'])) {
      $validators['file_validate_extensions'] = array($settings['file_extensions']);
    }

    return $validators;
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

}
