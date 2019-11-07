<?php

namespace Drupal\user\Plugin\views\filter;

use Drupal\user\RoleInterface;
use Drupal\user\RoleStorageInterface;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter handler for user roles.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("user_roles")
 */
class Roles extends ManyToOne {

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * Constructs a Roles object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RoleStorageInterface $role_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->roleStorage = $role_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('user_role')
    );
  }

  public function getValueOptions() {
    $this->valueOptions = user_role_names(TRUE);
    unset($this->valueOptions[RoleInterface::AUTHENTICATED_ID]);
    return $this->valueOptions;

  }

  /**
   * Override empty and not empty operator labels to be clearer for user roles.
   */
  public function operators() {
    $operators = parent::operators();
    $operators['empty']['title'] = $this->t("Only has the 'authenticated user' role");
    $operators['not empty']['title'] = $this->t("Has roles in addition to 'authenticated user'");
    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];

    if (in_array($this->operator, ['empty', 'not empty'])) {
      return $dependencies;
    }

    // The value might be a string due to the wrong plugin being used for role
    // field data, and subsequently the incorrect config schema object and
    // value. In the empty case stop early. Otherwise we cast it to an array
    // later.
    if (is_string($this->value) && $this->value === '') {
      return [];
    }

    foreach ((array) $this->value as $role_id) {
      if ($role = $this->roleStorage->load($role_id)) {
        $dependencies[$role->getConfigDependencyKey()][] = $role->getConfigDependencyName();
      }
      else {
        trigger_error("The {$role_id} role does not exist. You should review and fix the configuration of the {$this->view->id()} view.", E_USER_WARNING);
      }
    }
    return $dependencies;
  }

}
