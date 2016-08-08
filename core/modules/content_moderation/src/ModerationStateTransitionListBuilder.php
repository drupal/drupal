<?php

namespace Drupal\content_moderation;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Moderation state transition entities.
 */
class ModerationStateTransitionListBuilder extends DraggableListBuilder {

  /**
   * Moderation state entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $stateStorage;

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.manager')->getStorage('moderation_state'),
      $container->get('entity.manager')->getStorage('user_role')
    );
  }

  /**
   * Constructs a new ModerationStateTransitionListBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity Type.
   * @param \Drupal\Core\Entity\EntityStorageInterface $transition_storage
   *   Moderation state transition entity storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $state_storage
   *   Moderation state entity storage.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $transition_storage, EntityStorageInterface $state_storage, RoleStorageInterface $role_storage) {
    parent::__construct($entity_type, $transition_storage);
    $this->stateStorage = $state_storage;
    $this->roleStorage = $role_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_moderation_transition_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['to'] = $this->t('To state');
    $header['label'] = $this->t('Button label');
    $header['roles'] = $this->t('Allowed roles');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['to']['#markup'] = $this->stateStorage->load($entity->getToState())->label();
    $row['label'] = $entity->label();
    $row['roles']['#markup'] = implode(', ', user_role_names(FALSE, 'use ' . $entity->id() . ' transition'));

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['item'] = [
      '#type' => 'item',
      '#markup' => $this->t('On this screen you can define <em>transitions</em>. Every time an entity is saved, it undergoes a transition. It is not possible to save an entity if it tries do a transition not defined here. Transitions do not necessarily mean a state change, it is possible to transition from a state to the same state but that transition needs to be defined here as well.'),
      '#weight' => -5,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->entities = $this->load();

    // Get all the moderation states and sort them by weight.
    $states = $this->stateStorage->loadMultiple();
    uasort($states, array($this->entityType->getClass(), 'sort'));

    /** @var \Drupal\content_moderation\ModerationStateTransitionInterface $entity */
    $groups = array_fill_keys(array_keys($states), []);
    foreach ($this->entities as $entity) {
      $groups[$entity->getFromState()][] = $entity;
    }

    foreach ($groups as $group_name => $entities) {
      $form[$group_name] = [
        '#type' => 'details',
        '#title' => $this->t('From @state to...', ['@state' => $states[$group_name]->label()]),
        // Make sure that the first group is always open.
        '#open' => $group_name === array_keys($groups)[0],
      ];

      $form[$group_name][$this->entitiesKey] = array(
        '#type' => 'table',
        '#header' => $this->buildHeader(),
        '#empty' => t('There is no @label yet.', array('@label' => $this->entityType->getLabel())),
        '#tabledrag' => array(
          array(
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'weight',
          ),
        ),
      );

      $delta = 10;
      // Change the delta of the weight field if have more than 20 entities.
      if (!empty($this->weightKey)) {
        $count = count($this->entities);
        if ($count > 20) {
          $delta = ceil($count / 2);
        }
      }
      foreach ($entities as $entity) {
        $row = $this->buildRow($entity);
        if (isset($row['label'])) {
          $row['label'] = array('#markup' => $row['label']);
        }
        if (isset($row['weight'])) {
          $row['weight']['#delta'] = $delta;
        }
        $form[$group_name][$this->entitiesKey][$entity->id()] = $row;
      }
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save order'),
      '#button_type' => 'primary',
    );

    return $form;
  }

}
