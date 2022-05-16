<?php

namespace Drupal\update;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\EmailValidatorInterface;

/**
 * Configure update settings for this site.
 *
 * @internal
 */
class UpdateSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The email validator.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * Constructs an UpdateSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EmailValidatorInterface $email_validator) {
    parent::__construct($config_factory);
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['update.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('update.settings');

    $form['update_check_frequency'] = [
      '#type' => 'radios',
      '#title' => $this->t('Check for updates'),
      '#default_value' => $config->get('check.interval_days'),
      '#options' => [
        '1' => $this->t('Daily'),
        '7' => $this->t('Weekly'),
      ],
      '#description' => $this->t('Select how frequently you want to automatically check for new releases of your currently installed modules and themes.'),
    ];

    $form['update_check_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check for updates of uninstalled modules and themes'),
      '#default_value' => $config->get('check.disabled_extensions'),
    ];

    $notification_emails = $config->get('notification.emails');
    $form['update_notify_emails'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email addresses to notify when updates are available'),
      '#rows' => 4,
      '#default_value' => implode("\n", $notification_emails),
      '#description' => $this->t('Whenever your site checks for available updates and finds new releases, it can notify a list of users via email. Put each address on a separate line. If blank, no emails will be sent.'),
    ];

    $form['update_notification_threshold'] = [
      '#type' => 'radios',
      '#title' => $this->t('Email notification threshold'),
      '#default_value' => $config->get('notification.threshold'),
      '#options' => [
        'all' => $this->t('All newer versions'),
        'security' => $this->t('Only security updates'),
      ],
      '#description' => $this->t(
        'You can choose to send email only if a security update is available, or to be notified about all newer versions. If there are updates available of Drupal core or any of your installed modules and themes, your site will always print a message on the <a href=":status_report">status report</a> page. If there is a security update, an error message will be printed on administration pages for users with <a href=":update_permissions">permission to view update notifications</a>.',
        [
          ':status_report' => Url::fromRoute('system.status')->toString(),
          ':update_permissions' => Url::fromRoute('user.admin_permissions', [], ['fragment' => 'module-update'])->toString(),
        ]
      ),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->set('notify_emails', []);
    if (!$form_state->isValueEmpty('update_notify_emails')) {
      $valid = [];
      $invalid = [];
      foreach (explode("\n", trim($form_state->getValue('update_notify_emails'))) as $email) {
        $email = trim($email);
        if (!empty($email)) {
          if ($this->emailValidator->isValid($email)) {
            $valid[] = $email;
          }
          else {
            $invalid[] = $email;
          }
        }
      }
      if (empty($invalid)) {
        $form_state->set('notify_emails', $valid);
      }
      elseif (count($invalid) == 1) {
        $form_state->setErrorByName('update_notify_emails', $this->t('%email is not a valid email address.', ['%email' => reset($invalid)]));
      }
      else {
        $form_state->setErrorByName('update_notify_emails', $this->t('%emails are not valid email addresses.', ['%emails' => implode(', ', $invalid)]));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('update.settings');
    // See if the update_check_disabled setting is being changed, and if so,
    // invalidate all update status data.
    if ($form_state->getValue('update_check_disabled') != $config->get('check.disabled_extensions')) {
      update_storage_clear();
    }

    $config
      ->set('check.disabled_extensions', $form_state->getValue('update_check_disabled'))
      ->set('check.interval_days', $form_state->getValue('update_check_frequency'))
      ->set('notification.emails', $form_state->get('notify_emails'))
      ->set('notification.threshold', $form_state->getValue('update_notification_threshold'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
