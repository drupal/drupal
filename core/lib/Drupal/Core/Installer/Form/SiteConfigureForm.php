<?php

namespace Drupal\Core\Installer\Form;

use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Drupal\user\UserNameValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the site configuration form.
 *
 * @internal
 */
class SiteConfigureForm extends ConfigFormBase {

  use DeprecatedServicePropertyTrait;

  /**
   * Defines deprecated injected properties.
   *
   * @var array
   */
  protected array $deprecatedProperties = [
    'countryManager' => 'country_manager',
  ];

  /**
   * The site path.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Constructs a new SiteConfigureForm.
   *
   * @param string $root
   *   The app root.
   * @param string $site_path
   *   The site path.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface|\Drupal\user\UserStorageInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   * @param \Drupal\Core\Locale\CountryManagerInterface|\Drupal\user\UserNameValidator $userNameValidator
   *   The user validator.
   * @param bool|null $superUserAccessPolicy
   *   The value of the 'security.enable_super_user' container parameter.
   */
  public function __construct(
    $root,
    $site_path,
    protected EntityTypeManagerInterface|UserStorageInterface $entityTypeManager,
    ModuleInstallerInterface $module_installer,
    protected CountryManagerInterface|UserNameValidator $userNameValidator,
    protected ?bool $superUserAccessPolicy = NULL,
  ) {
    $this->root = $root;
    $this->sitePath = $site_path;
    if ($this->entityTypeManager instanceof UserStorageInterface) {
      @trigger_error('Calling ' . __METHOD__ . '() with the $entityTypeManager argument as UserStorageInterface is deprecated in drupal:10.3.0 and must be EntityTypeManagerInterface in drupal:11.0.0. See https://www.drupal.org/node/3443172', E_USER_DEPRECATED);
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }
    $this->userStorage = $this->entityTypeManager->getStorage('user');
    $this->moduleInstaller = $module_installer;
    if ($userNameValidator instanceof CountryManagerInterface) {
      @trigger_error('Calling ' . __METHOD__ . '() with the $userNameValidator argument as CountryManagerInterface is deprecated in drupal:10.3.0 and must be UserNameValidator in drupal:11.0.0. See https://www.drupal.org/node/3431205', E_USER_DEPRECATED);
      $this->userNameValidator = \Drupal::service('user.name_validator');
    }
    if ($this->superUserAccessPolicy === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $superUserAccessPolicy argument is deprecated in drupal:10.3.0 and must be passed in drupal:11.0.0. See https://www.drupal.org/node/3443172', E_USER_DEPRECATED);
      $this->superUserAccessPolicy = \Drupal::getContainer()->getParameter('security.enable_super_user') ?? TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->getParameter('app.root'),
      $container->getParameter('site.path'),
      $container->get('entity_type.manager'),
      $container->get('module_installer'),
      $container->get('user.name_validator'),
      // In order to disable the super user policy this must be set to FALSE. If
      // the container parameter is missing then the policy is enabled. See
      // \Drupal\Core\DependencyInjection\Compiler\SuperUserAccessPolicyPass.
      $container->getParameter('security.enable_super_user') ?? TRUE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'install_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'system.date',
      'system.site',
      'update.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $install_state;
    $form['#title'] = $this->t('Configure site');

    // Warn about settings.php permissions risk
    $settings_dir = $this->sitePath;
    $settings_file = $settings_dir . '/settings.php';
    // Check that $_POST is empty so we only show this message when the form is
    // first displayed, not on the next page after it is submitted. (We do not
    // want to repeat it multiple times because it is a general warning that is
    // not related to the rest of the installation process; it would also be
    // especially out of place on the last page of the installer, where it would
    // distract from the message that the Drupal installation has completed
    // successfully.)
    $post_params = $this->getRequest()->request->all();
    if (empty($post_params) && (Settings::get('skip_permissions_hardening') || !drupal_verify_install_file($this->root . '/' . $settings_file, FILE_EXIST | FILE_READABLE | FILE_NOT_WRITABLE) || !drupal_verify_install_file($this->root . '/' . $settings_dir, FILE_NOT_WRITABLE, 'dir'))) {
      $this->messenger()->addWarning($this->t('All necessary changes to %dir and %file have been made, so you should remove write permissions to them now in order to avoid security risks. If you are unsure how to do so, consult the <a href=":handbook_url">online handbook</a>.', ['%dir' => $settings_dir, '%file' => $settings_file, ':handbook_url' => 'https://www.drupal.org/server-permissions']));
    }

    $form['#attached']['library'][] = 'system/drupal.system';
    // Add JavaScript time zone detection.
    $form['#attached']['library'][] = 'core/drupal.timezone';
    // We add these strings as settings because JavaScript translation does not
    // work during installation.
    $form['#attached']['drupalSettings']['copyFieldValue']['edit-site-mail'] = ['edit-account-mail'];

    $form['site_information'] = [
      '#type' => 'fieldgroup',
      '#title' => $this->t('Site information'),
      '#access' => empty($install_state['config_install_path']),
    ];
    $form['site_information']['site_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site name'),
      '#required' => TRUE,
      '#weight' => -20,
      '#access' => empty($install_state['config_install_path']),
    ];
    // Use the default site mail if one is already configured, or fall back to
    // PHP's configured sendmail_from.
    $default_site_mail = $this->config('system.site')->get('mail') ?: ini_get('sendmail_from');
    $form['site_information']['site_mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Site email address'),
      '#default_value' => $default_site_mail,
      '#description' => $this->t("Automated emails, such as registration information, will be sent from this address. Use an address ending in your site's domain to help prevent these emails from being flagged as spam."),
      '#required' => TRUE,
      '#weight' => -15,
      '#access' => empty($install_state['config_install_path']),
    ];

