<?php

namespace Drupal\dblog\Hook;

use Drupal\views\ViewExecutable;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for dblog.
 */
class DblogHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.dblog':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Database Logging module logs system events in the Drupal database. For more information, see the <a href=":dblog">online documentation for the Database Logging module</a>.', [':dblog' => 'https://www.drupal.org/documentation/modules/dblog']) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Monitoring your site') . '</dt>';
        $output .= '<dd>' . t('The Database Logging module allows you to view an event log on the <a href=":dblog">Recent log messages</a> page. The log is a chronological list of recorded events containing usage data, performance data, errors, warnings and operational information. Administrators should check the log on a regular basis to ensure their site is working properly.', [':dblog' => Url::fromRoute('dblog.overview')->toString()]) . '</dd>';
        $output .= '<dt>' . t('Debugging site problems') . '</dt>';
        $output .= '<dd>' . t('In case of errors or problems with the site, the <a href=":dblog">Recent log messages</a> page can be useful for debugging, since it shows the sequence of events. The log messages include usage information, warnings, and errors.', [':dblog' => Url::fromRoute('dblog.overview')->toString()]) . '</dd>';
        $output .= '<dt>' . t('This log is not persistent') . '</dt>';
        $output .= '<dd>' . t('The Database Logging module logs may be cleared by administrators and automated cron tasks, so they should not be used for <a href=":audit_trail_wiki">forensic logging</a>. For forensic purposes, use the Syslog module.', [':audit_trail_wiki' => 'https://en.wikipedia.org/wiki/Audit_trail']) . '</dd>';
        $output .= '</dl>';
        return $output;

      case 'dblog.overview':
        return '<p>' . t('The Database Logging module logs system events in the Drupal database. Monitor your site or debug site problems on this page.') . '</p>';
    }
  }

  /**
   * Implements hook_menu_links_discovered_alter().
   */
  #[Hook('menu_links_discovered_alter')]
  public function menuLinksDiscoveredAlter(&$links): void {
    if (\Drupal::moduleHandler()->moduleExists('search')) {
      $links['dblog.search'] = [
        'title' => new TranslatableMarkup('Top search phrases'),
        'route_name' => 'dblog.search',
        'description' => new TranslatableMarkup('View most popular search phrases.'),
        'parent' => 'system.admin_reports',
      ];
    }
  }

  /**
   * Implements hook_cron().
   *
   * Controls the size of the log table, paring it to 'dblog_row_limit' messages.
   */
  #[Hook('cron')]
  public function cron(): void {
    // Cleanup the watchdog table.
    $row_limit = \Drupal::config('dblog.settings')->get('row_limit');
    // For row limit n, get the wid of the nth row in descending wid order.
    // Counting the most recent n rows avoids issues with wid number sequences,
    // e.g. auto_increment value > 1 or rows deleted directly from the table.
    if ($row_limit > 0) {
      $connection = \Drupal::database();
      $min_row = $connection->select('watchdog', 'w')->fields('w', ['wid'])->orderBy('wid', 'DESC')->range($row_limit - 1, 1)->execute()->fetchField();
      // Delete all table entries older than the nth row, if nth row was found.
      if ($min_row) {
        $connection->delete('watchdog')->condition('wid', $min_row, '<')->execute();
      }
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for system_logging_settings().
   */
  #[Hook('form_system_logging_settings_alter')]
  public function formSystemLoggingSettingsAlter(&$form, FormStateInterface $form_state) : void {
    $row_limits = [100, 1000, 10000, 100000, 1000000];
    $form['dblog_row_limit'] = [
      '#type' => 'select',
      '#title' => t('Database log messages to keep'),
      '#config_target' => 'dblog.settings:row_limit',
      '#options' => [
        0 => t('All'),
      ] + array_combine($row_limits, $row_limits),
      '#description' => t('The maximum number of messages to keep in the database log. Requires a <a href=":cron">cron maintenance task</a>.', [
        ':cron' => Url::fromRoute('system.status')->toString(),
      ]),
    ];
  }

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view) {
    if (isset($view) && $view->storage->get('base_table') == 'watchdog') {
      $view->element['#attached']['library'][] = 'dblog/drupal.dblog';
    }
  }

}
