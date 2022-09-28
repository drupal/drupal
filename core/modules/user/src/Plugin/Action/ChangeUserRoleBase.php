<?php

namespace Drupal\user\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for operations to change a user's role.
 */
abstract class ChangeUserRoleBase extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  use DependencyTrait;

  /**
   * The user role entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeInterface $entity_type) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityType = $entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getDefinition('user_role')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'rid' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $roles = user_role_names(TRUE);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    $form['rid'] = [
      '#type' => 'radios',
      '#title' => $this->t('Role'),
      '#options' => $roles,
      '#default_value' => $this->configuration['rid'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['rid'] = $form_state->getValue('rid');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    if (!empty($this->configuration['rid'])) {
      $prefix = $this->entityType->getConfigPrefix() . '.';
      $this->addDependency('config', $prefix . $this->configuration['rid']);
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andIf($object->roles->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
