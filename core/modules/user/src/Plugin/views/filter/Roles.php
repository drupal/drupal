<?php

namespace Drupal\user\Plugin\views\filter;

use Drupal\user\RoleInterface;
use Drupal\user\RoleStorageInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter handler for user roles.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("user_roles")]
class Roles extends ManyToOne {

  /**
   * Constructs a Roles object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\RoleStorageInterface $roleStorage
   *   The role storage.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   The logger service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly RoleStorageInterface $roleStorage,
    protected ?LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!$logger) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $logger argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3427368', E_USER_DEPRECATED);
      $this->logger = \Drupal::service('logger.channel.default');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('user_role'),
      $container->get('logger.channel.default'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $roles = $this->roleStorage->loadMultiple();
      unset($roles[RoleInterface::ANONYMOUS_ID]);
      unset($roles[RoleInterface::AUTHENTICATED_ID]);
      $this->valueOptions = array_map(fn(RoleInterface $role) => $role->label(), $roles);
    }
    return $this->valueOptions;

  }

  /**
   * Override empty and not empty operator labels to be clearer for user roles.
   *
   * @return array[]
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
        $this->logger->warning("View %view depends on role %role, but the role does not exist.", [
          '%view' => $this->view->id(),
          '%role' => $role_id,
        ]);
      }
    }
    return $dependencies;
  }

}
