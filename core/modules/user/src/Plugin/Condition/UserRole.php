<?php

namespace Drupal\user\Plugin\Condition;

use Drupal\Component\Utility\Html;
use Drupal\Core\Condition\Attribute\Condition;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Provides a 'User Role' condition.
 */
#[Condition(
  id: "user_role",
  label: new TranslatableMarkup("User Role"),
  context_definitions: [
    "user" => new EntityContextDefinition(
      data_type: "entity:user",
      label: new TranslatableMarkup("User"),
    ),
  ],
)]
class UserRole extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('When the user has the following roles'),
      '#default_value' => $this->configuration['roles'],
      '#options' => array_map(fn(RoleInterface $role) => Html::escape($role->label()), Role::loadMultiple()),
      '#description' => $this->t('If you select no roles, the condition will evaluate to TRUE for all users.'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'roles' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['roles'] = array_filter($form_state->getValue('roles'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // Use the role labels. They will be sanitized below.
    $roles = array_map(fn(RoleInterface $role) => $role->label(), Role::loadMultiple());
    $roles = array_intersect_key($roles, $this->configuration['roles']);
    if (count($roles) > 1) {
      $roles = implode(', ', $roles);
    }
    else {
      $roles = reset($roles);
    }
    if (!empty($this->configuration['negate'])) {
      return $this->t('The user is not a member of @roles', ['@roles' => $roles]);
    }
    else {
      return $this->t('The user is a member of @roles', ['@roles' => $roles]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['roles']) && !$this->isNegated()) {
      return TRUE;
    }
    $user = $this->getContextValue('user');
    return (bool) array_intersect($this->configuration['roles'], $user->getRoles());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Optimize cache context, if a user cache context is provided, only use
    // user.roles, since that's the only part this condition cares about.
    $contexts = [];
    foreach (parent::getCacheContexts() as $context) {
      $contexts[] = $context == 'user' ? 'user.roles' : $context;
    }
    return $contexts;
  }

}
