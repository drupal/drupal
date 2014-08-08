<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Condition\UserRole.
 */

namespace Drupal\user\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;

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
      '#options' => array_map('\Drupal\Component\Utility\String::checkPlain', user_role_names()),
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

}
