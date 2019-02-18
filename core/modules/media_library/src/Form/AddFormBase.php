<?php

namespace Drupal\media_library\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media_library\Ajax\UpdateSelectionCommand;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_library\MediaLibraryUiBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for creating media items from within the media library.
 *
 * @internal
 *   Media Library is an experimental module and its internal code may be
 *   subject to change in minor releases. External code should not instantiate
 *   or extend this class.
 */
abstract class AddFormBase extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The media library UI builder.
   *
   * @var \Drupal\media_library\MediaLibraryUiBuilder
   */
  protected $libraryUiBuilder;

  /**
   * The type of media items being created by this form.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $mediaType;

  /**
   * Constructs a AddFormBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\media_library\MediaLibraryUiBuilder $library_ui_builder
   *   The media library UI builder.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MediaLibraryUiBuilder $library_ui_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->libraryUiBuilder = $library_ui_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('media_library.ui_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_library_add_form';
  }

  /**
   * Get the media type from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\media\MediaTypeInterface
   *   The media type.
   */
  protected function getMediaType(FormStateInterface $form_state) {
    if ($this->mediaType) {
      return $this->mediaType;
    }

    $state = $form_state->get('media_library_state');

    if (!$state) {
      throw new \InvalidArgumentException('The media library state is not present in the form state.');
    }

    $selected_type_id = $form_state->get('media_library_state')->getSelectedTypeId();
    $this->mediaType = $this->entityTypeManager->getStorage('media_type')->load($selected_type_id);

    if (!$this->mediaType) {
      throw new \InvalidArgumentException("The '$selected_type_id' media type does not exist.");
    }

    return $this->mediaType;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="media-library-add-form-wrapper">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'media_library/style';

    // The form is posted via AJAX. When there are messages set during the
    // validation or submission of the form, the messages need to be shown to
    // the user.
    $form['status_messages'] = [
      '#type' => 'status_messages',
    ];

    $form['#attributes']['class'][] = 'media-library-add-form';
    $added_media = $form_state->get('media');
    if (empty($added_media)) {
      $form['#attributes']['class'][] = 'media-library-add-form--without-input';
      $form = $this->buildInputElement($form, $form_state);
    }
    else {
      $form['#attributes']['class'][] = 'media-library-add-form--with-input';

      $form['media'] = [
        '#type' => 'container',
      ];

      foreach ($added_media as $delta => $media) {
        $form['media'][$delta] = $this->buildEntityFormElement($media, $form, $form_state, $delta);
      }

      $form['actions'] = $this->buildActions($form, $form_state);
    }
    return $form;
  }

  /**
   * Builds the element for submitting source field value(s).
   *
   * The input element needs to have a submit handler to create media items from
   * the user input and store them in the form state using
   * ::processInputValues().
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The complete form, with the element added.
   *
   * @see ::processInputValues()
   */
  abstract protected function buildInputElement(array $form, FormStateInterface $form_state);

  /**
   * Builds the sub-form for setting required fields on a new media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A new, unsaved media item.
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param int $delta
   *   The delta of the media item.
   *
   * @return array
   *   The element containing the required fields sub-form.
   */
  protected function buildEntityFormElement(MediaInterface $media, array $form, FormStateInterface $form_state, $delta) {
    $element = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'media-library-add-form__media',
        ],
      ],
      'preview' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'media-library-add-form__preview',
          ],
        ],
      ],
      'fields' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'media-library-add-form__fields',
          ],
        ],
        // The '#parents' are set here because the entity form display needs it
        // to build the entity form fields.
        '#parents' => ['media', $delta, 'fields'],
      ],
    ];
    // @todo Make the image style configurable in
    //   https://www.drupal.org/node/2988223
    $source = $media->getSource();
    $plugin_definition = $source->getPluginDefinition();
    if ($thumbnail_uri = $source->getMetadata($media, $plugin_definition['thumbnail_uri_metadata_attribute'])) {
      $element['preview']['thumbnail'] = [
        '#theme' => 'image_style',
        '#style_name' => 'media_library',
        '#uri' => $thumbnail_uri,
      ];
    }

    $form_display = EntityFormDisplay::collectRenderDisplay($media, 'media_library');
    // When the name is not added to the form as an editable field, output
    // the name as a fixed element to confirm the right file was uploaded.
    if (!$form_display->getComponent('name')) {
      $element['fields']['name'] = [
        '#type' => 'item',
        '#title' => $this->t('Name'),
        '#markup' => $media->getName(),
      ];
    }
    $form_display->buildForm($media, $element['fields'], $form_state);

    // We hide the preview of the uploaded file in the image widget with CSS.
    // @todo Improve hiding file widget elements in
    //   https://www.drupal.org/project/drupal/issues/2987921
    $source_field_name = $this->getSourceFieldName($media->bundle->entity);
    if (isset($element['fields'][$source_field_name])) {
      $element['fields'][$source_field_name]['#attributes']['class'][] = 'media-library-add-form__source-field';
    }
    // The revision log field is currently not configurable from the form
    // display, so hide it by changing the access.
    // @todo Make the revision_log_message field configurable in
    //   https://www.drupal.org/project/drupal/issues/2696555
    if (isset($element['fields']['revision_log_message'])) {
      $element['fields']['revision_log_message']['#access'] = FALSE;
    }
    return $element;
  }

  /**
   * Returns an array of supported actions for the form.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   An actions element containing the actions of the form.
   */
  protected function buildActions(array $form, FormStateInterface $form_state) {
    return [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#ajax' => [
          'callback' => '::updateWidget',
          'wrapper' => 'media-library-add-form-wrapper',
        ],
      ],
    ];
  }

  /**
   * Creates media items from source field input values.
   *
   * @param mixed[] $source_field_values
   *   The values for source fields of the media items.
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  protected function processInputValues(array $source_field_values, array $form, FormStateInterface $form_state) {
    $media_type = $this->getMediaType($form_state);
    $media_storage = $this->entityTypeManager->getStorage('media');
    $source_field_name = $this->getSourceFieldName($media_type);
    $media = array_map(function ($source_field_value) use ($media_type, $media_storage, $source_field_name) {
      return $this->createMediaFromValue($media_type, $media_storage, $source_field_name, $source_field_value);
    }, $source_field_values);
    $form_state->set('media', $media)->setRebuild();
  }

  /**
   * Creates a new, unsaved media item from a source field value.
   *
   * @param \Drupal\media\MediaTypeInterface $media_type
   *   The media type of the media item.
   * @param \Drupal\Core\Entity\EntityStorageInterface $media_storage
   *   The media storage.
   * @param string $source_field_name
   *   The name of the media type's source field.
   * @param mixed $source_field_value
   *   The value for the source field of the media item.
   *
   * @return \Drupal\media\MediaInterface
   *   An unsaved media entity.
   */
  protected function createMediaFromValue(MediaTypeInterface $media_type, EntityStorageInterface $media_storage, $source_field_name, $source_field_value) {
    return $media_storage->create([
      'bundle' => $media_type->id(),
      $source_field_name => $source_field_value,
    ]);
  }

  /**
   * Prepares a created media item to be permanently saved.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The unsaved media item.
   */
  protected function prepareMediaEntityForSave(MediaInterface $media) {
    // Intentionally empty by default.
  }

  /**
   * AJAX callback to update the entire form based on source field input.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|array
   *   The form render array or an AJAX response object.
   */
  public function updateFormCallback(array &$form, FormStateInterface $form_state) {
    // When the source field input contains errors, replace the existing form to
    // let the user change the source field input. If the user input is valid,
    // the entire modal is replaced with the second step of the form to show the
    // form fields for each media item.
    if ($form_state::hasAnyErrors()) {
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('#media-library-add-form-wrapper', $form));
      return $response;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $added_media = $form_state->get('media') ?: [];
    foreach ($added_media as $delta => $media) {
      $this->validateMediaEntity($media, $form, $form_state, $delta);
    }
  }

  /**
   * Validate a created media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media item to validate.
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param int $delta
   *   The delta of the media item.
   */
  protected function validateMediaEntity(MediaInterface $media, array $form, FormStateInterface $form_state, $delta) {
    $form_display = EntityFormDisplay::collectRenderDisplay($media, 'media_library');
    $form_display->extractFormValues($media, $form['media'][$delta]['fields'], $form_state);
    $form_display->validateFormValues($media, $form['media'][$delta]['fields'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $added_media = $form_state->get('media') ?: [];
    foreach ($added_media as $delta => $media) {
      EntityFormDisplay::collectRenderDisplay($media, 'media_library')
        ->extractFormValues($media, $form['media'][$delta]['fields'], $form_state);
      $this->prepareMediaEntityForSave($media);
      $media->save();
    }
  }

  /**
   * AJAX callback to send the new media item(s) to the calling code.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   The form array when there are form errors or a AJAX response to select
   *   the created items in the media library.
   */
  public function updateWidget(array &$form, FormStateInterface $form_state) {
    if ($form_state::hasAnyErrors()) {
      return $form;
    }

    $added_media = $form_state->get('media') ?: [];
    $media_ids = array_map(function (MediaInterface $media) {
      return $media->id();
    }, $added_media);

    // Get the render array for the media library. The media library state might
    // contain the 'media_library_content' when it has been opened from a
    // vertical tab. We need to remove that to make sure the render array
    // contains the vertical tabs. Besides that, we also need to force the media
    // library to create a new instance of the media add form.
    // @see \Drupal\media_library\MediaLibraryUiBuilder::buildMediaTypeAddForm()
    $state = MediaLibraryState::fromRequest($this->getRequest());
    $state->remove('media_library_content');
    $state->set('_media_library_form_rebuild', TRUE);
    $library_ui = $this->libraryUiBuilder->buildUi($state);

    $response = new AjaxResponse();
    $response->addCommand(new UpdateSelectionCommand($media_ids));
    $response->addCommand(new ReplaceCommand('#media-library-add-form-wrapper', $library_ui));
    return $response;
  }

  /**
   * Returns the name of the source field for a media type.
   *
   * @param \Drupal\media\MediaTypeInterface $media_type
   *   The media type to get the source field name for.
   *
   * @return string
   *   The name of the media type's source field.
   */
  protected function getSourceFieldName(MediaTypeInterface $media_type) {
    return $media_type->getSource()
      ->getSourceFieldDefinition($media_type)
      ->getName();
  }

}
