<?php

namespace Drupal\syslog\Hook;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for syslog.
 */
class SyslogHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.syslog':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Syslog module logs events by sending messages to the logging facility of your web server\'s operating system. Syslog is an operating system administrative logging tool that provides valuable information for use in system management and security auditing. Most suited to medium and large sites, Syslog provides filtering tools that allow messages to be routed by type and severity. For more information, see the <a href=":syslog">online documentation for the Syslog module</a>, as well as PHP\'s documentation pages for the <a href="http://php.net/manual/function.openlog.php">openlog</a> and <a href="http://php.net/manual/function.syslog.php">syslog</a> functions.', [':syslog' => 'https://www.drupal.org/documentation/modules/syslog']) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Logging for UNIX, Linux, and Mac OS X') . '</dt>';
        $output .= '<dd>' . $this->t('On UNIX, Linux, and Mac OS X, you will find the configuration in the file <em>/etc/syslog.conf</em>, or in <em>/etc/rsyslog.conf</em> or in the directory <em>/etc/rsyslog.d</em>. These files define the routing configuration. Messages can be flagged with the codes <code>LOG_LOCAL0</code> through <code>LOG_LOCAL7</code>. For information on Syslog facilities, severity levels, and how to set up <em>syslog.conf</em> or <em>rsyslog.conf</em>, see the <em>syslog.conf</em> or <em>rsyslog.conf</em> manual page on your command line.') . '</dd>';
        $output .= '<dt>' . $this->t('Logging for Microsoft Windows') . '</dt>';
        $output .= '<dd>' . $this->t('On Microsoft Windows, messages are always sent to the Event Log using the code <code>LOG_USER</code>.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_system_logging_settings_alter')]
  public function formSystemLoggingSettingsAlter(&$form, FormStateInterface $form_state) : void {
    $config = \Drupal::configFactory()->getEditable('syslog.settings');
    $help = \Drupal::moduleHandler()->moduleExists('help') ? ' ' . Link::fromTextAndUrl($this->t('More information'), Url::fromRoute('help.page', ['name' => 'syslog']))->toString() . '.' : NULL;
    $form['syslog_identity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Syslog identity'),
      '#default_value' => $config->get('identity'),
      '#description' => $this->t('A string that will be prepended to every message logged to Syslog. If you have multiple sites logging to the same Syslog log file, a unique identity per site makes it easy to tell the log entries apart.') . $help,
    ];
    if (defined('LOG_LOCAL0')) {
      $form['syslog_facility'] = [
        '#type' => 'select',
        '#title' => $this->t('Syslog facility'),
        '#default_value' => $config->get('facility'),
        '#options' => syslog_facility_list(),
        '#description' => $this->t('Depending on the system configuration, Syslog and other logging tools use this code to identify or filter messages from within the entire system log.') . $help,
      ];
    }
    $form['syslog_format'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Syslog format'),
      '#default_value' => $config->get('format'),
      '#required' => TRUE,
      '#description' => $this->t('Specify the format of the syslog entry. Available variables are: <dl><dt><code>!base_url</code></dt><dd>Base URL of the site.</dd><dt><code>!timestamp</code></dt><dd>Unix timestamp of the log entry.</dd><dt><code>!type</code></dt><dd>The category to which this message belongs.</dd><dt><code>!ip</code></dt><dd>IP address of the user triggering the message.</dd><dt><code>!request_uri</code></dt><dd>The requested URI.</dd><dt><code>!referer</code></dt><dd>HTTP Referer if available.</dd><dt><code>!severity</code></dt><dd>The severity level of the event; ranges from 0 (Emergency) to 7 (Debug).</dd><dt><code>!uid</code></dt><dd>User ID.</dd><dt><code>!link</code></dt><dd>A link to associate with the message.</dd><dt><code>!message</code></dt><dd>The message to store in the log.</dd></dl>'),
    ];
    $form['#submit'][] = 'syslog_logging_settings_submit';
  }

}
