<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\argument_validator\User.
 */

namespace Drupal\user\Plugin\views\argument_validator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\views\Plugin\views\argument_validator\Entity;

/**
 * Validate whether an argument is a valid user.
 *
 * This supports either numeric arguments (UID) or strings (username) and
 * converts either one into the user's UID.  This validator also sets the
 * argument's title to the username.
 */
class User extends Entity {

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager);

    $this->userStorage = $entity_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['restrict_roles'] = array('default' => FALSE, 'bool' => TRUE);
    $options['roles'] = array('default' => array());

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['restrict_roles'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Restrict user based on role'),
      '#default_value' => $this->options['restrict_roles'],
    );

    $form['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Restrict to the selected roles'),
      '#options' => array_map(array('\Drupal\Component\Utility\String', 'checkPlain'), user_role_names(TRUE)),
      '#default_value' => $this->options['roles'],
      '#description' => $this->t('If no roles are selected, users from any role will be allowed.'),
      '#states' => array(
        'visible' => array(
          ':input[name="options[validate][options][user][restrict_roles]"]' => array('checked' => TRUE),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, &$form_state, &$options = array()) {
    // filter trash out of the options so we don't store giant unnecessary arrays
    $options['roles'] = array_filter($options['roles']);
  }

  /**
   * {@inheritdoc}
   */
  protected function validateEntity(EntityInterface $entity) {
    /** @var \Drupal\user\UserInterface $entity */
    $role_check_success = TRUE;
    // See if we're filtering users based on roles.
    if (!empty($this->options['restrict_roles']) && !empty($this->options['roles'])) {
      $roles = $this->options['roles'];
      if (!(bool) array_intersect($entity->getRoles(), $roles)) {
        $role_check_success = FALSE;
      }
    }

    return $role_check_success && parent::validateEntity($entity);
  }

}
