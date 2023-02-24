<?php

namespace Drupal\field_ui\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the field settings form.
 *
 * @internal
 */
class FieldConfigEditForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $entity;

  /**
   * The field storage being used by this form.
   *
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $fieldStorage;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new FieldConfigDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $this->fieldStorage = $this->entity->getFieldStorageDefinition();

    if ($this->fieldStorage->isLocked()) {
      $form['locked'] = [
        '#markup' => $this->t('The field %field is locked and cannot be edited.', ['%field' => $this->entity->getLabel()]),
      ];
      return $form;
    }

    $form['#prefix'] = '<div id="field-ui-edit-form">';
    $form['#suffix'] = '</div>';

    $form['tabs'] = [
      '#theme' => 'field_ui_tabs',
      '#items' => [
        [
          'value' => [
            '#type' => 'link',
            '#title' => $this->t('Basic settings'),
            '#url' =>  Url::fromUri('internal://<none>#basic'),
            '#attributes' => [
              'class' => [
                'tabs__link',
                'js-tabs-link',
                'is-active',
              ],
            ],
          ],
        ],
        [
          'value' => [
            '#type' => 'link',
            '#title' => $this->t('Advanced settings'),
            '#url' =>  Url::fromUri('internal://<none>#advanced'),
            '#attributes' => [
              'class' => [
                'tabs__link',
                'js-tabs-link',
              ],
            ],
          ],
        ],
      ],
    ];
    $form['basic'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => ['basic']
      ],
      '#title' => $this->t('Basic settings'),
    ];
    $form['advanced'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => ['advanced']
      ],
      '#title' => $this->t('Advanced settings'),
    ];

    // Build the configurable field values.
    $form['basic']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->entity->getLabel() ?: $this->fieldStorage->getName(),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#weight' => -20,
    ];

    $form['basic']['cardinality'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple values'),
      '#default_value' => $this->fieldStorage->getCardinality() > 1 || $this->fieldStorage->getCardinality() === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];
    $form['basic']['cardinality_unlimited'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow unlimited values'),
      '#default_value' => $this->fieldStorage->getCardinality() === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      '#states' => [
        'invisible' => [
          ':input[name="cardinality"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['basic']['cardinality_number'] = [
      '#type' => 'number',
      '#min' => 2,
      '#title' => $this->t('Limit'),
      '#default_value' => $this->fieldStorage->getCardinality() > 1 ? $this->fieldStorage->getCardinality() : '2',
      '#size' => 2,
      '#states' => [
        'visible' => [
          ':input[name="cardinality"]' => ['checked' => TRUE],
          ':input[name="cardinality_unlimited"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['advanced']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Help text'),
      '#default_value' => $this->entity->getDescription(),
      '#rows' => 5,
      '#description' => $this->t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', ['@tags' => FieldFilteredMarkup::displayAllowedTags()]) . '<br />' . $this->t('This field supports tokens.'),
      '#weight' => -10,
    ];

    $form['basic']['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required field'),
      '#default_value' => $this->entity->isRequired(),
      '#weight' => -5,
    ];

    // Create an arbitrary entity object (used by the 'default value' widget).
    $ids = (object) [
      'entity_type' => $this->entity->getTargetEntityTypeId(),
      'bundle' => $this->entity->getTargetBundle(),
      'entity_id' => NULL,
    ];
    $form['#entity'] = _field_create_entity_from_ids($ids);
    $items = $form['#entity']->get($this->entity->getName());
    $item = $items->first() ?: $items->appendItem();

    $form['basic']['field_storage_settings'] = [
      '#tree' => TRUE,
    ];
    $form['basic']['field_storage_settings'] += $item->storageSettingsForm($form, $form_state, $this->fieldStorage->hasData());
    foreach (Element::children($form['basic']['field_storage_settings']) as $child) {
      if (isset($form['basic']['field_storage_settings'][$child]['#group'])) {
        $form['basic']['field_storage_settings'][$child]['#parents'] = ['field_storage_settings', $child];
        $form['advanced'][$child] = $form['basic']['field_storage_settings'][$child];
        unset($form['basic']['field_storage_settings'][$child]);
      }
    }

    $form['basic']['settings'] = [
      '#tree' => TRUE,
    ];
    $form['basic']['settings'] += $item->fieldSettingsForm($form, $form_state);
    foreach (Element::children($form['basic']['settings']) as $child) {
      if (isset($form['basic']['field_settings'][$child]['#group'])) {
        $form['basic']['field_settings'][$child]['#parents'] = ['field_settings', $child];
        $form['advanced'][$child] = $form['basic']['field_settings'][$child];
        unset($form['basic']['field_settings'][$child]);
      }
    }


    $form['third_party_settings'] = [
      '#tree' => TRUE,
      '#weight' => 11,
      '#group' => 'advanced',
    ];

    // Add handling for default value.
    if ($element = $items->defaultValuesForm($form, $form_state)) {
      $has_default_value = FALSE;
      foreach (Element::children($element['widget']) as $child) {
        if (isset($element['widget'][$child]['#default_value'])) {
          $has_default_value = TRUE;
          break;
        }
      }
      $form['advanced']['default_value_checkbox'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Set initial value'),
        '#default_value' => $has_default_value,
        '#description' => $this->t('Provide a pre-filled value for the editing form.'),
      ];

      $element = array_merge($element, [
        '#type' => 'details',
        '#title' => $this->t('Initial value'),
        '#open' => TRUE,
        '#tree' => TRUE,
        '#description' => $this->t('The default value for this field to pre-fill the form when creating new content.'),
        '#weight' => 12,
        '#states' => [
          'invisible' => [
            ':input[name="default_value_checkbox"]' => ['checked' => FALSE],
          ],
        ],
      ]);

      $form['advanced']['default_value'] = $element;
    }

    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
//    $form['#action'] =  Url::fromRoute("entity.field_config.{$this->entity->getTargetEntityTypeId()}_field_edit_form", ['field_config' => $this->entity->id(), 'node_type' => $this->entity->getTargetBundle()])->toString();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save settings');
    $actions['submit']['#ajax'] = [
      'callback' => [$this, 'ajaxSubmitForm'],
      'url' => Url::fromRoute("entity.field_config.{$this->entity->getTargetEntityTypeId()}_field_edit_form", ['field_config' => $this->entity->id(), 'node_type' => $this->entity->getTargetBundle()]),
      'options' => [
        'query' => [
          FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
        ],
      ],
    ];

    if (!$this->entity->isNew()) {
      $target_entity_type = $this->entityTypeManager->getDefinition($this->entity->getTargetEntityTypeId());
      $route_parameters = [
        'field_config' => $this->entity->id(),
      ] + FieldUI::getRouteBundleParameter($target_entity_type, $this->entity->getTargetBundle());
      $url = new Url('entity.field_config.' . $target_entity_type->id() . '_field_delete_form', $route_parameters);

      if ($this->getRequest()->query->has('destination')) {
        $query = $url->getOption('query');
        $query['destination'] = $this->getRequest()->query->get('destination');
        $url->setOption('query', $query);
      }
      $actions['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Delete'),
        '#url' => $url,
        '#access' => $this->entity->access('delete'),
        '#attributes' => [
          'class' => ['button', 'button--danger', 'use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => '85vw',
          ]),
        ],
      ];
    }

    return $actions;
  }

  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if ($form_state::hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#field-ui-edit-form', $form));
      return $response;
    }

    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new RedirectCommand(FieldUI::getOverviewRouteInfo($this->entity->getTargetEntityTypeId(), $this->entity->getTargetBundle())->toString()));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (isset($form['advanced']['default_value'])) {
      $item = $form['#entity']->get($this->entity->getName());
      $item->defaultValuesFormValidate($form['advanced']['default_value'], $form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Handle the default value.
    $default_value = [];
    if (isset($form['advanced']['default_value'])) {
      $items = $form['#entity']->get($this->entity->getName());
      $default_value = $items->defaultValuesFormSubmit($form['advanced']['default_value'], $form, $form_state);
    }
    $this->entity->setDefaultValue($default_value);

    $this->fieldStorage->setCardinality(!$form_state->getValue('cardinality') ? 1 : ($form_state->getValue('cardinality_unlimited') ? FieldStorageConfigInterface::CARDINALITY_UNLIMITED : $form_state->getValue('cardinality_number')));
    $this->fieldStorage->setSettings($form_state->getValue('field_storage_settings'));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->fieldStorage->save();

    $this->messenger()->addStatus($this->t('Saved %label configuration.', ['%label' => $this->entity->getLabel()]));

    $request = $this->getRequest();
    if (($destinations = $request->query->all('destinations')) && $next_destination = FieldUI::getNextDestination($destinations)) {
      $request->query->remove('destinations');
      $form_state->setRedirectUrl($next_destination);
    }
    else {
      $form_state->setRedirectUrl(FieldUI::getOverviewRouteInfo($this->entity->getTargetEntityTypeId(), $this->entity->getTargetBundle()));
    }
  }

  /**
   * The _title_callback for the field settings form.
   *
   * @param \Drupal\field\FieldConfigInterface $field_config
   *   The field.
   *
   * @return string
   *   The label of the field.
   */
  public function getTitle(FieldConfigInterface $field_config) {
    return $field_config->label();
  }

}
