<?php

namespace Drupal\user\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:user",
 *   label = @Translation("User selection"),
 *   entity_types = {"user"},
 *   group = "default",
 *   weight = 1
 * )
 */
class UserSelection extends DefaultSelection {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new UserSelection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, Connection $connection, EntityFieldManagerInterface $entity_field_manager = NULL, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, EntityRepositoryInterface $entity_repository = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $current_user, $entity_field_manager, $entity_type_bundle_info, $entity_repository);

    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'filter' => [
        'type' => '_none',
        'role' => NULL,
      ],
      'include_anonymous' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form['include_anonymous'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include the anonymous user.'),
      '#default_value' => $configuration['include_anonymous'],
    ];

    // Add user specific filter options.
    $form['filter']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter by'),
      '#options' => [
        '_none' => $this->t('- None -'),
        'role' => $this->t('User role'),
      ],
      '#ajax' => TRUE,
      '#limit_validation_errors' => [],
      '#default_value' => $configuration['filter']['type'],
    ];

    $form['filter']['settings'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity_reference-settings']],
      '#process' => [['\Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem', 'formProcessMergeParent']],
    ];

    if ($configuration['filter']['type'] == 'role') {
      $roles = Role::loadMultiple();
      unset($roles[RoleInterface::ANONYMOUS_ID]);
      unset($roles[RoleInterface::AUTHENTICATED_ID]);
      $roles = array_map(fn(RoleInterface $role) => $role->label(), $roles);
      $form['filter']['settings']['role'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Restrict to the selected roles'),
        '#required' => TRUE,
        '#options' => $roles,
        '#default_value' => $configuration['filter']['role'],
      ];
    }

    $form += parent::buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    $configuration = $this->getConfiguration();

    // Filter out the Anonymous user if the selection handler is configured to
    // exclude it.
    if (!$configuration['include_anonymous']) {
      $query->condition('uid', 0, '<>');
    }

    // The user entity doesn't have a label column.
    if (isset($match)) {
      $query->condition('name', $match, $match_operator);
    }

    // Filter by role.
    if (!empty($configuration['filter']['role'])) {
      $query->condition('roles', $configuration['filter']['role'], 'IN');
    }

    // Adding the permission check is sadly insufficient for users: core
    // requires us to also know about the concept of 'blocked' and 'active'.
    if (!$this->currentUser->hasPermission('administer users')) {
      $query->condition('status', 1);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $user = parent::createNewEntity($entity_type_id, $bundle, $label, $uid);

    // In order to create a referenceable user, it needs to be active.
    if (!$this->currentUser->hasPermission('administer users')) {
      /** @var \Drupal\user\UserInterface $user */
      $user->activate();
    }

    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    if ($role = $this->getConfiguration()['filter']['role']) {
      $entities = array_filter($entities, function ($user) use ($role) {
        /** @var \Drupal\user\UserInterface $user */
        return !empty(array_intersect($user->getRoles(), $role));
      });
    }
    if (!$this->currentUser->hasPermission('administer users')) {
      $entities = array_filter($entities, function ($user) {
        /** @var \Drupal\user\UserInterface $user */
        return $user->isActive();
      });
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {
    parent::entityQueryAlter($query);

    // Bail out early if we do not need to match the Anonymous user.
    if (!$this->getConfiguration()['include_anonymous']) {
      return;
    }

    if ($this->currentUser->hasPermission('administer users')) {
      // In addition, if the user is administrator, we need to make sure to
      // match the anonymous user, that doesn't actually have a name in the
      // database.
      $conditions = &$query->conditions();
      foreach ($conditions as $key => $condition) {
        if ($key !== '#conjunction' && is_string($condition['field']) && $condition['field'] === 'users_field_data.name') {
          // Remove the condition.
          unset($conditions[$key]);

          // Re-add the condition and a condition on uid = 0 so that we end up
          // with a query in the form:
          // WHERE (name LIKE :name) OR (:anonymous_name LIKE :name AND uid = 0)
          $or = $this->connection->condition('OR');
          $or->condition($condition['field'], $condition['value'], $condition['operator']);
          // Sadly, the Database layer doesn't allow us to build a condition
          // in the form ':placeholder = :placeholder2', because the 'field'
          // part of a condition is always escaped.
          // As a (cheap) workaround, we separately build a condition with no
          // field, and concatenate the field and the condition separately.
          $value_part = $this->connection->condition('AND');
          $value_part->condition('anonymous_name', $condition['value'], $condition['operator']);
          $value_part->compile($this->connection, $query);
          $or->condition(($this->connection->condition('AND'))
            ->where(str_replace($query->escapeField('anonymous_name'), ':anonymous_name', (string) $value_part), $value_part->arguments() + [':anonymous_name' => \Drupal::config('user.settings')->get('anonymous')])
            ->condition('base_table.uid', 0)
          );
          $query->condition($or);
        }
      }
    }
  }

}