    if (count($this->getAdminRoles()) === 0 && $this->superUserAccessPolicy === FALSE) {
      $account_label = $this->t('Site account');
    }
    else {
      $account_label = $this->t('Site maintenance account');
    }

    $form['admin_account'] = [
      '#type' => 'fieldgroup',
      '#title' => $account_label,
    ];
    $form['admin_account']['account']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
      '#description' => $this->t("Several special characters are allowed, including space, period (.), hyphen (-), apostrophe ('), underscore (_), and the @ sign."),
      '#required' => TRUE,
      '#attributes' => ['class' => ['username']],
    ];
    $form['admin_account']['account']['pass'] = [
      '#type' => 'password_confirm',
      '#required' => TRUE,
      '#size' => 25,
    ];
    $form['admin_account']['account']['#tree'] = TRUE;
    $form['admin_account']['account']['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
    ];

    $form['regional_settings'] = [
      '#type' => 'fieldgroup',
      '#title' => $this->t('Regional settings'),
      '#access' => empty($install_state['config_install_path']),
    ];
    // Use the default site timezone if one is already configured, or fall back
    // to the system timezone if set (and avoid throwing a warning in
    // PHP >=5.4).
    $default_timezone = $this->config('system.date')->get('timezone.default') ?: @date_default_timezone_get();
    $form['regional_settings']['date_default_timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Default time zone'),
      '#default_value' => $default_timezone,
      '#options' => TimeZoneFormHelper::getOptionsListByRegion(),
      '#weight' => 5,
      '#attributes' => ['class' => ['timezone-detect']],
      '#access' => empty($install_state['config_install_path']),
    ];

    $form['update_notifications'] = [
      '#type' => 'fieldgroup',
      '#title' => $this->t('Update notifications'),
      '#description' => $this->t('When checking for updates, your site automatically sends anonymous information to Drupal.org. See the <a href="@update-module-docs" target="_blank">Update module documentation</a> for details.', ['@update-module-docs' => 'https://www.drupal.org/node/178772']),
      '#access' => empty($install_state['config_install_path']),
    ];
    $form['update_notifications']['enable_update_status_module'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check for updates automatically'),
      '#default_value' => 1,
      '#access' => empty($install_state['config_install_path']),
    ];
    $form['update_notifications']['enable_update_status_emails'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Receive email notifications'),
      '#default_value' => 1,
      '#states' => [
        'visible' => [
          'input[name="enable_update_status_module"]' => ['checked' => TRUE],
        ],
      ],
      '#access' => empty($install_state['config_install_path']),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#weight' => 15,
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $violations = $this->userNameValidator->validateName($form_state->getValue(['account', 'name']));
    if ($violations->count() > 0) {
      $form_state->setErrorByName('account][name', $violations[0]->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $install_state;

    if (empty($install_state['config_install_path'])) {
      $this->config('system.site')
        ->set('name', (string) $form_state->getValue('site_name'))
        ->set('mail', (string) $form_state->getValue('site_mail'))
        ->save(TRUE);

      $this->config('system.date')
        ->set('timezone.default', (string) $form_state->getValue('date_default_timezone'))
        ->save(TRUE);
    }

    $account_values = $form_state->getValue('account');

    // Enable update.module if this option was selected.
    $update_status_module = $form_state->getValue('enable_update_status_module');
    if (empty($install_state['config_install_path']) && $update_status_module) {
      $this->moduleInstaller->install(['update']);

      // Add the site maintenance account's email address to the list of
      // addresses to be notified when updates are available, if selected.
      $email_update_status_emails = $form_state->getValue('enable_update_status_emails');
      if ($email_update_status_emails) {
        // Reset the configuration factory so it is updated with the new module.
        $this->resetConfigFactory();
        $this->config('update.settings')->set('notification.emails', [$account_values['mail']])->save(TRUE);
      }
    }

    // We created user 1 with placeholder values. Let's save the real values.
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->userStorage->load(1);
    $account->init = $account->mail = $account_values['mail'];
    $account->roles = $account->getRoles();
    $account->activate();
    $account->timezone = $form_state->getValue('date_default_timezone');
    $account->pass = $account_values['pass'];
    $account->name = $account_values['name'];

    // Ensure user 1 has an administrator role if one exists.
    /** @var \Drupal\user\RoleInterface[] $admin_roles */
    $admin_roles = $this->getAdminRoles();
    if (count(array_intersect($account->getRoles(), array_keys($admin_roles))) === 0) {
      if (count($admin_roles) > 0) {
        foreach ($admin_roles as $role) {
          $account->addRole($role->id());
        }
      }
      elseif ($this->superUserAccessPolicy === FALSE) {
        $this->messenger()->addWarning($this->t(
          'The user %username does not have administrator access. For more information, see the documentation on <a href="@secure-user-1-docs">securing the admin super user</a>.',
          [
            '%username' => $account->getDisplayName(),
            '@secure-user-1-docs' => 'https://www.drupal.org/docs/administering-a-drupal-site/security-in-drupal/securing-the-admin-super-user-1#s-disable-the-super-user-access-policy',
          ]
        ));
      }
    }

    $account->save();
  }

  /**
   * Returns the list of admin roles.
   *
   * @return \Drupal\user\RoleInterface[]
   *   The list of admin roles.
   */
  protected function getAdminRoles(): array {
    return $this->entityTypeManager->getStorage('user_role')->loadByProperties(['is_admin' => TRUE]);
  }

}
