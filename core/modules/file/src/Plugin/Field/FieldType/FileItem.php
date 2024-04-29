<?php

namespace Drupal\file\Plugin\Field\FieldType;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Environment;
use Drupal\Component\Utility\Random;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\Validation\FileValidatorSettingsTrait;

/**
 * Plugin implementation of the 'file' field type.
 */
#[FieldType(
  id: "file",
  label: new TranslatableMarkup("File"),
  description: [
    new TranslatableMarkup("For uploading files"),
    new TranslatableMarkup("Can be configured with options such as allowed file extensions and maximum upload size"),
  ],
  category: "file_upload",
  default_widget: "file_generic",
  default_formatter: "file_default",
  list_class: FileFieldItemList::class,
  constraints: ["ReferenceAccess" => [], "FileValidation" => []],
  column_groups: [
    'target_id' => [
      'label' => new TranslatableMarkup('File'),
      'translatable' => TRUE,
    ],
    'display' => [
      'label' => new TranslatableMarkup('Display'),
      'translatable' => TRUE,
    ],
    'description' => [
      'label' => new TranslatableMarkup('Description'),
      'translatable' => TRUE,
    ],
  ],
)]
class FileItem extends EntityReferenceItem {

  use FileValidatorSettingsTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => 'file',
      'display_field' => FALSE,
      'display_default' => FALSE,
      'uri_scheme' => \Drupal::config('system.file')->get('default_scheme'),
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
      ->setLabel(new TranslatableMarkup('Display'))
      ->setDescription(new TranslatableMarkup('Flag to control whether this file should be displayed when viewing content'));

    $properties['description'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Description'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsSummary(FieldStorageDefinitionInterface $storage_definition): array {
    // Bypass the parent setting summary as it produces redundant information.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    $element['#attached']['library'][] = 'file/drupal.file';

    $element['display_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable <em>Display</em> field'),
      '#default_value' => $this->getSetting('display_field'),
      '#description' => $this->t('The display option allows users to choose if a file should be shown when viewing the content.'),
    ];
    $element['display_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Files displayed by default'),
      '#default_value' => $this->getSetting('display_default'),
      '#description' => $this->t('This setting only has an effect if the display option is enabled.'),
      '#states' => [
        'visible' => [
          ':input[name="field_storage[subform][settings][display_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $scheme_options = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    $element['uri_scheme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Upload destination'),
      '#options' => $scheme_options,
      '#default_value' => $this->getSetting('uri_scheme'),
      '#description' => $this->t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
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
      '#title' => $this->t('File directory'),
      '#default_value' => $settings['file_directory'],
      '#description' => $this->t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes.'),
      '#element_validate' => [[static::class, 'validateDirectory']],
      '#weight' => 3,
    ];

    // Make the extension list a little more human-friendly by comma-separation.
    $extensions = str_replace(' ', ', ', $settings['file_extensions']);
    $element['file_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#default_value' => $extensions,
      '#description' => $this->t("Separate extensions with a comma or space. Each extension can contain alphanumeric characters, '.', and '_', and should start and end with an alphanumeric character."),
      '#element_validate' => [[static::class, 'validateExtensions']],
      '#weight' => 1,
      '#maxlength' => 256,
      // By making this field required, we prevent a potential security issue
      // that would allow files of any type to be uploaded.
      '#required' => TRUE,
    ];

    $element['max_filesize'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum upload size'),
      '#default_value' => $settings['max_filesize'],
      '#description' => $this->t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes could be limited only by PHP\'s maximum post and file upload sizes (current limit <strong>%limit</strong>).', [
        '%limit' => ByteSizeMarkup::create(Environment::getUploadMaxSize()),
      ]),
      '#size' => 10,
      '#element_validate' => [[static::class, 'validateMaxFilesize']],
      '#weight' => 5,
    ];

    $element['description_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable <em>Description</em> field'),
      '#default_value' => $settings['description_field'] ?? '',
      '#description' => $this->t('The description field allows users to enter a description about the uploaded file.'),
      '#weight' => 11,
    ];

    return $element;
  }

  /**
   * Form API callback.
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
   * as a space-separated list for compatibility with the 'FileExtension'
   * constraint.
   */
  public static function validateExtensions($element, FormStateInterface $form_state) {
    if (!empty($element['#value'])) {
      $extensions = preg_replace('/([, ]+\.?)/', ' ', trim(strtolower($element['#value'])));
      $extension_array = array_unique(array_filter(explode(' ', $extensions)));
      $extensions = implode(' ', $extension_array);
      if (!preg_match('/^([a-z0-9]+([._][a-z0-9])* ?)+$/', $extensions)) {
        $form_state->setError($element, new TranslatableMarkup("The list of allowed extensions is not valid. Allowed characters are a-z, 0-9, '.', and '_'. The first and last characters cannot be '.' or '_', and these two characters cannot appear next to each other. Separate extensions with a comma or space."));
      }
      else {
        $form_state->setValueForElement($element, $extensions);
      }

      // If insecure uploads are not allowed and txt is not in the list of
      // allowed extensions, ensure that no insecure extensions are allowed.
      if (!in_array('txt', $extension_array, TRUE) && !\Drupal::config('system.file')->get('allow_insecure_uploads')) {
        foreach ($extension_array as $extension) {
          if (preg_match(FileSystemInterface::INSECURE_EXTENSION_REGEX, 'test.' . $extension)) {
            $form_state->setError($element, new TranslatableMarkup('Add %txt_extension to the list of allowed extensions to securely upload files with a %extension extension. The %txt_extension extension will then be added automatically.', ['%extension' => $extension, '%txt_extension' => 'txt']));

            break;
          }
        }
      }
    }
  }

  /**
   * Form API callback.
   *
   * Ensures that a size has been entered and that it can be parsed by
   * \Drupal\Component\Utility\Bytes::toNumber().
   *
   * This function is assigned as an #element_validate callback in
   * fieldSettingsForm().
   */
  public static function validateMaxFilesize($element, FormStateInterface $form_state) {
    $element['#value'] = trim($element['#value']);
    $form_state->setValue(['settings', 'max_filesize'], $element['#value']);
    if (!empty($element['#value']) && !Bytes::validate($element['#value'])) {
      $form_state->setError($element, new TranslatableMarkup('The "@name" option must contain a valid value. You may either leave the text field empty or enter a string like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes).', ['@name' => $element['#title']]));
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
    return $this->getFileUploadValidators($this->getSettings());
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
    /** @var \Drupal\file\FileRepositoryInterface $file_repository */
    $file_repository = \Drupal::service('file.repository');
    $file = $file_repository->writeData($data, $destination, FileExists::Error);
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
