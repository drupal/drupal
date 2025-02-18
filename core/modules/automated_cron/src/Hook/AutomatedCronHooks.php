<?php

namespace Drupal\automated_cron\Hook;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for automated_cron.
 */
class AutomatedCronHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.automated_cron':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Automated Cron module runs cron operations for your site using normal browser/page requests instead of having to set up a separate cron job. The Automated Cron module checks at the end of each server response when cron operation was last ran and, if it has been too long since last run, it executes the cron tasks after sending a server response. For more information, see the <a href=":automated_cron-documentation">online documentation for the Automated Cron module</a>.', [
          ':automated_cron-documentation' => 'https://www.drupal.org/documentation/modules/automated_cron',
        ]) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Configuring Automated Cron') . '</dt>';
        $output .= '<dd>' . $this->t('On the <a href=":cron-settings">Cron page</a>, you can set the frequency (time interval) for running cron jobs.', [
          ':cron-settings' => Url::fromRoute('system.cron_settings')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Disabling Automated Cron') . '</dt>';
        $output .= '<dd>' . $this->t('To disable automated cron, the recommended method is to uninstall the module, to reduce site overhead. If you only want to disable it temporarily, you can set the frequency to Never on the Cron page, and then change the frequency back when you want to start it up again.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_form_FORM_ID_alter() for the system_cron_settings() form.
   */
  #[Hook('form_system_cron_settings_alter')]
  public function formSystemCronSettingsAlter(&$form, &$form_state) : void {
    $automated_cron_settings = \Drupal::config('automated_cron.settings');
    $options = [3600, 10800, 21600, 43200, 86400, 604800];
    $form['cron']['interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Run cron every'),
      '#description' => $this->t('More information about setting up scheduled tasks can be found by <a href=":url">reading the cron tutorial on drupal.org</a>.', [
        ':url' => 'https://www.drupal.org/docs/8/administering-a-drupal-8-site/cron-automated-tasks',
      ]),
      '#default_value' => $automated_cron_settings->get('interval'),
      '#options' => [
        0 => $this->t('Never'),
      ] + array_map([
        \Drupal::service('date.formatter'),
        'formatInterval',
      ], array_combine($options, $options)),
    ];
    // Add submit callback.
    $form['#submit'][] = 'automated_cron_settings_submit';
    // Theme this form as a config form.
    $form['#theme'] = 'system_config_form';
  }

}
