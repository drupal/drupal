<?php

namespace Drupal\update;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure update settings for this site.
 *
 * @internal
 */
class UpdateSettingsForm extends ConfigFormBase {

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
    $form['update_check_frequency'] = [
      '#type' => 'radios',
      '#title' => $this->t('Check for updates'),
      '#config_target' => 'update.settings:check.interval_days',
      '#options' => [
        '1' => $this->t('Daily'),
        '7' => $this->t('Weekly'),
      ],
      '#description' => $this->t('Select how frequently you want to automatically check for new releases of your currently installed modules and themes.'),
    ];

    $form['update_check_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check for updates of uninstalled modules and themes'),
      '#config_target' => 'update.settings:check.disabled_extensions',
    ];

    $form['update_notify_emails'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email addresses to notify when updates are available'),
      '#rows' => 4,
      '#config_target' => new ConfigTarget(
        'update.settings',
        'notification.emails',
        static::class . '::arrayToMultiLineString',
        static::class . '::multiLineStringToArray',
      ),
      '#description' => $this->t('Whenever your site checks for available updates and finds new releases, it can notify a list of users via email. Put each address on a separate line. If blank, no emails will be sent.'),
    ];

    $form['update_notification_threshold'] = [
      '#type' => 'radios',
      '#title' => $this->t('Email notification threshold'),
      '#config_target' => 'update.settings:notification.threshold',
      '#options' => [
        'all' => $this->t('All newer versions'),
        'security' => $this->t('Only security updates'),
      ],
      '#description' => $this->t(
        'You can choose to send email only if a security update is available, or to be notified about all newer versions. If there are updates available of Drupal core or any of your installed modules and themes, your site will always print a message on the <a href=":status_report">status report</a> page. If there is a security update, an error message will be printed on administration pages for users with <a href=":update_permissions">permission to view update notifications</a>.',
        [
          ':status_report' => Url::fromRoute('system.status')->toString(),
          ':update_permissions' => Url::fromRoute('user.admin_permissions', [], ['fragment' => 'module-update'])
            ->toString(),
        ]
      ),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatMultipleViolationsMessage(string $form_element_name, array $violations): TranslatableMarkup {
    if ($form_element_name !== 'update_notify_emails') {
      return parent::formatMultipleViolationsMessage($form_element_name, $violations);
    }

    $invalid_email_addresses = [];
    foreach ($violations as $violation) {
      $invalid_email_addresses[] = $violation->getInvalidValue();
    }
    return $this->t('%emails are not valid email addresses.', ['%emails' => implode(', ', $invalid_email_addresses)]);
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

    parent::submitForm($form, $form_state);
  }

  /**
   * Prepares the submitted value to be stored in the notify_emails property.
   *
   * @param string $value
   *   The submitted value.
   *
   * @return array
   *   The value to be stored in config.
   */
  public static function multiLineStringToArray(string $value): array {
    return array_map('trim', explode("\n", trim($value)));
  }

  /**
   * Prepares the saved notify_emails property to be displayed in the form.
   *
   * @param array $value
   *   The value saved in config.
   *
   * @return string
   *   The value of the form element.
   */
  public static function arrayToMultiLineString(array $value): string {
    return implode("\n", $value);
  }

}
