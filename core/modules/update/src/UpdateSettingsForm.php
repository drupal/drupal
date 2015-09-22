<?php

/**
 * @file
 * Contains \Drupal\update\UpdateSettingsForm.
 */

namespace Drupal\update;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Egulias\EmailValidator\EmailValidator;

/**
 * Configure update settings for this site.
 */
class UpdateSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * Constructs a new UpdateSettingsForm.
   *
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   */
  public function __construct(EmailValidator $email_validator) {
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
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

    $form['update_check_frequency'] = array(
      '#type' => 'radios',
      '#title' => t('Check for updates'),
      '#default_value' => $config->get('check.interval_days'),
      '#options' => array(
        '1' => t('Daily'),
        '7' => t('Weekly'),
      ),
      '#description' => t('Select how frequently you want to automatically check for new releases of your currently installed modules and themes.'),
    );

    $form['update_check_disabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Check for updates of uninstalled modules and themes'),
      '#default_value' => $config->get('check.disabled_extensions'),
    );

    $notification_emails = $config->get('notification.emails');
    $form['update_notify_emails'] = array(
      '#type' => 'textarea',
      '#title' => t('Email addresses to notify when updates are available'),
      '#rows' => 4,
      '#default_value' => implode("\n", $notification_emails),
      '#description' => t('Whenever your site checks for available updates and finds new releases, it can notify a list of users via email. Put each address on a separate line. If blank, no emails will be sent.'),
    );

    $form['update_notification_threshold'] = array(
      '#type' => 'radios',
      '#title' => t('Email notification threshold'),
      '#default_value' => $config->get('notification.threshold'),
      '#options' => array(
        'all' => t('All newer versions'),
        'security' => t('Only security updates'),
      ),
      '#description' => t('You can choose to send email only if a security update is available, or to be notified about all newer versions. If there are updates available of Drupal core or any of your installed modules and themes, your site will always print a message on the <a href=":status_report">status report</a> page, and will also display an error message on administration pages if there is a security update.', array(':status_report' => $this->url('system.status')))
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->set('notify_emails', []);
    if (!$form_state->isValueEmpty('update_notify_emails')) {
      $valid = array();
      $invalid = array();
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
        $form_state->setErrorByName('update_notify_emails', $this->t('%email is not a valid email address.', array('%email' => reset($invalid))));
      }
      else {
        $form_state->setErrorByName('update_notify_emails', $this->t('%emails are not valid email addresses.', array('%emails' => implode(', ', $invalid))));
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
