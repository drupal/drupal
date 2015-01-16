<?php

/**
 * @file
 * Contains \Drupal\Core\Installer\Form\SiteConfigureForm.
 */

namespace Drupal\Core\Installer\Form;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the site configuration form.
 */
class SiteConfigureForm extends ConfigFormBase {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

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
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *   The country manager.
   */
  public function __construct($root, UserStorageInterface $user_storage, StateInterface $state, ModuleInstallerInterface $module_installer, CountryManagerInterface $country_manager) {
    $this->root = $root;
    $this->userStorage = $user_storage;
    $this->state = $state;
    $this->moduleInstaller = $module_installer;
    $this->countryManager = $country_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('app.root'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('state'),
      $container->get('module_installer'),
      $container->get('country_manager')
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
    $form['#title'] = $this->t('Configure site');

    // Warn about settings.php permissions risk
    $settings_dir = conf_path();
    $settings_file = $settings_dir . '/settings.php';
    // Check that $_POST is empty so we only show this message when the form is
    // first displayed, not on the next page after it is submitted. (We do not
    // want to repeat it multiple times because it is a general warning that is
    // not related to the rest of the installation process; it would also be
    // especially out of place on the last page of the installer, where it would
    // distract from the message that the Drupal installation has completed
    // successfully.)
    $post_params = $this->getRequest()->request->all();
    if (empty($post_params) && (!drupal_verify_install_file($this->root . '/' . $settings_file, FILE_EXIST|FILE_READABLE|FILE_NOT_WRITABLE) || !drupal_verify_install_file($this->root . '/' . $settings_dir, FILE_NOT_WRITABLE, 'dir'))) {
      drupal_set_message(t('All necessary changes to %dir and %file have been made, so you should remove write permissions to them now in order to avoid security risks. If you are unsure how to do so, consult the <a href="@handbook_url">online handbook</a>.', array('%dir' => $settings_dir, '%file' => $settings_file, '@handbook_url' => 'http://drupal.org/server-permissions')), 'warning');
    }

    $form['#attached']['library'][] = 'system/drupal.system';
    // Add JavaScript time zone detection.
    $form['#attached']['library'][] = 'core/drupal.timezone';
    // We add these strings as settings because JavaScript translation does not
    // work during installation.
    $form['#attached']['drupalSettings']['copyFieldValue']['edit-site-mail'] = ['edit-account-mail'];

    // Cache a fully-built schema. This is necessary for any invocation of
    // index.php because: (1) setting cache table entries requires schema
    // information, (2) that occurs during bootstrap before any module are
    // loaded, so (3) if there is no cached schema, drupal_get_schema() will
    // try to generate one but with no loaded modules will return nothing.
    //
    // @todo Move this to the 'install_finished' task?
    drupal_get_schema(NULL, TRUE);

    $form['site_information'] = array(
      '#type' => 'fieldgroup',
      '#title' => $this->t('Site information'),
    );
    $form['site_information']['site_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Site name'),
      '#required' => TRUE,
      '#weight' => -20,
    );
    $form['site_information']['site_mail'] = array(
      '#type' => 'email',
      '#title' => $this->t('Site email address'),
      '#default_value' => ini_get('sendmail_from'),
      '#description' => $this->t("Automated emails, such as registration information, will be sent from this address. Use an address ending in your site's domain to help prevent these emails from being flagged as spam."),
      '#required' => TRUE,
      '#weight' => -15,
    );

    $form['admin_account'] = array(
      '#type' => 'fieldgroup',
      '#title' => $this->t('Site maintenance account'),
    );
    $form['admin_account']['account']['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#description' => $this->t('Spaces are allowed; punctuation is not allowed except for periods, hyphens, and underscores.'),
      '#required' => TRUE,
      '#attributes' => array('class' => array('username')),
    );
    $form['admin_account']['account']['pass'] = array(
      '#type' => 'password_confirm',
      '#required' => TRUE,
      '#size' => 25,
    );
    $form['admin_account']['account']['#tree'] = TRUE;
    $form['admin_account']['account']['mail'] = array(
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
    );

    $form['regional_settings'] = array(
      '#type' => 'fieldgroup',
      '#title' => $this->t('Regional settings'),
    );
    $countries = $this->countryManager->getList();
    $form['regional_settings']['site_default_country'] = array(
      '#type' => 'select',
      '#title' => $this->t('Default country'),
      '#empty_value' => '',
      '#default_value' => $this->config('system.date')->get('country.default'),
      '#options' => $countries,
      '#description' => $this->t('Select the default country for the site.'),
      '#weight' => 0,
    );
    $form['regional_settings']['date_default_timezone'] = array(
      '#type' => 'select',
      '#title' => $this->t('Default time zone'),
      '#default_value' => date_default_timezone_get(),
      '#options' => system_time_zones(),
      '#description' => $this->t('By default, dates in this site will be displayed in the chosen time zone.'),
      '#weight' => 5,
      '#attributes' => array('class' => array('timezone-detect')),
    );

    $form['update_notifications'] = array(
      '#type' => 'fieldgroup',
      '#title' => $this->t('Update notifications'),
    );
    $form['update_notifications']['update_status_module'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Update notifications'),
      '#options' => array(
        1 => $this->t('Check for updates automatically'),
        2 => $this->t('Receive email notifications'),
      ),
      '#default_value' => array(1, 2),
      '#description' => $this->t('The system will notify you when updates and important security releases are available for installed components. Anonymous information about your site is sent to <a href="@drupal">Drupal.org</a>.', array('@drupal' => 'http://drupal.org')),
      '#weight' => 15,
    );
    $form['update_notifications']['update_status_module'][2] = array(
      '#states' => array(
        'visible' => array(
          'input[name="update_status_module[1]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#weight' => 15,
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($error = user_validate_name($form_state->getValue(array('account', 'name')))) {
      $form_state->setErrorByName('account][name', $error);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('system.site')
      ->set('name', $form_state->getValue('site_name'))
      ->set('mail', $form_state->getValue('site_mail'))
      ->save();

    $this->config('system.date')
      ->set('timezone.default', $form_state->getValue('date_default_timezone'))
      ->set('country.default', $form_state->getValue('site_default_country'))
      ->save();

    $account_values = $form_state->getValue('account');

    // Enable update.module if this option was selected.
    $update_status_module = $form_state->getValue('update_status_module');
    if ($update_status_module[1]) {
      $this->moduleInstaller->install(array('file', 'update'), FALSE);

      // Add the site maintenance account's email address to the list of
      // addresses to be notified when updates are available, if selected.
      if ($update_status_module[2]) {
        // Reset the configuration factory so it is updated with the new module.
        $this->resetConfigFactory();
        $this->config('update.settings')->set('notification.emails', array($account_values['mail']))->save();
      }
    }

    // We precreated user 1 with placeholder values. Let's save the real values.
    $account = $this->userStorage->load(1);
    $account->init = $account->mail = $account_values['mail'];
    $account->roles = $account->getRoles();
    $account->activate();
    $account->timezone = $form_state->getValue('date_default_timezone');
    $account->pass = $account_values['pass'];
    $account->name = $account_values['name'];
    $account->save();

    // Record when this install ran.
    $this->state->set('install_time', $_SERVER['REQUEST_TIME']);
  }

}
