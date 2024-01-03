<?php

namespace Drupal\user;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure user settings for this site.
 *
 * @internal
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
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, ModuleHandlerInterface $module_handler, RoleStorageInterface $role_storage) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->moduleHandler = $module_handler;
    $this->roleStorage = $role_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('module_handler'),
      $container->get('entity_type.manager')->getStorage('user_role')
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
    $site_config = $this->config('system.site');

    $form['#attached']['library'][] = 'user/drupal.user.admin';

    // Settings for anonymous users.
    $form['anonymous_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Anonymous users'),
      '#open' => TRUE,
    ];
    $form['anonymous_settings']['anonymous'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#config_target' => 'user.settings:anonymous',
      '#description' => $this->t('The name used to indicate anonymous users.'),
      '#required' => TRUE,
    ];

    // @todo Remove this check once language settings are generalized.
    if ($this->moduleHandler->moduleExists('content_translation')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#open' => TRUE,
        '#tree' => TRUE,
      ];
      $form_state->set(['content_translation', 'key'], 'language');
      $form['language'] += content_translation_enable_widget('user', 'user', $form, $form_state);
    }

    // User registration settings.
    $form['registration_cancellation'] = [
      '#type' => 'details',
      '#title' => $this->t('Registration and cancellation'),
      '#open' => TRUE,
    ];
    $form['registration_cancellation']['user_register'] = [
      '#type' => 'radios',
      '#title' => $this->t('Who can register accounts?'),
      '#config_target' => 'user.settings:register',
      '#options' => [
        UserInterface::REGISTER_ADMINISTRATORS_ONLY => $this->t('Administrators only'),
        UserInterface::REGISTER_VISITORS => $this->t('Visitors'),
        UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL => $this->t('Visitors, but administrator approval is required'),
      ],
    ];
    $form['registration_cancellation']['user_email_verification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require email verification when a visitor creates an account'),
      '#config_target' => 'user.settings:verify_mail',
      '#description' => $this->t('New users will be required to validate their email address prior to logging into the site, and will be assigned a system-generated password. With this setting disabled, users will be logged in immediately upon registering, and may select their own passwords during registration.'),
    ];
    $form['registration_cancellation']['user_password_strength'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable password strength indicator'),
      '#config_target' => 'user.settings:password_strength',
    ];
    $form['registration_cancellation']['user_cancel_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('When cancelling a user account'),
      '#config_target' => 'user.settings:cancel_method',
      '#description' => $this->t('Users with the %select-cancel-method or %administer-users <a href=":permissions-url">permissions</a> can override this default method.', ['%select-cancel-method' => $this->t('Select method for cancelling account'), '%administer-users' => $this->t('Administer users'), ':permissions-url' => Url::fromRoute('user.admin_permissions')->toString()]),
    ];
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
    $form['mail_notification_address'] = [
      '#type' => 'email',
      '#title' => $this->t('Notification email address'),
      '#config_target' => 'system.site:mail_notification',
      '#description' => $this->t("The email address to be used as the 'from' address for all account notifications listed below. If <em>'Visitors, but administrator approval is required'</em> is selected above, a notification email will also be sent to this address for any new registrations. Leave empty to use the default system email address <em>(%site-email).</em>", ['%site-email' => $site_config->get('mail')]),
      '#maxlength' => 180,
    ];

    $form['email'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Emails'),
    ];
    // These email tokens are shared for all settings, so just define
    // the list once to help ensure they stay in sync.
    $email_token_help = $this->t('Available variables are: [site:name], [site:url], [user:display-name], [user:account-name], [user:mail], [site:login-url], [site:url-brief], [user:edit-url], [user:one-time-login-url], [user:cancel-url].');

    $form['email_admin_created'] = [
      '#type' => 'details',
      '#title' => $this->t('Welcome (new user created by administrator)'),
      '#open' => $config->get('register') == UserInterface::REGISTER_ADMINISTRATORS_ONLY,
      '#description' => $this->t('Edit the welcome email messages sent to new member accounts created by an administrator.') . ' ' . $email_token_help,
      '#group' => 'email',
    ];
    $form['email_admin_created']['user_mail_register_admin_created_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#config_target' => 'user.mail:register_admin_created.subject',
      '#required' => TRUE,
      '#maxlength' => 180,
    ];
    $form['email_admin_created']['user_mail_register_admin_created_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#config_target' => 'user.mail:register_admin_created.body',
      '#rows' => 15,
    ];

    $form['email_pending_approval'] = [
      '#type' => 'details',
      '#title' => $this->t('Welcome (awaiting approval)'),
      '#open' => $config->get('register') == UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL,
      '#description' => $this->t('Edit the welcome email messages sent to new members upon registering, when administrative approval is required.') . ' ' . $email_token_help,
      '#group' => 'email',
    ];
    $form['email_pending_approval']['user_mail_register_pending_approval_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#config_target' => 'user.mail:register_pending_approval.subject',
      '#required' => TRUE,
      '#maxlength' => 180,
    ];
    $form['email_pending_approval']['user_mail_register_pending_approval_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#config_target' => 'user.mail:register_pending_approval.body',
      '#rows' => 8,
    ];

    $form['email_pending_approval_admin'] = [
      '#type' => 'details',
      '#title' => $this->t('Admin (user awaiting approval)'),
      '#open' => $config->get('register') == UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL,
      '#description' => $this->t('Edit the email notifying the site administrator that there are new members awaiting administrative approval.') . ' ' . $email_token_help,
      '#group' => 'email',
    ];
    $form['email_pending_approval_admin']['register_pending_approval_admin_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#config_target' => 'user.mail:register_pending_approval_admin.subject',
      '#required' => TRUE,
      '#maxlength' => 180,
    ];
    $form['email_pending_approval_admin']['register_pending_approval_admin_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#config_target' => 'user.mail:register_pending_approval_admin.body',
      '#rows' => 8,
    ];

    $form['email_no_approval_required'] = [
      '#type' => 'details',
      '#title' => $this->t('Welcome (no approval required)'),
      '#open' => $config->get('register') == UserInterface::REGISTER_VISITORS,
      '#description' => $this->t('Edit the welcome email messages sent to new members upon registering, when no administrator approval is required.') . ' ' . $email_token_help,
      '#group' => 'email',
    ];
    $form['email_no_approval_required']['user_mail_register_no_approval_required_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#config_target' => 'user.mail:register_no_approval_required.subject',
      '#required' => TRUE,
      '#maxlength' => 180,
    ];
    $form['email_no_approval_required']['user_mail_register_no_approval_required_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#config_target' => 'user.mail:register_no_approval_required.body',
      '#rows' => 15,
    ];

    $form['email_password_reset'] = [
      '#type' => 'details',
      '#title' => $this->t('Password recovery'),
      '#description' => $this->t('Edit the email messages sent to users who request a new password.') . ' ' . $email_token_help,
      '#group' => 'email',
      '#weight' => 10,
    ];
    $form['email_password_reset']['user_mail_password_reset_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#config_target' => 'user.mail:password_reset.subject',
      '#required' => TRUE,
      '#maxlength' => 180,
    ];
    $form['email_password_reset']['user_mail_password_reset_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#config_target' => 'user.mail:password_reset.body',
      '#rows' => 12,
    ];

    $form['email_activated'] = [
      '#type' => 'details',
      '#title' => $this->t('Account activation'),
      '#description' => $this->t('Enable and edit email messages sent to users upon account activation (when an administrator activates an account of a user who has already registered, on a site where administrative approval is required).') . ' ' . $email_token_help,
      '#group' => 'email',
    ];
    $form['email_activated']['user_mail_status_activated_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user when account is activated'),
      '#config_target' => 'user.settings:notify.status_activated',
    ];
    $form['email_activated']['settings'] = [
      '#type' => 'container',
      '#states' => [
        // Hide the additional settings when this email is disabled.
        'invisible' => [
          'input[name="user_mail_status_activated_notify"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['email_activated']['settings']['user_mail_status_activated_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#config_target' => 'user.mail:status_activated.subject',
      '#states' => [
        'required' => [
          'input[name="user_mail_status_activated_notify"]' => ['checked' => TRUE],
        ],
      ],
      '#maxlength' => 180,
    ];
    $form['email_activated']['settings']['user_mail_status_activated_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#config_target' => 'user.mail:status_activated.body',
      '#rows' => 15,
    ];

    $form['email_blocked'] = [
      '#type' => 'details',
      '#title' => $this->t('Account blocked'),
      '#description' => $this->t('Enable and edit email messages sent to users when their accounts are blocked.') . ' ' . $email_token_help,
      '#group' => 'email',
    ];
    $form['email_blocked']['user_mail_status_blocked_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user when account is blocked'),
      '#config_target' => 'user.settings:notify.status_blocked',
    ];
    $form['email_blocked']['settings'] = [
      '#type' => 'container',
      '#states' => [
        // Hide the additional settings when the blocked email is disabled.
        'invisible' => [
          'input[name="user_mail_status_blocked_notify"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['email_blocked']['settings']['user_mail_status_blocked_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#config_target' => 'user.mail:status_blocked.subject',
      '#states' => [
        'required' => [
          'input[name="user_mail_status_blocked_notify"]' => ['checked' => TRUE],
        ],
      ],
      '#maxlength' => 180,
    ];
    $form['email_blocked']['settings']['user_mail_status_blocked_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#config_target' => 'user.mail:status_blocked.body',
      '#rows' => 3,
    ];

    $form['email_cancel_confirm'] = [
      '#type' => 'details',
      '#title' => $this->t('Account cancellation confirmation'),
      '#description' => $this->t('Edit the email messages sent to users when they attempt to cancel their accounts.') . ' ' . $email_token_help,
      '#group' => 'email',
    ];
    $form['email_cancel_confirm']['user_mail_cancel_confirm_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#config_target' => 'user.mail:cancel_confirm.subject',
      '#required' => TRUE,
      '#maxlength' => 180,
    ];
    $form['email_cancel_confirm']['user_mail_cancel_confirm_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#config_target' => 'user.mail:cancel_confirm.body',
      '#rows' => 3,
    ];

    $form['email_canceled'] = [
      '#type' => 'details',
      '#title' => $this->t('Account canceled'),
      '#description' => $this->t('Enable and edit email messages sent to users when their accounts are canceled.') . ' ' . $email_token_help,
      '#group' => 'email',
    ];
    $form['email_canceled']['user_mail_status_canceled_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user when account is canceled'),
      '#config_target' => 'user.settings:notify.status_canceled',
    ];
    $form['email_canceled']['settings'] = [
      '#type' => 'container',
      '#states' => [
        // Hide the settings when the cancel notify checkbox is disabled.
        'invisible' => [
          'input[name="user_mail_status_canceled_notify"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['email_canceled']['settings']['user_mail_status_canceled_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#config_target' => 'user.mail:status_canceled.subject',
      '#states' => [
        'required' => [
          'input[name="user_mail_status_canceled_subject"]' => ['checked' => TRUE],
        ],
      ],
      '#maxlength' => 180,
    ];
    $form['email_canceled']['settings']['user_mail_status_canceled_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#config_target' => 'user.mail:status_canceled.body',
      '#rows' => 3,
    ];

    return $form;
  }

}
