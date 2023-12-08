<?php

namespace Drupal\media_library\Plugin\Field\FieldWidget;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AnnounceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;
use Drupal\media\Entity\Media;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_library\MediaLibraryUiBuilder;
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
 *   Plugin classes are internal.
 */
class MediaLibraryWidget extends WidgetBase implements TrustedCallbackInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current active user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, ModuleHandlerInterface $module_handler) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
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
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('module_handler')
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
   * Gets the enabled media type IDs sorted by weight.
   *
   * @return string[]
   *   The media type IDs sorted by weight.
   */
  protected function getAllowedMediaTypeIdsSorted() {
    // Get the media type IDs sorted by the user in the settings form.
    $sorted_media_type_ids = $this->getSetting('media_types');

    // Get the configured media types from the field storage.
    $handler_settings = $this->getFieldSetting('handler_settings');
    // The target bundles will be blank when saving field storage settings,
    // when first adding a media reference field.
    $allowed_media_type_ids = $handler_settings['target_bundles'] ?? NULL;

    // When there are no allowed media types, return the empty array.
    if ($allowed_media_type_ids === []) {
      return $allowed_media_type_ids;
    }

    // When no target bundles are configured for the field, all are allowed.
    if ($allowed_media_type_ids === NULL) {
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
    $elements = [];
    $media_type_ids = $this->getAllowedMediaTypeIdsSorted();

    if (count($media_type_ids) <= 1) {
      return $elements;
    }

    $elements['media_types'] = [
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
      $elements['media_types'][$media_type_id] = [
        'label' => ['#markup' => $label],
        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => $label]),
          '#title_display' => 'invisible',
          '#default_value' => $weight,
          '#attributes' => ['class' => ['weight']],
        ],
        '#weight' => $weight,
        '#attributes' => ['class' => ['draggable']],
      ];
      $weight++;
    }

    return $elements;
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
      return $element['#default_value'] ?? [];
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
      $summary[] = $this->t('Tab order: @order', ['@order' => implode(', ', $media_type_labels)]);
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
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    parent::extractFormValues($items, $form, $form_state);

    // Update reference to 'items' stored during add or remove to take into
    // account changes to values like 'weight' etc.
    // @see Drupal\media_library\Plugin\Field\FieldWidget\MediaLibraryWidget::addItems
    // @see Drupal\media_library\Plugin\Field\FieldWidget\MediaLibraryWidget::removeItem
    $field_name = $this->fieldDefinition->getName();
    $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
    $field_state['items'] = $items->getValue();
    static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
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
    // Create an ID suffix from the parents to make sure each widget is unique.
    $id_suffix = $parents ? '-' . implode('-', $parents) : '';
    $field_widget_id = implode(':', array_filter([$field_name, $id_suffix]));
    $wrapper_id = $field_name . '-media-library-wrapper' . $id_suffix;
    $limit_validation_errors = [array_merge($parents, [$field_name])];

    $settings = $this->getFieldSetting('handler_settings');
    $element += [
      '#type' => 'fieldset',
      '#cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
      // If no target bundles are specified, all target bundles are allowed.
      '#target_bundles' => $settings['target_bundles'] ?? [],
      '#attributes' => [
        'id' => $wrapper_id,
        'class' => ['js-media-library-widget'],
      ],
      '#pre_render' => [
        [$this, 'preRenderWidget'],
      ],
      '#attached' => [
        'library' => ['media_library/widget'],
      ],
      '#theme_wrappers' => [
        'fieldset__media_library_widget',
      ],
    ];

    if ($this->fieldDefinition->isRequired()) {
      $element['#element_validate'][] = [static::class, 'validateRequired'];
    }

    // When the list of allowed types in the field configuration is null,
    // ::getAllowedMediaTypeIdsSorted() returns all existing media types. When
    // the list of allowed types is an empty array, we show a message to users
    // and ask them to configure the field if they have access.
    $allowed_media_type_ids = $this->getAllowedMediaTypeIdsSorted();
    if (!$allowed_media_type_ids) {
      $element['no_types_message'] = [
        '#markup' => $this->getNoMediaTypesAvailableMessage(),
      ];
      return $element;
    }

    $multiple_items = FALSE;
    if (empty($referenced_entities)) {
      $element['#field_prefix']['empty_selection'] = [
        '#markup' => $this->t('No media items are selected.'),
      ];
    }
    else {
      // @todo Use a <button> link here.
      $multiple_items = count($referenced_entities) > 1;
      $element['#field_prefix']['weight_toggle'] = [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->t('Show media item weights'),
        '#access' => $multiple_items,
        '#attributes' => [
          'class' => [
            'link',
            'js-media-library-widget-toggle-weight',
          ],
        ],
      ];
    }

    $element['selection'] = [
      '#type' => 'container',
      '#theme_wrappers' => [
        'container__media_library_widget_selection',
      ],
      '#attributes' => [
        'class' => [
          'js-media-library-selection',
        ],
      ],
    ];

    foreach ($referenced_entities as $delta => $media_item) {
      if ($media_item->access('view')) {
        // @todo Make the view mode configurable in https://www.drupal.org/project/drupal/issues/2971209
        $preview = $view_builder->view($media_item, 'media_library');
      }
      else {
        $item_label = $media_item->access('view label') ? $media_item->label() : new FormattableMarkup('@label @id', [
          '@label' => $media_item->getEntityType()->getSingularLabel(),
          '@id' => $media_item->id(),
        ]);
        $preview = [
          '#theme' => 'media_embed_error',
          '#message' => $this->t('You do not have permission to view @item_label.', ['@item_label' => $item_label]),
        ];
      }
      $element['selection'][$delta] = [
        '#theme' => 'media_library_item__widget',
        '#attributes' => [
          'class' => [
            'js-media-library-item',
          ],
          // Add the tabindex '-1' to allow the focus to be shifted to the next
          // media item when an item is removed. We set focus to the container
          // because we do not want to set focus to the remove button
          // automatically.
          // @see ::updateWidget()
          'tabindex' => '-1',
          // Add a data attribute containing the delta to allow us to easily
          // shift the focus to a specific media item.
          // @see ::updateWidget()
          'data-media-library-item-delta' => $delta,
        ],
        'remove_button' => [
          '#type' => 'submit',
          '#name' => $field_name . '-' . $delta . '-media-library-remove-button' . $id_suffix,
          '#value' => $this->t('Remove'),
          '#media_id' => $media_item->id(),
          '#attributes' => [
            'aria-label' => $media_item->access('view label') ? $this->t('Remove @label', ['@label' => $media_item->label()]) : $this->t('Remove media'),
          ],
          '#ajax' => [
            'callback' => [static::class, 'updateWidget'],
            'wrapper' => $wrapper_id,
            'progress' => [
              'type' => 'throbber',
              'message' => $media_item->access('view label') ? $this->t('Removing @label.', ['@label' => $media_item->label()]) : $this->t('Removing media.'),
            ],
          ],
          '#submit' => [[static::class, 'removeItem']],
          // Prevent errors in other widgets from preventing removal.
          '#limit_validation_errors' => $limit_validation_errors,
        ],
        'rendered_entity' => $preview,
        'target_id' => [
          '#type' => 'hidden',
          '#value' => $media_item->id(),
        ],
        // This hidden value can be toggled visible for accessibility.
        'weight' => [
          '#type' => 'number',
          '#theme' => 'input__number__media_library_item_weight',
          '#title' => $this->t('Weight'),
          '#access' => $multiple_items,
          '#default_value' => $delta,
          '#attributes' => [
            'class' => [
              'js-media-library-item-weight',
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
    $selected_type_id = reset($allowed_media_type_ids);
    $remaining = $cardinality_unlimited ? FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED : $remaining;
    // This particular media library opener needs some extra metadata for its
    // \Drupal\media_library\MediaLibraryOpenerInterface::getSelectionResponse()
    // to be able to target the element whose 'data-media-library-widget-value'
    // attribute is the same as $field_widget_id. The entity ID, entity type ID,
    // bundle, field name are used for access checking.
    $entity = $items->getEntity();
    $opener_parameters = [
      'field_widget_id' => $field_widget_id,
      'entity_type_id' => $entity->getEntityTypeId(),
      'bundle' => $entity->bundle(),
      'field_name' => $field_name,
    ];
    // Only add the entity ID when we actually have one. The entity ID needs to
    // be a string to ensure that the media library state generates its
    // tamper-proof hash in a consistent way.
    if (!$entity->isNew()) {
      $opener_parameters['entity_id'] = (string) $entity->id();

      if ($entity->getEntityType()->isRevisionable()) {
        $opener_parameters['revision_id'] = (string) $entity->getRevisionId();
      }
    }
    $state = MediaLibraryState::create('media_library.opener.field_widget', $allowed_media_type_ids, $selected_type_id, $remaining, $opener_parameters);

    // Add a button that will load the Media library in a modal using AJAX.
    $element['open_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Add media'),
      '#name' => $field_name . '-media-library-open-button' . $id_suffix,
      '#attributes' => [
        'class' => [
          'js-media-library-open-button',
        ],
      ],
      '#media_library_state' => $state,
      '#ajax' => [
        'callback' => [static::class, 'openMediaLibrary'],
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Opening media library.'),
        ],
      ],
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
      $triggering_element = $form_state->getTriggeringElement();
      if ($triggering_element && ($trigger_parents = $triggering_element['#array_parents']) && end($trigger_parents) === 'media_library_update_widget') {
        // The widget is being rebuilt from a selection change.
        $element['open_button']['#attributes']['data-disabled-focus'] = 'true';
        $element['open_button']['#attributes']['class'][] = 'visually-hidden';
      }
      else {
        // The widget is being built without a selection change, so we can just
        // set the item to disabled now, there is no need to set the focus
        // first.
        $element['open_button']['#disabled'] = TRUE;
        $element['open_button']['#attributes']['class'][] = 'visually-hidden';
      }
    }

    // This hidden field and button are used to add new items to the widget.
    $element['media_library_selection'] = [
      '#type' => 'hidden',
      '#attributes' => [
        // This is used to pass the selection from the modal to the widget.
        'data-media-library-widget-value' => $field_widget_id,
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
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Adding selection.'),
        ],
      ],
      '#attributes' => [
        'data-media-library-widget-update' => $field_widget_id,
        'class' => ['js-hide'],
      ],
      '#validate' => [[static::class, 'validateItems']],
      '#submit' => [[static::class, 'addItems']],
      // We need to prevent the widget from being validated when no media items
      // are selected. When a media field is added in a subform, entity
      // validation is triggered in EntityFormDisplay::validateFormValues().
      // Since the media item is not added to the form yet, this triggers errors
      // for required media fields.
      '#limit_validation_errors' => !empty($referenced_entities) ? $limit_validation_errors : [],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderWidget'];
  }

  /**
   * Prepares the widget's render element for rendering.
   *
   * @param array $element
   *   The element to transform.
   *
   * @return array
   *   The transformed element.
   *
   * @see ::formElement()
   */
  public function preRenderWidget(array $element) {
    if (isset($element['open_button'])) {
      $element['#field_suffix']['open_button'] = $element['open_button'];
      unset($element['open_button']);
    }
    return $element;
  }

  /**
   * Gets the message to display when there are no allowed media types.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The message to display when there are no allowed media types.
   */
  protected function getNoMediaTypesAvailableMessage() {
    $entity_type_id = $this->fieldDefinition->getTargetEntityTypeId();

    $default_message = $this->t('There are no allowed media types configured for this field. Contact the site administrator.');

    // Show the default message if the user does not have the permissions to
    // configure the fields for the entity type.
    if (!$this->currentUser->hasPermission("administer $entity_type_id fields")) {
      return $default_message;
    }

    // Show a message for privileged users to configure the field if the Field
    // UI module is not enabled.
    if (!$this->moduleHandler->moduleExists('field_ui')) {
      return $this->t('There are no allowed media types configured for this field. Edit the field settings to select the allowed media types.');
    }

    // Add a link to the message to configure the field if the Field UI module
    // is enabled.
    $route_parameters = FieldUI::getRouteBundleParameter($this->entityTypeManager->getDefinition($entity_type_id), $this->fieldDefinition->getTargetBundle());
    $route_parameters['field_config'] = $this->fieldDefinition->id();
    $url = Url::fromRoute('entity.field_config.' . $entity_type_id . '_field_edit_form', $route_parameters);
    if ($url->access($this->currentUser)) {
      return $this->t('There are no allowed media types configured for this field. <a href=":url">Edit the field settings</a> to select the allowed media types.', [
        ':url' => $url->toString(),
      ]);
    }

    // If the user for some reason doesn't have access to the Field UI, fall
    // back to the default message.
    return $default_message;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return $element['target_id'] ?? FALSE;
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
    $is_remove_button = end($triggering_element['#parents']) === 'remove_button';
    $length = $is_remove_button ? -3 : -1;
    if (count($triggering_element['#array_parents']) < abs($length)) {
      throw new \LogicException('The element that triggered the widget update was at an unexpected depth. Triggering element parents were: ' . implode(',', $triggering_element['#array_parents']));
    }
    $parents = array_slice($triggering_element['#array_parents'], 0, $length);
    $element = NestedArray::getValue($form, $parents);

    // Always clear the textfield selection to prevent duplicate additions.
    $element['media_library_selection']['#value'] = '';

    $field_state = static::getFieldState($element, $form_state);

    // Announce the updated content to screen readers.
    if ($is_remove_button) {
      $media_item = Media::load($field_state['removed_item_id']);
      $announcement = $media_item->access('view label') ? new TranslatableMarkup('@label has been removed.', ['@label' => $media_item->label()]) : new TranslatableMarkup('Media has been removed.');
    }
    else {
      $new_items = count(static::getNewMediaItems($element, $form_state));
      $announcement = \Drupal::translation()->formatPlural($new_items, 'Added one media item.', 'Added @count media items.');
    }

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand("#$wrapper_id", $element));
    $response->addCommand(new AnnounceCommand($announcement));

    // When the remove button is clicked, shift focus to the next remove button.
    // When the last item is deleted, we no longer have a selection and shift
    // the focus to the open button.
    $removed_last = $is_remove_button && !count($field_state['items']);
    if ($is_remove_button && !$removed_last) {
      // Find the next media item by weight. The weight of the removed item is
      // added to the field state when it is removed in ::removeItem(). If there
      // is no item with a bigger weight, we automatically shift the focus to
      // the previous media item.
      // @see ::removeItem()
      $removed_item_weight = $field_state['removed_item_weight'];
      $delta_to_focus = 0;
      foreach ($field_state['items'] as $delta => $item_fields) {
        $delta_to_focus = $delta;
        if ($item_fields['weight'] > $removed_item_weight) {
          // Stop directly when we find an item with a bigger weight. We also
          // have to subtract 1 from the delta in this case, since the delta's
          // are renumbered when rebuilding the form.
          $delta_to_focus--;
          break;
        }
      }
      $response->addCommand(new InvokeCommand("#$wrapper_id [data-media-library-item-delta=$delta_to_focus]", 'focus'));
    }
    // Shift focus to the open button if the user removed the last selected
    // item, or when the user has added items to the selection and is allowed to
    // select more items. When the user is not allowed to add more items, the
    // button needs to be disabled. Since we can't shift the focus to disabled
    // elements, the focus is set via JavaScript by adding the
    // 'data-disabled-focus' attribute and we also don't want to set the focus
    // here.
    // @see Drupal.behaviors.MediaLibraryWidgetDisableButton
    elseif ($removed_last || (!$is_remove_button && !isset($element['open_button']['#attributes']['data-disabled-focus']))) {
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
    // During the form rebuild, formElement() will create field item widget
    // elements using re-indexed deltas, so clear out FormState::$input to
    // avoid a mismatch between old and new deltas. The rebuilt elements will
    // have #default_value set appropriately for the current state of the field,
    // so nothing is lost in doing this.
    // @see Drupal\media_library\Plugin\Field\FieldWidget\MediaLibraryWidget::extractFormValues
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -2);
    NestedArray::setValue($form_state->getUserInput(), $parents, NULL);

    // Get the parents required to find the top-level widget element.
    if (count($triggering_element['#array_parents']) < 4) {
      throw new \LogicException('Expected the remove button to be more than four levels deep in the form. Triggering element parents were: ' . implode(',', $triggering_element['#array_parents']));
    }
    $parents = array_slice($triggering_element['#array_parents'], 0, -3);
    $element = NestedArray::getValue($form, $parents);

    // Get the field state.
    $path = $element['#parents'];
    $values = NestedArray::getValue($form_state->getValues(), $path);
    $field_state = static::getFieldState($element, $form_state);

    // Get the delta of the item being removed.
    $delta = array_slice($triggering_element['#array_parents'], -2, 1)[0];
    if (isset($values['selection'][$delta])) {
      // Add the weight of the removed item to the field state so we can shift
      // focus to the next/previous item in an easy way.
      $field_state['removed_item_weight'] = $values['selection'][$delta]['weight'];
      $field_state['removed_item_id'] = $triggering_element['#media_id'];
      unset($values['selection'][$delta]);
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
        $form_state->setError($element, new TranslatableMarkup('The media item "@label" is not of an accepted type. Allowed types: @types', [
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
  public static function addItems(array $form, FormStateInterface $form_state) {
    // During the form rebuild, formElement() will create field item widget
    // elements using re-indexed deltas, so clear out FormState::$input to
    // avoid a mismatch between old and new deltas. The rebuilt elements will
    // have #default_value set appropriately for the current state of the field,
    // so nothing is lost in doing this.
    // @see Drupal\media_library\Plugin\Field\FieldWidget\MediaLibraryWidget::extractFormValues
    $button = $form_state->getTriggeringElement();
    $parents = array_slice($button['#parents'], 0, -1);
    $parents[] = 'selection';
    NestedArray::setValue($form_state->getUserInput(), $parents, NULL);

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
    // Get the new media IDs passed to our hidden button. We need to use the
    // actual user input, since when #limit_validation_errors is used, the
    // unvalidated user input is not added to the form state.
    // @see FormValidator::handleErrorsWithLimitedValidation()
    $values = $form_state->getUserInput();
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
    // We need to use the actual user input, since when #limit_validation_errors
    // is used, the unvalidated user input is not added to the form state.
    // @see FormValidator::handleErrorsWithLimitedValidation()
    $values = NestedArray::getValue($form_state->getUserInput(), $path);
    $selection = $values['selection'] ?? [];

    $widget_state = static::getWidgetState($element['#field_parents'], $element['#field_name'], $form_state);
    $widget_state['items'] = $widget_state['items'] ?? $selection;
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
   * Validates whether the widget is required and contains values.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The form array.
   */
  public static function validateRequired(array $element, FormStateInterface $form_state, array $form) {
    // If a remove button triggered submit, this validation isn't needed.
    if (in_array([static::class, 'removeItem'], $form_state->getSubmitHandlers(), TRUE)) {
      return;
    }

    // If user has no access, the validation isn't needed.
    if (isset($element['#access']) && !$element['#access']) {
      return;
    }

    $field_state = static::getFieldState($element, $form_state);
    // Trigger error if the field is required and no media is present. Although
    // the Form API's default validation would also catch this, the validation
    // error message is too vague, so a more precise one is provided here.
    if (count($field_state['items']) === 0) {
      $form_state->setError($element, new TranslatableMarkup('@name field is required.', ['@name' => $element['#title']]));
    }
  }

}
