<?php

namespace Drupal\user\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
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
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new UserSelection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $module_handler, $current_user);

    $this->connection = $connection;
    $this->userStorage = $entity_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $selection_handler_settings = $this->configuration['handler_settings'];

    // Merge in default values.
    $selection_handler_settings += array(
      'filter' => array(
        'type' => '_none',
      ),
      'include_anonymous' => TRUE,
    );

    $form['include_anonymous'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Include the anonymous user.'),
      '#default_value' => $selection_handler_settings['include_anonymous'],
    );

    // Add user specific filter options.
    $form['filter']['type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Filter by'),
      '#options' => array(
        '_none' => $this->t('- None -'),
        'role' => $this->t('User role'),
      ),
      '#ajax' => TRUE,
      '#limit_validation_errors' => array(),
      '#default_value' => $selection_handler_settings['filter']['type'],
    );

    $form['filter']['settings'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('entity_reference-settings')),
      '#process' => array(array('\Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem', 'formProcessMergeParent')),
    );

    if ($selection_handler_settings['filter']['type'] == 'role') {
      // Merge in default values.
      $selection_handler_settings['filter'] += array(
        'role' => NULL,
      );

      $form['filter']['settings']['role'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Restrict to the selected roles'),
        '#required' => TRUE,
        '#options' => array_diff_key(user_role_names(TRUE), array(RoleInterface::AUTHENTICATED_ID => RoleInterface::AUTHENTICATED_ID)),
        '#default_value' => $selection_handler_settings['filter']['role'],
      );
    }

    $form += parent::buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // The user entity doesn't have a label column.
    if (isset($match)) {
      $query->condition('name', $match, $match_operator);
    }

    // Filter by role.
    $handler_settings = $this->configuration['handler_settings'];
    if (!empty($handler_settings['filter']['role'])) {
      $query->condition('roles', $handler_settings['filter']['role'], 'IN');
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
    if (!empty($this->configuration['handler_settings']['filter']['role'])) {
      $entities = array_filter($entities, function ($user) {
        /** @var \Drupal\user\UserInterface $user */
        return !empty(array_intersect($user->getRoles(), $this->configuration['handler_settings']['filter']['role']));
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
    // Bail out early if we do not need to match the Anonymous user.
    $handler_settings = $this->configuration['handler_settings'];
    if (isset($handler_settings['include_anonymous']) && !$handler_settings['include_anonymous']) {
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
          $or = db_or();
          $or->condition($condition['field'], $condition['value'], $condition['operator']);
          // Sadly, the Database layer doesn't allow us to build a condition
          // in the form ':placeholder = :placeholder2', because the 'field'
          // part of a condition is always escaped.
          // As a (cheap) workaround, we separately build a condition with no
          // field, and concatenate the field and the condition separately.
          $value_part = db_and();
          $value_part->condition('anonymous_name', $condition['value'], $condition['operator']);
          $value_part->compile($this->connection, $query);
          $or->condition(db_and()
            ->where(str_replace('anonymous_name', ':anonymous_name', (string) $value_part), $value_part->arguments() + array(':anonymous_name' => \Drupal::config('user.settings')->get('anonymous')))
            ->condition('base_table.uid', 0)
          );
          $query->condition($or);
        }
      }
    }
  }

}
