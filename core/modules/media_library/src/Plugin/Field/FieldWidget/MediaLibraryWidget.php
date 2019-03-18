<?php

namespace Drupal\media_library\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\Entity\Media;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\media_library\MediaLibraryState;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'media_library_widget' widget.
 *
 * @FieldWidget(
 *   id = "media_library_widget",
 *   label = @Translation("Media library"),
 *   description = @Translation("Allows you to select items from the media library."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE,
 * )
 *
 * @internal
 *   Media Library is an experimental module and its internal code may be
 *   subject to change in minor releases. External code should not instantiate
 *   or extend this class.
 */
class MediaLibraryWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The prefix to use with a field ID for media library opener IDs.
   *
   * @var string
   */
  protected static $openerIdPrefix = 'field:';

  /**
   * Constructs a MediaLibraryWidget widget.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getSetting('target_type') === 'media';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'media_types' => [],
    ] + parent::defaultSettings();
  }

  /**
   * Get the enabled media type IDs sorted by weight.
   *
   * @return string[]
   *   The media type IDs sorted by weight.
   */
  protected function getAllowedMediaTypeIdsSorted() {
    // Get the media type IDs sorted by the user in the settings form.
    $sorted_media_type_ids = $this->getSetting('media_types');

    // Get the configured media types from the field storage.
    $handler_settings = $this->getFieldSetting('handler_settings');
    $allowed_media_type_ids = !empty($handler_settings['target_bundles']) ? $handler_settings['target_bundles'] : [];

    // When no target bundles are configured for the field, all are allowed.
    if (!$allowed_media_type_ids) {
      $allowed_media_type_ids = $this->entityTypeManager->getStorage('media_type')->getQuery()->execute();
    }

    // When the user did not sort the media types, return the media type IDs
    // configured for the field.
    if (empty($sorted_media_type_ids)) {
      return $allowed_media_type_ids;
    }

    // Some of the media types may no longer exist, and new media types may have
    // been added that we don't yet know about. We need to make sure new media
    // types are added to the list and remove media types that are no longer
    // configured for the field.
    $new_media_type_ids = array_diff($allowed_media_type_ids, $sorted_media_type_ids);
    // Add new media type IDs to the list.
    $sorted_media_type_ids = array_merge($sorted_media_type_ids, array_values($new_media_type_ids));
    // Remove media types that are no longer available.
    $sorted_media_type_ids = array_intersect($sorted_media_type_ids, $allowed_media_type_ids);

    // Make sure the keys are numeric.
    return array_values($sorted_media_type_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $media_type_ids = $this->getAllowedMediaTypeIdsSorted();

    if (count($media_type_ids) <= 1) {
      return $form;
    }

    $form['media_types'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Tab order'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ],
      ],
      '#value_callback' => [static::class, 'setMediaTypesValue'],
    ];

    $media_types = $this->entityTypeManager->getStorage('media_type')->loadMultiple($media_type_ids);
    $weight = 0;
    foreach ($media_types as $media_type_id => $media_type) {
      $label = $media_type->label();
      $form['media_types'][$media_type_id] = [
        'label' => ['#markup' => $label],
        'weight' => [
          '#type' => 'weight',
          '#title' => t('Weight for @title', ['@title' => $label]),
          '#title_display' => 'invisible',
          '#default_value' => $weight,
          '#attributes' => ['class' => ['weight']],
        ],
        '#weight' => $weight,
        '#attributes' => ['class' => ['draggable']],
      ];
      $weight++;
    }

    return $form;
  }

  /**
   * Value callback to optimize the way the media type weights are stored.
   *
   * The tabledrag functionality needs a specific weight field, but we don't
   * want to store this extra weight field in our settings.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param mixed $input
   *   The incoming input to populate the form element. If this is FALSE,
   *   the element's default value should be returned.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return mixed
   *   The value to assign to the element.
   */
  public static function setMediaTypesValue(array &$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      return isset($element['#default_value']) ? $element['#default_value'] : [];
    }

    // Sort the media types by weight value and set the value in the form state.
    uasort($input, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    $sorted_media_type_ids = array_keys($input);
    $form_state->setValue($element['#parents'], $sorted_media_type_ids);

    // We have to unset the child elements containing the weight fields for each
    // media type to stop FormBuilder::doBuildForm() from processing the weight
    // fields as well.
    foreach ($sorted_media_type_ids as $media_type_id) {
      unset($element[$media_type_id]);
    }

    return $sorted_media_type_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $media_type_labels = [];
    $media_types = $this->entityTypeManager->getStorage('media_type')->loadMultiple($this->getAllowedMediaTypeIdsSorted());
    if (count($media_types) !== 1) {
      foreach ($media_types as $media_type) {
        $media_type_labels[] = $media_type->label();
      }
      $summary[] = t('Tab order: @order', ['@order' => implode(', ', $media_type_labels)]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    // Load the items for form rebuilds from the field state.
    $field_state = static::getWidgetState($form['#parents'], $this->fieldDefinition->getName(), $form_state);
    if (isset($field_state['items'])) {
      usort($field_state['items'], [SortArray::class, 'sortByWeightElement']);
      $items->setValue($field_state['items']);
    }

    return parent::form($items, $form, $form_state, $get_delta);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items */
    $referenced_entities = $items->referencedEntities();
    $view_builder = $this->entityTypeManager->getViewBuilder('media');
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];
    $id_suffix = $parents ? '-' . implode('-', $parents) : '';
    $wrapper_id = $field_name . '-media-library-wrapper' . $id_suffix;
    $limit_validation_errors = [array_merge($parents, [$field_name])];

    $settings = $this->getFieldSetting('handler_settings');
    $element += [
      '#type' => 'fieldset',
      '#cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
      '#target_bundles' => isset($settings['target_bundles']) ? $settings['target_bundles'] : FALSE,
      '#attributes' => [
        'id' => $wrapper_id,
        'class' => ['media-library-widget'],
      ],
      '#attached' => [
        'library' => ['media_library/widget'],
      ],
    ];

    if (empty($referenced_entities)) {
      $element['empty_selection'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('No media items are selected.'),
        '#attributes' => [
          'class' => [
            'media-library-widget-empty-text',
          ],
        ],
      ];
    }
    else {
      $element['weight_toggle'] = [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->t('Show media item weights'),
        '#attributes' => [
          'class' => [
            'link',
            'media-library-widget__toggle-weight',
            'js-media-library-widget-toggle-weight',
          ],
        ],
      ];
    }

    $element['selection'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'js-media-library-selection',
          'media-library-selection',
        ],
      ],
    ];

    foreach ($referenced_entities as $delta => $media_item) {
      $element['selection'][$delta] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'media-library-item',
            'media-library-item--grid',
            'js-media-library-item',
          ],
        ],
        'preview' => [
          '#type' => 'container',
          'remove_button' => [
            '#type' => 'submit',
            '#name' => $field_name . '-' . $delta . '-media-library-remove-button' . $id_suffix,
            '#value' => $this->t('Remove'),
            '#attributes' => [
              'class' => ['media-library-item__remove'],
              'aria-label' => $this->t('Remove @label', ['@label' => $media_item->label()]),
            ],
            '#ajax' => [
              'callback' => [static::class, 'updateWidget'],
              'wrapper' => $wrapper_id,
            ],
            '#submit' => [[static::class, 'removeItem']],
            // Prevent errors in other widgets from preventing removal.
            '#limit_validation_errors' => $limit_validation_errors,
          ],
          // @todo Make the view mode configurable in https://www.drupal.org/project/drupal/issues/2971209
          'rendered_entity' => $view_builder->view($media_item, 'media_library'),
        ],
        'target_id' => [
          '#type' => 'hidden',
          '#value' => $media_item->id(),
        ],
        // This hidden value can be toggled visible for accessibility.
        'weight' => [
          '#type' => 'number',
          '#title' => $this->t('Weight'),
          '#default_value' => $delta,
          '#attributes' => [
            'class' => [
              'js-media-library-item-weight',
              'media-library-item__weight',
            ],
          ],
        ],
      ];
    }

    $cardinality_unlimited = ($element['#cardinality'] === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $remaining = $element['#cardinality'] - count($referenced_entities);

    // Inform the user of how many items are remaining.
    if (!$cardinality_unlimited) {
      if ($remaining) {
        $cardinality_message = $this->formatPlural($remaining, 'One media item remaining.', '@count media items remaining.');
      }
      else {
        $cardinality_message = $this->t('The maximum number of media items have been selected.');
      }

      // Add a line break between the field message and the cardinality message.
      if (!empty($element['#description'])) {
        $element['#description'] .= '<br />';
      }
      $element['#description'] .= $cardinality_message;
    }

    // Create a new media library URL with the correct state parameters.
    $allowed_media_type_ids = $this->getAllowedMediaTypeIdsSorted();
    $selected_type_id = reset($allowed_media_type_ids);
    $remaining = $cardinality_unlimited ? FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED : $remaining;
    // The opener ID is used by the select form and the upload form to add the
    // selected/uploaded media items to the widget.
    $opener_id = static::$openerIdPrefix . $field_name . $id_suffix;

    $state = MediaLibraryState::create($opener_id, $allowed_media_type_ids, $selected_type_id, $remaining);

    // Add a button that will load the Media library in a modal using AJAX.
    $element['media_library_open_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add media'),
      '#name' => $field_name . '-media-library-open-button' . $id_suffix,
      '#attributes' => [
        'class' => [
          'media-library-open-button',
          'js-media-library-open-button',
        ],
        // The jQuery UI dialog automatically moves focus to the first :tabbable
        // element of the modal, so we need to disable refocus on the button.
        'data-disable-refocus' => 'true',
      ],
      '#media_library_state' => $state,
      '#ajax' => [
        'callback' => [static::class, 'openMediaLibrary'],
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Opening media library.'),
        ],
      ],
      '#submit' => [],
      // Allow the media library to be opened even if there are form errors.
      '#limit_validation_errors' => [],
    ];

    // When the user returns from the modal to the widget, we want to shift the
    // focus back to the open button. If the user is not allowed to add more
    // items, the button needs to be disabled. Since we can't shift the focus to
    // disabled elements, the focus is set back to the open button via
    // JavaScript by adding the 'data-disabled-focus' attribute.
    // @see Drupal.behaviors.MediaLibraryWidgetDisableButton
    if (!$cardinality_unlimited && $remaining === 0) {
      $element['media_library_open_button']['#attributes']['data-disabled-focus'] = 'true';
      $element['media_library_open_button']['#attributes']['class'][] = 'visually-hidden';
    }

    // This hidden field and button are used to add new items to the widget.
    $element['media_library_selection'] = [
      '#type' => 'hidden',
      '#attributes' => [
        // This is used to pass the selection from the modal to the widget.
        'data-media-library-widget-value' => $field_name . $id_suffix,
      ],
    ];

    // When a selection is made this hidden button is pressed to add new media
    // items based on the "media_library_selection" value.
    $element['media_library_update_widget'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update widget'),
      '#name' => $field_name . '-media-library-update' . $id_suffix,
      '#ajax' => [
        'callback' => [static::class, 'updateWidget'],
        'wrapper' => $wrapper_id,
      ],
      '#attributes' => [
        'data-media-library-widget-update' => $field_name . $id_suffix,
        'class' => ['js-hide'],
      ],
      '#validate' => [[static::class, 'validateItems']],
      '#submit' => [[static::class, 'updateItems']],
      // Prevent errors in other widgets from preventing updates.
      '#limit_validation_errors' => $limit_validation_errors,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return isset($element['target_id']) ? $element['target_id'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    if (isset($values['selection'])) {
      usort($values['selection'], [SortArray::class, 'sortByWeightElement']);
      return $values['selection'];
    }
    return [];
  }

  /**
   * AJAX callback to update the widget when the selection changes.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response to update the selection.
   */
  public static function updateWidget(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $wrapper_id = $triggering_element['#ajax']['wrapper'];

    // This callback is either invoked from the remove button or the update
    // button, which have different nesting levels.
    $remove_button = end($triggering_element['#parents']) === 'remove_button';
    $length = $remove_button ? -4 : -1;
    if (count($triggering_element['#array_parents']) < abs($length)) {
      throw new \LogicException('The element that triggered the widget update was at an unexpected depth. Triggering element parents were: ' . implode(',', $triggering_element['#array_parents']));
    }
    $parents = array_slice($triggering_element['#array_parents'], 0, $length);
    $element = NestedArray::getValue($form, $parents);
    // Always clear the textfield selection to prevent duplicate additions.
    $element['media_library_selection']['#value'] = '';

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand("#$wrapper_id", $element));

    $field_state = static::getFieldState($element, $form_state);

    // When the remove button is clicked, the focus will be kept in the
    // selection area by default. When the last item is deleted, we no longer
    // have a selection and shift the focus to the open button.
    $removed_last = $remove_button && !count($field_state['items']);

    // Shift focus to the open button if the user did not click the remove
    // button. When the user is not allowed to add more items, the button needs
    // to be disabled. Since we can't shift the focus to disabled elements, the
    // focus is set via JavaScript by adding the 'data-disabled-focus' attribute
    // and we also don't want to set the focus here.
    // @see Drupal.behaviors.MediaLibraryWidgetDisableButton
    $select_more = !$remove_button && !isset($element['media_library_open_button']['#attributes']['data-disabled-focus']);

    if ($removed_last || $select_more) {
      $response->addCommand(new InvokeCommand("#$wrapper_id .js-media-library-open-button", 'focus'));
    }

    return $response;
  }

  /**
   * Submit callback for remove buttons.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function removeItem(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    // Get the parents required to find the top-level widget element.
    if (count($triggering_element['#array_parents']) < 4) {
      throw new \LogicException('Expected the remove button to be more than four levels deep in the form. Triggering element parents were: ' . implode(',', $triggering_element['#array_parents']));
    }
    $parents = array_slice($triggering_element['#array_parents'], 0, -4);
    // Get the delta of the item being removed.
    $delta = array_slice($triggering_element['#array_parents'], -3, 1)[0];
    $element = NestedArray::getValue($form, $parents);

    // Get the field state.
    $path = $element['#parents'];
    $values = NestedArray::getValue($form_state->getValues(), $path);
    $field_state = static::getFieldState($element, $form_state);

    // Remove the item from the field state and update it.
    if (isset($values['selection'][$delta])) {
      array_splice($values['selection'], $delta, 1);
      $field_state['items'] = $values['selection'];
      static::setFieldState($element, $form_state, $field_state);
    }

    $form_state->setRebuild();
  }

  /**
   * AJAX callback to open the library modal.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response to open the media library.
   */
  public static function openMediaLibrary(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $library_ui = \Drupal::service('media_library.ui_builder')->buildUi($triggering_element['#media_library_state']);
    $dialog_options = MediaLibraryUiBuilder::dialogOptions();
    return (new AjaxResponse())
      ->addCommand(new OpenModalDialogCommand($dialog_options['title'], $library_ui, $dialog_options));
  }

  /**
   * Validates that newly selected items can be added to the widget.
   *
   * Making an invalid selection from the view should not be possible, but we
   * still validate in case other selection methods (ex: upload) are valid.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateItems(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    $field_state = static::getFieldState($element, $form_state);
    $media = static::getNewMediaItems($element, $form_state);
    if (empty($media)) {
      return;
    }

    // Check if more items were selected than we allow.
    $cardinality_unlimited = ($element['#cardinality'] === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $selection = count($field_state['items']) + count($media);
    if (!$cardinality_unlimited && ($selection > $element['#cardinality'])) {
      $form_state->setError($element, \Drupal::translation()->formatPlural($element['#cardinality'], 'Only one item can be selected.', 'Only @count items can be selected.'));
    }

    // Validate that each selected media is of an allowed bundle.
    $all_bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('media');
    $bundle_labels = array_map(function ($bundle) use ($all_bundles) {
      return $all_bundles[$bundle]['label'];
    }, $element['#target_bundles']);
    foreach ($media as $media_item) {
      if ($element['#target_bundles'] && !in_array($media_item->bundle(), $element['#target_bundles'], TRUE)) {
        $form_state->setError($element, t('The media item "@label" is not of an accepted type. Allowed types: @types', [
          '@label' => $media_item->label(),
          '@types' => implode(', ', $bundle_labels),
        ]));
      }
    }
  }

  /**
   * Updates the field state and flags the form for rebuild.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function updateItems(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    $field_state = static::getFieldState($element, $form_state);

    $media = static::getNewMediaItems($element, $form_state);
    if (!empty($media)) {
      // Get the weight of the last items and count from there.
      $last_element = end($field_state['items']);
      $weight = $last_element ? $last_element['weight'] : 0;
      foreach ($media as $media_item) {
        // Any ID can be passed to the widget, so we have to check access.
        if ($media_item->access('view')) {
          $field_state['items'][] = [
            'target_id' => $media_item->id(),
            'weight' => ++$weight,
          ];
        }
      }
      static::setFieldState($element, $form_state, $field_state);
    }

    $form_state->setRebuild();
  }

  /**
   * Gets newly selected media items.
   *
   * @param array $element
   *   The wrapping element for this widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\media\MediaInterface[]
   *   An array of selected media items.
   */
  protected static function getNewMediaItems(array $element, FormStateInterface $form_state) {
    // Get the new media IDs passed to our hidden button.
    $values = $form_state->getValues();
    $path = $element['#parents'];
    $value = NestedArray::getValue($values, $path);

    if (!empty($value['media_library_selection'])) {
      $ids = explode(',', $value['media_library_selection']);
      $ids = array_filter($ids, 'is_numeric');
      if (!empty($ids)) {
        /** @var \Drupal\media\MediaInterface[] $media */
        return Media::loadMultiple($ids);
      }
    }
    return [];
  }

  /**
   * Gets the field state for the widget.
   *
   * @param array $element
   *   The wrapping element for this widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array[]
   *   An array of arrays with the following key/value pairs:
   *   - items: (array) An array of selections.
   *     - target_id: (int) A media entity ID.
   *     - weight: (int) A weight for the selection.
   */
  protected static function getFieldState(array $element, FormStateInterface $form_state) {
    // Default to using the current selection if the form is new.
    $path = $element['#parents'];
    $values = NestedArray::getValue($form_state->getValues(), $path);
    $selection = isset($values['selection']) ? $values['selection'] : [];

    $widget_state = static::getWidgetState($element['#field_parents'], $element['#field_name'], $form_state);
    $widget_state['items'] = isset($widget_state['items']) ? $widget_state['items'] : $selection;
    return $widget_state;
  }

  /**
   * Sets the field state for the widget.
   *
   * @param array $element
   *   The wrapping element for this widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array[] $field_state
   *   An array of arrays with the following key/value pairs:
   *   - items: (array) An array of selections.
   *     - target_id: (int) A media entity ID.
   *     - weight: (int) A weight for the selection.
   */
  protected static function setFieldState(array $element, FormStateInterface $form_state, array $field_state) {
    static::setWidgetState($element['#field_parents'], $element['#field_name'], $form_state, $field_state);
  }

  /**
   * Get the field ID of the widget from an opener ID.
   *
   * @param string $opener_id
   *   The opener ID of the media library.
   *
   * @return string|null
   *   The field ID or NULL if the opener ID is not valid for the widget.
   *
   * @see \Drupal\media_library\MediaLibraryState
   */
  public static function getOpenerFieldId($opener_id) {
    // Media library widget opener IDs are always prefixed with 'field:' in .
    if (preg_match('/^' . static::$openerIdPrefix . '([a-z0-9_-]+)$/', $opener_id, $matches)) {
      return $matches[1];
    }
    return NULL;
  }

}
