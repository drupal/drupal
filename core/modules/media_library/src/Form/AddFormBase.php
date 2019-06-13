<?php

namespace Drupal\media_library\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media_library\Ajax\UpdateSelectionCommand;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\media_library\OpenerResolverInterface;
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
   * The media view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * The opener resolver.
   *
   * @var \Drupal\media_library\OpenerResolverInterface
   */
  protected $openerResolver;

  /**
   * Constructs a AddFormBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\media_library\MediaLibraryUiBuilder $library_ui_builder
   *   The media library UI builder.
   * @param \Drupal\media_library\OpenerResolverInterface $opener_resolver
   *   The opener resolver.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MediaLibraryUiBuilder $library_ui_builder, OpenerResolverInterface $opener_resolver = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->libraryUiBuilder = $library_ui_builder;
    $this->viewBuilder = $this->entityTypeManager->getViewBuilder('media');
    if (!$opener_resolver) {
      @trigger_error('The media_library.opener_resolver service must be passed to AddFormBase::__construct(), it is required before Drupal 9.0.0.', E_USER_DEPRECATED);
      $opener_resolver = \Drupal::service('media_library.opener_resolver');
    }
    $this->openerResolver = $opener_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('media_library.ui_builder'),
      $container->get('media_library.opener_resolver')
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
   *
   * @throws \InvalidArgumentException
   *   If the selected media type does not exist.
   */
  protected function getMediaType(FormStateInterface $form_state) {
    if ($this->mediaType) {
      return $this->mediaType;
    }

    $state = $this->getMediaLibraryState($form_state);
    $selected_type_id = $state->getSelectedTypeId();
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
    // @todo Remove the ID when we can use selectors to replace content via
    //   AJAX in https://www.drupal.org/project/drupal/issues/2821793.
    $form['#prefix'] = '<div id="media-library-add-form-wrapper" class="media-library-add-form-wrapper">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'media_library/style';

    // The media library is loaded via AJAX, which means that the form action
    // URL defaults to the current URL. However, to add media, we always need to
    // submit the form to the media library URL, not whatever the current URL
    // may be.
    $form['#action'] = Url::fromRoute('media_library.ui', [], [
      'query' => $this->getMediaLibraryState($form_state)->all(),
    ])->toString();

    // The form is posted via AJAX. When there are messages set during the
    // validation or submission of the form, the messages need to be shown to
    // the user.
    $form['status_messages'] = [
      '#type' => 'status_messages',
    ];

    $form['#attributes']['class'] = [
      'media-library-add-form',
      'js-media-library-add-form',
    ];

    $added_media = $this->getAddedMediaItems($form_state);
    if (empty($added_media)) {
      $form['#attributes']['class'][] = 'media-library-add-form--without-input';
      $form = $this->buildInputElement($form, $form_state);
    }
    else {
      $form['#attributes']['class'][] = 'media-library-add-form--with-input';

      $form['media'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'media-library-add-form__added-media',
          ],
          'aria-label' => $this->t('Added media items'),
          'role' => 'list',
          // Add the tabindex '-1' to allow the focus to be shifted to the added
          // media wrapper when items are added. We set focus to the container
          // because a media item does not necessarily have required fields and
          // we do not want to set focus to the remove button automatically.
          // @see ::updateFormCallback()
          'tabindex' => '-1',
        ],
      ];

      $form['media']['description'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->formatPlural(count($added_media), 'The media item has been created but has not yet been saved. Fill in any required fields and save to add it to the media library.', 'The media items have been created but have not yet been saved. Fill in any required fields and save to add them to the media library.'),
        '#attributes' => [
          'class' => [
            'media-library-add-form__description',
          ],
        ],
      ];

      foreach ($added_media as $delta => $media) {
        $form['media'][$delta] = $this->buildEntityFormElement($media, $form, $form_state, $delta);
      }

      $form['selection'] = $this->buildCurrentSelectionArea($form, $form_state);
      $form['actions'] = $this->buildActions($form, $form_state);
    }

    // Allow the current selection to be set in a hidden field so the selection
    // can be passed between different states of the form. This field is filled
    // via JavaScript so the default value should be empty.
    // @see Drupal.behaviors.MediaLibraryItemSelection
    $form['current_selection'] = [
      '#type' => 'hidden',
      '#default_value' => '',
      '#attributes' => [
        'class' => [
          'js-media-library-add-form-current-selection',
        ],
      ],
    ];

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
    // We need to make sure each button has a unique name attribute. The default
    // name for button elements is 'op'. If the name is not unique, the
    // triggering element is not set correctly and the wrong media item is
    // removed.
    // @see ::removeButtonSubmit()
    $parents = isset($form['#parents']) ? $form['#parents'] : [];
    $id_suffix = $parents ? '-' . implode('-', $parents) : '';

    $element = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'media-library-add-form__media',
        ],
        'aria-label' => $media->getName(),
        'role' => 'listitem',
        // Add the tabindex '-1' to allow the focus to be shifted to the next
        // media item when an item is removed. We set focus to the container
        // because a media item does not necessarily have required fields and we
        // do not want to set focus to the remove button automatically.
        // @see ::updateFormCallback()
        'tabindex' => '-1',
        // Add a data attribute containing the delta to allow us to easily shift
        // the focus to a specific media item.
        // @see ::updateFormCallback()
        'data-media-library-added-delta' => $delta,
      ],
      'preview' => [
        '#type' => 'container',
        '#weight' => 10,
        '#attributes' => [
          'class' => [
            'media-library-add-form__preview',
          ],
        ],
      ],
      'fields' => [
        '#type' => 'container',
        '#weight' => 20,
        '#attributes' => [
          'class' => [
            'media-library-add-form__fields',
          ],
        ],
        // The '#parents' are set here because the entity form display needs it
        // to build the entity form fields.
        '#parents' => ['media', $delta, 'fields'],
      ],
      'remove_button' => [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'media-' . $delta . '-remove-button' . $id_suffix,
        '#weight' => 30,
        '#attributes' => [
          'class' => ['media-library-add-form__remove-button'],
          'aria-label' => $this->t('Remove @label', ['@label' => $media->getName()]),
        ],
        '#ajax' => [
          'callback' => '::updateFormCallback',
          'wrapper' => 'media-library-add-form-wrapper',
          'message' => $this->t('Removing @label.', ['@label' => $media->getName()]),
        ],
        '#submit' => ['::removeButtonSubmit'],
        // Ensure errors in other media items do not prevent removal.
        '#limit_validation_errors' => [],
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
   * Returns a render array containing the current selection.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   A render array containing the current selection.
   */
  protected function buildCurrentSelectionArea(array $form, FormStateInterface $form_state) {
    $pre_selected_items = $this->getPreSelectedMediaItems($form_state);

    if (!$pre_selected_items) {
      return [];
    }

    $selection = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Additional selected media'),
      '#attributes' => [
        'class' => [
          'media-library-add-form__selected-media',
        ],
      ],
    ];
    foreach ($pre_selected_items as $media_id => $media) {
      $selection[$media_id] = $this->buildSelectedItemElement($media, $form, $form_state);
    }

    return $selection;
  }

  /**
   * Returns a render array for a single pre-selected media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media item.
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   A render array of a pre-selected media item.
   */
  protected function buildSelectedItemElement(MediaInterface $media, array $form, FormStateInterface $form_state) {
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'media-library-item',
          'media-library-item--grid',
          'media-library-item--small',
          'js-media-library-item',
          'js-click-to-select',
        ],
      ],
      'select' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'js-click-to-select-checkbox',
          ],
        ],
        'select_checkbox' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Select @name', ['@name' => $media->label()]),
          '#title_display' => 'invisible',
          '#return_value' => $media->id(),
          // The checkbox's value is never processed by this form. It is present
          // for usability and accessibility reasons, and only used by
          // JavaScript to track whether or not this media item is selected. The
          // hidden 'current_selection' field is used to store the actual IDs of
          // selected media items.
          '#value' => FALSE,
        ],
      ],
      'rendered_entity' => $this->viewBuilder->view($media, 'media_library'),
    ];
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
      'save_select' => [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->t('Save and select'),
        '#ajax' => [
          'callback' => '::updateLibrary',
          'wrapper' => 'media-library-add-form-wrapper',
        ],
      ],
      'save_insert' => [
        '#type' => 'submit',
        '#value' => $this->t('Save and insert'),
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
    // Re-key the media items before setting them in the form state.
    $form_state->set('media', array_values($media));
    // Save the selected items in the form state so they are remembered when an
    // item is removed.
    $form_state->set('current_selection', array_filter(explode(',', $form_state->getValue('current_selection'))));
    $form_state->setRebuild();
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
    $media = $media_storage->create([
      'bundle' => $media_type->id(),
      $source_field_name => $source_field_value,
    ]);
    $media->setName($media->getName());
    return $media;
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
   * Submit handler for the remove button.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function removeButtonSubmit(array $form, FormStateInterface $form_state) {
    // Retrieve the delta of the media item from the parents of the remove
    // button.
    $triggering_element = $form_state->getTriggeringElement();
    $delta = array_slice($triggering_element['#array_parents'], -2, 1)[0];

    $added_media = $form_state->get('media');
    $removed_media = $added_media[$delta];

    // Update the list of added media items in the form state.
    unset($added_media[$delta]);

    // Update the media items in the form state.
    $form_state->set('media', $added_media)->setRebuild();

    // Show a message to the user to confirm the media is removed.
    $this->messenger()->addStatus($this->t('The media item %label has been removed.', ['%label' => $removed_media->label()]));
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
    $triggering_element = $form_state->getTriggeringElement();
    $wrapper_id = $triggering_element['#ajax']['wrapper'];
    $added_media = $form_state->get('media');

    $response = new AjaxResponse();

    // When the source field input contains errors, replace the existing form to
    // let the user change the source field input. If the user input is valid,
    // the entire modal is replaced with the second step of the form to show the
    // form fields for each media item.
    if ($form_state::hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#media-library-add-form-wrapper', $form));
      return $response;
    }

    // Check if the remove button is clicked.
    if (end($triggering_element['#parents']) === 'remove_button') {
      // When the list of added media is empty, return to the media library and
      // shift focus back to the first tabbable element (which should be the
      // source field).
      if (empty($added_media)) {
        $response->addCommand(new ReplaceCommand('#media-library-add-form-wrapper', $this->buildMediaLibraryUi($form_state)));
        $response->addCommand(new InvokeCommand('#media-library-add-form-wrapper :tabbable', 'focus'));
      }
      // When there are still more items, update the form and shift the focus to
      // the next media item. If the last list item is removed, shift focus to
      // the previous item.
      else {
        $response->addCommand(new ReplaceCommand("#$wrapper_id", $form));

        // Find the delta of the next media item. If there is no item with a
        // bigger delta, we automatically use the delta of the previous item and
        // shift the focus there.
        $removed_delta = array_slice($triggering_element['#array_parents'], -2, 1)[0];
        $delta_to_focus = 0;
        foreach ($added_media as $delta => $media) {
          $delta_to_focus = $delta;
          if ($delta > $removed_delta) {
            break;
          }
        }
        $response->addCommand(new InvokeCommand(".media-library-add-form__media[data-media-library-added-delta=$delta_to_focus]", 'focus'));
      }
    }
    // Update the form and shift focus to the added media items.
    else {
      $response->addCommand(new ReplaceCommand("#$wrapper_id", $form));
      $response->addCommand(new InvokeCommand('.media-library-add-form__added-media', 'focus'));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getAddedMediaItems($form_state) as $delta => $media) {
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
    foreach ($this->getAddedMediaItems($form_state) as $delta => $media) {
      EntityFormDisplay::collectRenderDisplay($media, 'media_library')
        ->extractFormValues($media, $form['media'][$delta]['fields'], $form_state);
      $this->prepareMediaEntityForSave($media);
      $media->save();
    }
  }

  /**
   * AJAX callback to send the new media item(s) to the media library.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   The form array if there are validation errors, or an AJAX response to add
   *   the created items to the current selection.
   */
  public function updateLibrary(array &$form, FormStateInterface $form_state) {
    if ($form_state::hasAnyErrors()) {
      return $form;
    }

    $media_ids = array_map(function (MediaInterface $media) {
      return $media->id();
    }, $this->getAddedMediaItems($form_state));

    $response = new AjaxResponse();
    $response->addCommand(new UpdateSelectionCommand($media_ids));
    $response->addCommand(new ReplaceCommand('#media-library-add-form-wrapper', $this->buildMediaLibraryUi($form_state)));
    return $response;
  }

  /**
   * Build the render array of the media library UI.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The render array for the media library.
   */
  protected function buildMediaLibraryUi(FormStateInterface $form_state) {
    // Get the render array for the media library. The media library state might
    // contain the 'media_library_content' when it has been opened from a
    // vertical tab. We need to remove that to make sure the render array
    // contains the vertical tabs. Besides that, we also need to force the media
    // library to create a new instance of the media add form.
    // @see \Drupal\media_library\MediaLibraryUiBuilder::buildMediaTypeAddForm()
    $state = $this->getMediaLibraryState($form_state);
    $state->remove('media_library_content');
    $state->set('_media_library_form_rebuild', TRUE);
    return $this->libraryUiBuilder->buildUi($state);
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

    // The added media items get an ID when they are saved in ::submitForm().
    // For that reason the added media items are keyed by delta in the form
    // state and we have to do an array map to get each media ID.
    $current_media_ids = array_map(function (MediaInterface $media) {
      return $media->id();
    }, $this->getCurrentMediaItems($form_state));

    // Allow the opener service to respond to the selection.
    $state = $this->getMediaLibraryState($form_state);
    return $this->openerResolver->get($state)
      ->getSelectionResponse($state, $current_media_ids)
      ->addCommand(new CloseDialogCommand());
  }

  /**
   * Get the media library state from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\media_library\MediaLibraryState
   *   The media library state.
   *
   * @throws \InvalidArgumentException
   *   If the media library state is not present in the form state.
   */
  protected function getMediaLibraryState(FormStateInterface $form_state) {
    $state = $form_state->get('media_library_state');
    if (!$state) {
      throw new \InvalidArgumentException('The media library state is not present in the form state.');
    }
    return $state;
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

  /**
   * Get all pre-selected media items from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\media\MediaInterface[]
   *   An array containing the pre-selected media items keyed by ID.
   */
  protected function getPreSelectedMediaItems(FormStateInterface $form_state) {
    // Get the current selection from the form state.
    // @see ::processInputValues()
    $media_ids = $form_state->get('current_selection');
    if (!$media_ids) {
      return [];
    }
    return $this->entityTypeManager->getStorage('media')->loadMultiple($media_ids);
  }

  /**
   * Get all added media items from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\media\MediaInterface[]
   *   An array containing the added media items keyed by delta. The media items
   *   won't have an ID untill they are saved in ::submitForm().
   */
  protected function getAddedMediaItems(FormStateInterface $form_state) {
    return $form_state->get('media') ?: [];
  }

  /**
   * Get all pre-selected and added media items from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\media\MediaInterface[]
   *   An array containing all pre-selected and added media items with
   *   renumbered numeric keys.
   */
  protected function getCurrentMediaItems(FormStateInterface $form_state) {
    $pre_selected_media = $this->getPreSelectedMediaItems($form_state);
    $added_media = $this->getAddedMediaItems($form_state);
    // Using array_merge will renumber the numeric keys.
    return array_merge($pre_selected_media, $added_media);
  }

}
