<?php

namespace Drupal\media_library\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\File\Exception\FileWriteException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media_library\MediaLibraryUiBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a form to create media entities from uploaded files.
 *
 * @internal
 *   Media Library is an experimental module and its internal code may be
 *   subject to change in minor releases. External code should not instantiate
 *   or extend this class.
 */
class FileUploadForm extends AddFormBase {

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $renderer;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new FileUploadForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\media_library\MediaLibraryUiBuilder $library_ui_builder
   *   The media library UI builder.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MediaLibraryUiBuilder $library_ui_builder, ElementInfoManagerInterface $element_info, RendererInterface $renderer, FileSystemInterface $file_system) {
    parent::__construct($entity_type_manager, $library_ui_builder);
    $this->elementInfo = $element_info;
    $this->renderer = $renderer;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('media_library.ui_builder'),
      $container->get('element_info'),
      $container->get('renderer'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getMediaType(FormStateInterface $form_state) {
    if ($this->mediaType) {
      return $this->mediaType;
    }

    $media_type = parent::getMediaType($form_state);
    // The file upload form only supports media types which use a file field as
    // a source field.
    $field_definition = $media_type->getSource()->getSourceFieldDefinition($media_type);
    if (!is_a($field_definition->getClass(), FileFieldItemList::class, TRUE)) {
      throw new \InvalidArgumentException('Can only add media types which use a file field as a source field.');
    }
    return $media_type;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'media-library-add-form--upload';

    // Create a file item to get the upload validators.
    $media_type = $this->getMediaType($form_state);
    $item = $this->createFileItem($media_type);

    /** @var \Drupal\media_library\MediaLibraryState $state */
    $state = $this->getMediaLibraryState($form_state);
    if (!$state->hasSlotsAvailable()) {
      return $form;
    }

    $slots = $state->getAvailableSlots();

    // Add a container to group the input elements for styling purposes.
    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['media-library-add-form__input-wrapper'],
      ],
    ];

    $process = (array) $this->elementInfo->getInfoProperty('managed_file', '#process', []);
    $form['container']['upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->formatPlural($slots, 'Add file', 'Add files'),
      // @todo Move validation in https://www.drupal.org/node/2988215
      '#process' => array_merge(['::validateUploadElement'], $process, ['::processUploadElement']),
      '#upload_validators' => $item->getUploadValidators(),
      '#multiple' => $slots > 1 || $slots === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      '#cardinality' => $slots,
      '#remaining_slots' => $slots,
    ];

    $file_upload_help = [
      '#theme' => 'file_upload_help',
      '#upload_validators' => $form['container']['upload']['#upload_validators'],
      '#cardinality' => $slots,
    ];

    // The file upload help needs to be rendered since the description does not
    // accept render arrays. The FileWidget::formElement() method adds the file
    // upload help in the same way, so any theming improvements made to file
    // fields would also be applied to this upload field.
    // @see \Drupal\file\Plugin\Field\FieldWidget\FileWidget::formElement()
    $form['container']['upload']['#description'] = $this->renderer->renderPlain($file_upload_help);

    return $form;
  }

  /**
   * Validates the upload element.
   *
   * @param array $element
   *   The upload element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The processed upload element.
   */
  public function validateUploadElement(array $element, FormStateInterface $form_state) {
    if ($form_state::hasAnyErrors()) {
      // When an error occurs during uploading files, remove all files so the
      // user can re-upload the files.
      $element['#value'] = [];
    }
    $values = $form_state->getValue('upload', []);
    if (count($values['fids']) > $element['#cardinality'] && $element['#cardinality'] !== FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $form_state->setError($element, $this->t('A maximum of @count files can be uploaded.', [
        '@count' => $element['#cardinality'],
      ]));
      $form_state->setValue('upload', []);
      $element['#value'] = [];
    }
    return $element;
  }

  /**
   * Processes an upload (managed_file) element.
   *
   * @param array $element
   *   The upload element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The processed upload element.
   */
  public function processUploadElement(array $element, FormStateInterface $form_state) {
    $element['upload_button']['#submit'] = ['::uploadButtonSubmit'];
    // Limit the validation errors to make sure
    // FormValidator::handleErrorsWithLimitedValidation doesn't remove the
    // current selection from the form state.
    // @see Drupal\Core\Form\FormValidator::handleErrorsWithLimitedValidation()
    $element['upload_button']['#limit_validation_errors'] = [
      ['upload'],
      ['current_selection'],
    ];
    $element['upload_button']['#ajax'] = [
      'callback' => '::updateFormCallback',
      'wrapper' => 'media-library-wrapper',
      // Add a fixed URL to post the form since AJAX forms are automatically
      // posted to <current> instead of $form['#action'].
      // @todo Remove when https://www.drupal.org/project/drupal/issues/2504115
      //   is fixed.
      'url' => Url::fromRoute('media_library.ui'),
      'options' => [
        'query' => $this->getMediaLibraryState($form_state)->all() + [
          FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
        ],
      ],
    ];
    return $element;
  }

  /**
   * Submit handler for the upload button, inside the managed_file element.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function uploadButtonSubmit(array $form, FormStateInterface $form_state) {
    $files = $this->entityTypeManager
      ->getStorage('file')
      ->loadMultiple($form_state->getValue('upload', []));
    $this->processInputValues($files, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function createMediaFromValue(MediaTypeInterface $media_type, EntityStorageInterface $media_storage, $source_field_name, $file) {
    if (!($file instanceof FileInterface)) {
      throw new \InvalidArgumentException('Cannot create a media item without a file entity.');
    }

    // Create a file item to get the upload location.
    $item = $this->createFileItem($media_type);
    $upload_location = $item->getUploadLocation();
    if (!$this->fileSystem->prepareDirectory($upload_location, FileSystemInterface::CREATE_DIRECTORY)) {
      throw new FileWriteException("The destination directory '$upload_location' is not writable");
    }
    $file = file_move($file, $upload_location);
    if (!$file) {
      throw new \RuntimeException("Unable to move file to '$upload_location'");
    }

    return parent::createMediaFromValue($media_type, $media_storage, $source_field_name, $file);
  }

  /**
   * Create a file field item.
   *
   * @param \Drupal\media\MediaTypeInterface $media_type
   *   The media type of the media item.
   *
   * @return \Drupal\file\Plugin\Field\FieldType\FileItem
   *   A created file item.
   */
  protected function createFileItem(MediaTypeInterface $media_type) {
    $field_definition = $media_type->getSource()->getSourceFieldDefinition($media_type);
    $data_definition = FieldItemDataDefinition::create($field_definition);
    return new FileItem($data_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareMediaEntityForSave(MediaInterface $media) {
    /** @var \Drupal\file\FileInterface $file */
    $file = $media->get($this->getSourceFieldName($media->bundle->entity))->entity;
    $file->setPermanent();
    $file->save();
  }

}
