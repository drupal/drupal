<?php

namespace Drupal\content_moderation\Plugin\Field\FieldWidget;

use Drupal\content_moderation\Plugin\Field\ModerationStateFieldItemList;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\content_moderation\ModerationInformation;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'moderation_state_default' widget.
 *
 * @FieldWidget(
 *   id = "moderation_state_default",
 *   label = @Translation("Moderation state"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class ModerationStateWidget extends OptionsSelectWidget implements ContainerFactoryPluginInterface {

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformation
   */
  protected $moderationInformation;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Moderation state transition validation service.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface
   */
  protected $validator;

  /**
   * Constructs a new ModerationStateWidget object.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param array $settings
   *   Field settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\content_moderation\ModerationInformation $moderation_information
   *   Moderation information service.
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $validator
   *   Moderation state transition validation service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ModerationInformation $moderation_information, StateTransitionValidationInterface $validator) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->moderationInformation = $moderation_information;
    $this->validator = $validator;
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
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('content_moderation.moderation_information'),
      $container->get('content_moderation.state_transition_validation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $entity = $items->getEntity();
    if (!$this->moderationInformation->isModeratedEntity($entity)) {
      return [];
    }
    return parent::form($items, $form, $form_state, $get_delta);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $items->getEntity();

    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $default = $items->get($delta)->value ? $workflow->getTypePlugin()->getState($items->get($delta)->value) : $workflow->getTypePlugin()->getInitialState($entity);

    /** @var \Drupal\workflows\Transition[] $transitions */
    $transitions = $this->validator->getValidTransitions($entity, $this->currentUser);

    $transition_labels = [];
    $default_value = NULL;
    foreach ($transitions as $transition) {
      $transition_to_state = $transition->to();
      $transition_labels[$transition_to_state->id()] = $transition_to_state->label();
    }

    $element += [
      '#type' => 'container',
      'current' => [
        '#type' => 'item',
        '#title' => $this->t('Current state'),
        '#markup' => $default->label(),
        '#access' => !$entity->isNew(),
        '#wrapper_attributes' => [
          'class' => ['container-inline'],
        ],
      ],
      'state' => [
        '#type' => 'select',
        '#title' => $entity->isNew() ? $this->t('Save as') : $this->t('Change to'),
        '#key_column' => $this->column,
        '#options' => $transition_labels,
        '#default_value' => $default_value,
        '#access' => !empty($transition_labels),
        '#wrapper_attributes' => [
          'class' => ['container-inline'],
        ],
      ],
    ];
    $element['#element_validate'][] = [get_class($this), 'validateElement'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    $form_state->setValueForElement($element, [$element['state']['#key_column'] => $element['state']['#value']]);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return is_a($field_definition->getClass(), ModerationStateFieldItemList::class, TRUE);
  }

}
