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
      $container->get('entity.manager')->getStorage('user_role')
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
    foreach ($this->value as $role_id) {
      $role = $this->roleStorage->load($role_id);
      $dependencies[$role->getConfigDependencyKey()][] = $role->getConfigDependencyName();
    }
    return $dependencies;
  }

}
