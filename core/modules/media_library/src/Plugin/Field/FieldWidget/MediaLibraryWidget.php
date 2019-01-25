<?php

namespace Drupal\media_library\Plugin\Field\FieldWidget;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\media_library\Form\MediaLibraryUploadForm;
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
 */
class MediaLibraryWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Indicates whether or not the add button should be shown.
   *
   * @var bool
   */
  protected $addAccess = FALSE;

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
   * @param bool $add_access
   *   Indicates whether or not the add button should be shown.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, $add_access) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->addAccess = $add_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $settings = $configuration['field_definition']->getSettings()['handler_settings'];
    $target_bundles = isset($settings['target_bundles']) ? $settings['target_bundles'] : NULL;
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      // @todo Use URL access in https://www.drupal.org/node/2956747
      MediaLibraryUploadForm::create($container)->access($target_bundles)->isAllowed()
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
    $id_suffix = '-' . implode('-', $parents);
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
        '#markup' => $this->t('<p>No media items are selected.</p>'),
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
          'title' => $this->t('Re-order media by numerical weight instead of dragging'),
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
      $element['#description'] .= '<br />' . $cardinality_message;
    }

    $query = [
      'media_library_widget_id' => $field_name . $id_suffix,
      'media_library_allowed_types' => $element['#target_bundles'],
      'media_library_remaining' => $cardinality_unlimited ? FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED : $remaining,
    ];
    $dialog_options = Json::encode([
      'dialogClass' => 'media-library-widget-modal',
      'height' => '75%',
      'width' => '75%',
      'title' => $this->t('Media library'),
    ]);

    // Add a button that will load the Media library in a modal using AJAX.
    $element['media_library_open_button'] = [
      '#type' => 'link',
      '#title' => $this->t('Browse media'),
      '#name' => $field_name . '-media-library-open-button' . $id_suffix,
      // @todo Make the view configurable in https://www.drupal.org/project/drupal/issues/2971209
      '#url' => Url::fromRoute('view.media_library.widget', [], [
        'query' => $query,
      ]),
      '#attributes' => [
        'class' => ['button', 'use-ajax', 'media-library-open-button'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => $dialog_options,
      ],
      // Prevent errors in other widgets from preventing addition.
      '#limit_validation_errors' => $limit_validation_errors,
      '#access' => $cardinality_unlimited || $remaining > 0,
    ];

    $element['media_library_add_button'] = [
      '#type' => 'link',
      '#title' => $this->t('Add media'),
      '#name' => $field_name . '-media-library-add-button' . $id_suffix,
      '#url' => Url::fromRoute('media_library.upload', [], [
        'query' => $query,
      ]),
      '#attributes' => [
        'class' => ['button', 'use-ajax', 'media-library-add-button'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => $dialog_options,
      ],
      // Prevent errors in other widgets from preventing addition.
      '#limit_validation_errors' => $limit_validation_errors,
      '#access' => $this->addAccess && ($cardinality_unlimited || $remaining > 0),
    ];

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
   * @return array
   *   An array representing the updated widget.
   */
  public static function updateWidget(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // This callback is either invoked from the remove button or the update
    // button, which have different nesting levels.
    $length = end($triggering_element['#parents']) === 'remove_button' ? -4 : -1;
    if (count($triggering_element['#array_parents']) < abs($length)) {
      throw new \LogicException('The element that triggered the widget update was at an unexpected depth. Triggering element parents were: ' . implode(',', $triggering_element['#array_parents']));
    }
    $parents = array_slice($triggering_element['#array_parents'], 0, $length);
    $element = NestedArray::getValue($form, $parents);
    // Always clear the textfield selection to prevent duplicate additions.
    $element['media_library_selection']['#value'] = '';
    return $element;
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
      $weight = count($field_state['items']);
      foreach ($media as $media_item) {
        // Any ID can be passed to the widget, so we have to check access.
        if ($media_item->access('view')) {
          $field_state['items'][] = [
            'target_id' => $media_item->id(),
            'weight' => $weight++,
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

}
