<?php

namespace Drupal\user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure administrator role settings for this site.
 */
class RoleSettingsForm extends FormBase {

  /**
   * The role storage used when changing the admin role.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * Constructs a \Drupal\user\Form\RoleSettingsForm object.
   *
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   */
  public function __construct(RoleStorageInterface $role_storage) {
    $this->roleStorage = $role_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('user_role')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'role_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Administrative role option.
    $form['admin_role'] = [
      '#type' => 'details',
      '#title' => $this->t('Administrator role'),
      '#open' => TRUE,
    ];
    // Do not allow users to set the anonymous or authenticated user roles as
    // the administrator role.
    $roles = user_role_names(TRUE);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    $admin_roles = $this->roleStorage->getQuery()
      ->condition('is_admin', TRUE)
      ->execute();
    $default_value = reset($admin_roles);
    $form['admin_role']['user_admin_role'] = [
      '#type' => 'select',
      '#title' => $this->t('Administrator role'),
      '#empty_value' => '',
      '#default_value' => $default_value,
      '#options' => $roles,
      '#description' => $this->t('This role will be automatically granted all permissions.'),
      // Don't allow to select a single admin role in case multiple roles got
      // marked as admin role already.
      '#access' => count($admin_roles) <= 1,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasValue('user_admin_role')) {
      $admin_roles = $this->roleStorage->getQuery()
        ->condition('is_admin', TRUE)
        ->execute();
      foreach ($admin_roles as $rid) {
        $this->roleStorage->load($rid)->setIsAdmin(FALSE)->save();
      }
      $new_admin_role = $form_state->getValue('user_admin_role');
      if ($new_admin_role) {
        $this->roleStorage->load($new_admin_role)->setIsAdmin(TRUE)->save();
      }
    }
  }

}
