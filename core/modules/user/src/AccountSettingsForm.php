<?php

/**
 * @file
 * Contains \Drupal\user\AccountSettingsForm.
 */

namespace Drupal\user;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure user settings for this site.
 */
class AccountSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The role storage used when changing the admin role.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * Constructs a \Drupal\user\AccountSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, RoleStorageInterface $role_storage) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->roleStorage = $role_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity.manager')->getStorage('user_role')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'system.site',
      'user.mail',
      'user.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('user.settings');
    $mail_config = $this->config('user.mail');
    $site_config = $this->config('system.site');

    $form['#attached']['library'][] = 'user/drupal.user.admin';

    // Settings for anonymous users.
    $form['anonymous_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Anonymous users'),
      '#open' => TRUE,
    );
    $form['anonymous_settings']['anonymous'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $config->get('anonymous'),
      '#description' => $this->t('The name used to indicate anonymous users.'),
      '#required' => TRUE,
    );

    // Administrative role option.
    $form['admin_role'] = array(
      '#type' => 'details',
      '#title' => $this->t('Administrator role'),
      '#open' => TRUE,
    );
    // Do not allow users to set the anonymous or authenticated user roles as the
    // administrator role.
    $roles = user_role_names(TRUE);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);

    $admin_roles = $this->roleStorage->getQuery()
      ->condition('is_admin', TRUE)
      ->execute();
    $default_value = reset($admin_roles);

    $form['admin_role']['user_admin_role'] = array(
      '#type' => 'select',
      '#title' => $this->t('Administrator role'),
      '#empty_value' => '',
      '#default_value' => $default_value,
      '#options' => $roles,
      '#description' => $this->t('This role will be automatically assigned new permissions whenever a module is enabled. Changing this setting will not affect existing permissions.'),
      // Don't allow to select a single admin role in case multiple roles got
      // marked as admin role already.
      '#access' => count($admin_roles) <= 1,
    );

    // @todo Remove this check once language settings are generalized.
    if ($this->moduleHandler->moduleExists('content_translation')) {
      $form['language'] = array(
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#open' => TRUE,
        '#tree' => TRUE,
      );
      $form_state->set(['content_translation', 'key'], 'language');
      $form['language'] += content_translation_enable_widget('user', 'user', $form, $form_state);
    }

    // User registration settings.
    $form['registration_cancellation'] = array(
      '#type' => 'details',
      '#title' => $this->t('Registration and cancellation'),
      '#open' => TRUE,
    );
    $form['registration_cancellation']['user_register'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Who can register accounts?'),
      '#default_value' => $config->get('register'),
      '#options' => array(
        USER_REGISTER_ADMINISTRATORS_ONLY => $this->t('Administrators only'),
        USER_REGISTER_VISITORS => $this->t('Visitors'),
        USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL => $this->t('Visitors, but administrator approval is required'),
      )
    );
    $form['registration_cancellation']['user_email_verification'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Require email verification when a visitor creates an account'),
      '#default_value' => $config->get('verify_mail'),
      '#description' => $this->t('New users will be required to validate their email address prior to logging into the site, and will be assigned a system-generated password. With this setting disabled, users will be logged in immediately upon registering, and may select their own passwords during registration.')
    );
    $form['registration_cancellation']['user_password_strength'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable password strength indicator'),
      '#default_value' => $config->get('password_strength'),
    );
    $form['registration_cancellation']['user_cancel_method'] = array(
      '#type' => 'radios',
      '#title' => $this->t('When cancelling a user account'),
      '#default_value' => $config->get('cancel_method'),
      '#description' => $this->t('Users with the %select-cancel-method or %administer-users <a href=":permissions-url">permissions</a> can override this default method.', array('%select-cancel-method' => $this->t('Select method for cancelling account'), '%administer-users' => $this->t('Administer users'), ':permissions-url' => $this->url('user.admin_permissions'))),
    );
    $form['registration_cancellation']['user_cancel_method'] += user_cancel_methods();
    foreach (Element::children($form['registration_cancellation']['user_cancel_method']) as $key) {
      // All account cancellation methods that specify #access cannot be
      // configured as default method.
      // @see hook_user_cancel_methods_alter()
      if (isset($form['registration_cancellation']['user_cancel_method'][$key]['#access'])) {
        $form['registration_cancellation']['user_cancel_method'][$key]['#access'] = FALSE;
      }
    }

    // Default notifications address.
    $form['mail_notification_address'] = array(
      '#type' => 'email',
      '#title' => $this->t('Notification email address'),
      '#default_value' => $site_config->get('mail_notification'),
      '#description' => $this->t("The email address to be used as the 'from' address for all account notifications listed below. If <em>'Visitors, but administrator approval is required'</em> is selected above, a notification email will also be sent to this address for any new registrations. Leave empty to use the default system email address <em>(%site-email).</em>", array('%site-email' => $site_config->get('mail'))),
      '#maxlength' => 180,
    );

    $form['email'] = array(
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Emails'),
    );
    // These email tokens are shared for all settings, so just define
    // the list once to help ensure they stay in sync.
    $email_token_help = $this->t('Available variables are: [site:name], [site:url], [user:display-name], [user:account-name], [user:mail], [site:login-url], [site:url-brief], [user:edit-url], [user:one-time-login-url], [user:cancel-url].');

    $form['email_admin_created'] = array(
      '#type' => 'details',
      '#title' => $this->t('Welcome (new user created by administrator)'),
      '#open' => $config->get('register') == USER_REGISTER_ADMINISTRATORS_ONLY,
      '#description' => $this->t('Edit the welcome email messages sent to new member accounts created by an administrator.') . ' ' . $email_token_help,
      '#group' => 'email',
    );
    $form['email_admin_created']['user_mail_register_admin_created_subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $mail_config->get('register_admin_created.subject'),
      '#maxlength' => 180,
    );
    $form['email_admin_created']['user_mail_register_admin_created_body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' =>  $mail_config->get('register_admin_created.body'),
      '#rows' => 15,
    );

    $form['email_pending_approval'] = array(
      '#type' => 'details',
      '#title' => $this->t('Welcome (awaiting approval)'),
      '#open' => $config->get('register') == USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL,
      '#description' => $this->t('Edit the welcome email messages sent to new members upon registering, when administrative approval is required.') . ' ' . $email_token_help,
      '#group' => 'email',
    );
    $form['email_pending_approval']['user_mail_register_pending_approval_subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $mail_config->get('register_pending_approval.subject'),
      '#maxlength' => 180,
    );
    $form['email_pending_approval']['user_mail_register_pending_approval_body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $mail_config->get('register_pending_approval.body'),
      '#rows' => 8,
    );

    $form['email_pending_approval_admin'] = array(
      '#type' => 'details',
      '#title' => $this->t('Admin (user awaiting approval)'),
      '#open' => $config->get('register') == USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL,
      '#description' => $this->t('Edit the email notifying the site administrator that there are new members awaiting administrative approval.') . ' ' . $email_token_help,
      '#group' => 'email',
    );
    $form['email_pending_approval_admin']['register_pending_approval_admin_subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $mail_config->get('register_pending_approval_admin.subject'),
      '#maxlength' => 180,
    );
    $form['email_pending_approval_admin']['register_pending_approval_admin_body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $mail_config->get('register_pending_approval_admin.body'),
      '#rows' => 8,
    );

    $form['email_no_approval_required'] = array(
      '#type' => 'details',
      '#title' => $this->t('Welcome (no approval required)'),
      '#open' => $config->get('register') == USER_REGISTER_VISITORS,
      '#description' => $this->t('Edit the welcome email messages sent to new members upon registering, when no administrator approval is required.') . ' ' . $email_token_help,
      '#group' => 'email',
    );
    $form['email_no_approval_required']['user_mail_register_no_approval_required_subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $mail_config->get('register_no_approval_required.subject'),
      '#maxlength' => 180,
    );
    $form['email_no_approval_required']['user_mail_register_no_approval_required_body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $mail_config->get('register_no_approval_required.body'),
      '#rows' => 15,
    );

    $form['email_password_reset'] = array(
      '#type' => 'details',
      '#title' => $this->t('Password recovery'),
      '#description' => $this->t('Edit the email messages sent to users who request a new password.') . ' ' . $email_token_help,
      '#group' => 'email',
      '#weight' => 10,
    );
    $form['email_password_reset']['user_mail_password_reset_subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $mail_config->get('password_reset.subject'),
      '#maxlength' => 180,
    );
    $form['email_password_reset']['user_mail_password_reset_body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $mail_config->get('password_reset.body'),
      '#rows' => 12,
    );

    $form['email_activated'] = array(
      '#type' => 'details',
      '#title' => $this->t('Account activation'),
      '#description' => $this->t('Enable and edit email messages sent to users upon account activation (when an administrator activates an account of a user who has already registered, on a site where administrative approval is required).') . ' ' . $email_token_help,
      '#group' => 'email',
    );
    $form['email_activated']['user_mail_status_activated_notify'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user when account is activated'),
      '#default_value' => $config->get('notify.status_activated'),
    );
    $form['email_activated']['settings'] = array(
      '#type' => 'container',
      '#states' => array(
        // Hide the additional settings when this email is disabled.
        'invisible' => array(
          'input[name="user_mail_status_activated_notify"]' => array('checked' => FALSE),
        ),
      ),
    );
    $form['email_activated']['settings']['user_mail_status_activated_subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $mail_config->get('status_activated.subject'),
      '#maxlength' => 180,
    );
    $form['email_activated']['settings']['user_mail_status_activated_body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $mail_config->get('status_activated.body'),
      '#rows' => 15,
    );

    $form['email_blocked'] = array(
      '#type' => 'details',
      '#title' => $this->t('Account blocked'),
      '#description' => $this->t('Enable and edit email messages sent to users when their accounts are blocked.') . ' ' . $email_token_help,
      '#group' => 'email',
    );
    $form['email_blocked']['user_mail_status_blocked_notify'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user when account is blocked'),
      '#default_value' => $config->get('notify.status_blocked'),
    );
    $form['email_blocked']['settings'] = array(
      '#type' => 'container',
      '#states' => array(
        // Hide the additional settings when the blocked email is disabled.
        'invisible' => array(
          'input[name="user_mail_status_blocked_notify"]' => array('checked' => FALSE),
        ),
      ),
    );
    $form['email_blocked']['settings']['user_mail_status_blocked_subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $mail_config->get('status_blocked.subject'),
      '#maxlength' => 180,
    );
    $form['email_blocked']['settings']['user_mail_status_blocked_body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $mail_config->get('status_blocked.body'),
      '#rows' => 3,
    );

    $form['email_cancel_confirm'] = array(
      '#type' => 'details',
      '#title' => $this->t('Account cancellation confirmation'),
      '#description' => $this->t('Edit the email messages sent to users when they attempt to cancel their accounts.') . ' ' . $email_token_help,
      '#group' => 'email',
    );
    $form['email_cancel_confirm']['user_mail_cancel_confirm_subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $mail_config->get('cancel_confirm.subject'),
      '#maxlength' => 180,
    );
    $form['email_cancel_confirm']['user_mail_cancel_confirm_body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $mail_config->get('cancel_confirm.body'),
      '#rows' => 3,
    );

    $form['email_canceled'] = array(
      '#type' => 'details',
      '#title' => $this->t('Account canceled'),
      '#description' => $this->t('Enable and edit email messages sent to users when their accounts are canceled.') . ' ' . $email_token_help,
      '#group' => 'email',
    );
    $form['email_canceled']['user_mail_status_canceled_notify'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user when account is canceled'),
      '#default_value' => $config->get('notify.status_canceled'),
    );
    $form['email_canceled']['settings'] = array(
      '#type' => 'container',
      '#states' => array(
        // Hide the settings when the cancel notify checkbox is disabled.
        'invisible' => array(
          'input[name="user_mail_status_canceled_notify"]' => array('checked' => FALSE),
        ),
      ),
    );
    $form['email_canceled']['settings']['user_mail_status_canceled_subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $mail_config->get('status_canceled.subject'),
      '#maxlength' => 180,
    );
    $form['email_canceled']['settings']['user_mail_status_canceled_body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $mail_config->get('status_canceled.body'),
      '#rows' => 3,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('user.settings')
      ->set('anonymous', $form_state->getValue('anonymous'))
      ->set('register', $form_state->getValue('user_register'))
      ->set('password_strength', $form_state->getValue('user_password_strength'))
      ->set('verify_mail', $form_state->getValue('user_email_verification'))
      ->set('cancel_method', $form_state->getValue('user_cancel_method'))
      ->set('notify.status_activated', $form_state->getValue('user_mail_status_activated_notify'))
      ->set('notify.status_blocked', $form_state->getValue('user_mail_status_blocked_notify'))
      ->set('notify.status_canceled', $form_state->getValue('user_mail_status_canceled_notify'))
      ->save();
    $this->config('user.mail')
      ->set('cancel_confirm.body', $form_state->getValue('user_mail_cancel_confirm_body'))
      ->set('cancel_confirm.subject', $form_state->getValue('user_mail_cancel_confirm_subject'))
      ->set('password_reset.body', $form_state->getValue('user_mail_password_reset_body'))
      ->set('password_reset.subject', $form_state->getValue('user_mail_password_reset_subject'))
      ->set('register_admin_created.body', $form_state->getValue('user_mail_register_admin_created_body'))
      ->set('register_admin_created.subject', $form_state->getValue('user_mail_register_admin_created_subject'))
      ->set('register_no_approval_required.body', $form_state->getValue('user_mail_register_no_approval_required_body'))
      ->set('register_no_approval_required.subject', $form_state->getValue('user_mail_register_no_approval_required_subject'))
      ->set('register_pending_approval.body', $form_state->getValue('user_mail_register_pending_approval_body'))
      ->set('register_pending_approval.subject', $form_state->getValue('user_mail_register_pending_approval_subject'))
      ->set('register_pending_approval_admin.body', $form_state->getValue('register_pending_approval_admin_body'))
      ->set('register_pending_approval_admin.subject', $form_state->getValue('register_pending_approval_admin_subject'))
      ->set('status_activated.body', $form_state->getValue('user_mail_status_activated_body'))
      ->set('status_activated.subject', $form_state->getValue('user_mail_status_activated_subject'))
      ->set('status_blocked.body', $form_state->getValue('user_mail_status_blocked_body'))
      ->set('status_blocked.subject', $form_state->getValue('user_mail_status_blocked_subject'))
      ->set('status_canceled.body', $form_state->getValue('user_mail_status_canceled_body'))
      ->set('status_canceled.subject', $form_state->getValue('user_mail_status_canceled_subject'))
      ->save();
    $this->config('system.site')
      ->set('mail_notification', $form_state->getValue('mail_notification_address'))
      ->save();

    // Change the admin role.
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
