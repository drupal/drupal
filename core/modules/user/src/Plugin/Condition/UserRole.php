<?php

namespace Drupal\user\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'User Role' condition.
 *
 * @Condition(
 *   id = "user_role",
 *   label = @Translation("User Role"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", label = @Translation("User"))
 *   }
 * )
 *
 */
class UserRole extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('When the user has the following roles'),
      '#default_value' => $this->configuration['roles'],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names()),
      '#description' => $this->t('If you select no roles, the condition will evaluate to TRUE for all users.'),
    );
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'roles' => array(),
    ) + parent::defaultConfiguration();
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
    $roles = array_intersect_key(user_role_names(), $this->configuration['roles']);
    if (count($roles) > 1) {
      $roles = implode(', ', $roles);
    }
    else {
      $roles = reset($roles);
    }
    if (!empty($this->configuration['negate'])) {
      return $this->t('The user is not a member of @roles', array('@roles' => $roles));
    }
    else {
      return $this->t('The user is a member of @roles', array('@roles' => $roles));
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
