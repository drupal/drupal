<?php

namespace Drupal\content_moderation\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\content_moderation\ModerationInformation;
use Drupal\content_moderation\StateTransitionValidation;
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
   * @var \Drupal\content_moderation\StateTransitionValidation
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
   * @param \Drupal\content_moderation\StateTransitionValidation $validator
   *   Moderation state transition validation service
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ModerationInformation $moderation_information, StateTransitionValidation $validator) {
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
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var ContentEntityInterface $entity */
    $entity = $items->getEntity();

    /* @var \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity */
    $bundle_entity = $this->entityTypeManager->getStorage($entity->getEntityType()->getBundleEntityType())->load($entity->bundle());
    if (!$this->moderationInformation->isModeratedEntity($entity)) {
      // @todo https://www.drupal.org/node/2779933 write a test for this.
      return $element + ['#access' => FALSE];
    }

    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $default = $items->get($delta)->value ? $workflow->getState($items->get($delta)->value) : $workflow->getInitialState();
    if (!$default) {
      throw new \UnexpectedValueException(sprintf('The %s bundle has an invalid moderation state configuration, moderation states are enabled but no default is set.', $bundle_entity->label()));
    }

    /** @var \Drupal\workflows\Transition[] $transitions */
    $transitions = $this->validator->getValidTransitions($entity, $this->currentUser);

    $target_states = [];
    foreach ($transitions as $transition) {
      $target_states[$transition->to()->id()] = $transition->label();
    }

    // @todo https://www.drupal.org/node/2779933 write a test for this.
    $element += [
      '#access' => FALSE,
      '#type' => 'select',
      '#options' => $target_states,
      '#default_value' => $default->id(),
      '#published' => $default->isPublishedState(),
      '#key_column' => $this->column,
    ];
    $element['#element_validate'][] = [get_class($this), 'validateElement'];

    // Use the dropbutton.
    $element['#process'][] = [get_called_class(), 'processActions'];
    return $element;
  }

  /**
   * Entity builder updating the node moderation state with the submitted value.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function updateStatus($entity_type_id, ContentEntityInterface $entity, array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (isset($element['#moderation_state'])) {
      $entity->moderation_state->value = $element['#moderation_state'];
    }
  }

  /**
   * Process callback to alter action buttons.
   */
  public static function processActions($element, FormStateInterface $form_state, array &$form) {

    // We'll steal most of the button configuration from the default submit
    // button. However, NodeForm also hides that button for admins (as it adds
    // its own, too), so we have to restore it.
    $default_button = $form['actions']['submit'];
    $default_button['#access'] = TRUE;

    // Add a custom button for each transition we're allowing. The #dropbutton
    // property tells FAPI to cluster them all together into a single widget.
    $options = $element['#options'];

    $entity = $form_state->getFormObject()->getEntity();
    $translatable = !$entity->isNew() && $entity->isTranslatable();
    foreach ($options as $id => $label) {
      $button = [
        '#dropbutton' => 'save',
        '#moderation_state' => $id,
        '#weight' => -10,
      ];

      $button['#value'] = $translatable
        ? t('Save and @transition (this translation)', ['@transition' => $label])
        : t('Save and @transition', ['@transition' => $label]);

      $form['actions']['moderation_state_' . $id] = $button + $default_button;
    }

    // Hide the default buttons, including the specialty ones added by
    // NodeForm.
    foreach (['publish', 'unpublish', 'submit'] as $key) {
      $form['actions'][$key]['#access'] = FALSE;
      unset($form['actions'][$key]['#dropbutton']);
    }

    // Setup a callback to translate the button selection back into field
    // widget, so that it will get saved properly.
    $form['#entity_builders']['update_moderation_state'] = [get_called_class(), 'updateStatus'];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getName() === 'moderation_state';
  }

}
