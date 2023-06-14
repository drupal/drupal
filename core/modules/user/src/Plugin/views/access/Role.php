<?php

namespace Drupal\user\Plugin\views\access;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;
use Drupal\user\RoleStorageInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Session\AccountInterface;

/**
 * Access plugin that provides role-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "role",
 *   title = @Translation("Role"),
 *   help = @Translation("Access will be granted to users with any of the specified roles.")
 * )
 */
class Role extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * Constructs a Role object.
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

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return !empty(array_intersect(array_filter($this->options['role']), $account->getRoles()));
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    if ($this->options['role']) {
      $route->setRequirement('_role', (string) implode('+', $this->options['role']));
    }
  }

  public function summaryTitle() {
    $count = count($this->options['role']);
    if ($count < 1) {
      return $this->t('No role(s) selected');
    }
    elseif ($count > 1) {
      return $this->t('Multiple roles');
    }
    else {
      $rid = reset($this->options['role']);
      return $this->roleStorage->load($rid)->label();
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['role'] = ['default' => []];

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['role'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Role'),
      '#default_value' => $this->options['role'],
      '#options' => array_map(fn(RoleInterface $role) => Html::escape($role->label()), $this->roleStorage->loadMultiple()),
      '#description' => $this->t('Only the checked roles will be able to access this display.'),
    ];
  }

  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $role = $form_state->getValue(['access_options', 'role']);
    $role = array_filter($role);

    if (!$role) {
      $form_state->setError($form['role'], $this->t('You must select at least one role if type is "by role"'));
    }

    $form_state->setValue(['access_options', 'role'], $role);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    foreach (array_keys($this->options['role']) as $rid) {
      if ($role = $this->roleStorage->load($rid)) {
        $dependencies[$role->getConfigDependencyKey()][] = $role->getConfigDependencyName();
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.roles'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
