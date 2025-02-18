<?php

namespace Drupal\migrate_drupal_ui\Hook;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for migrate_drupal_ui.
 */
class MigrateDrupalUiHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.migrate_drupal_ui':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Migrate Drupal UI module provides a simple user interface to perform an upgrade from an earlier version of Drupal. For more information, see the <a href=":migrate">online documentation for the Migrate Drupal UI module</a>.', [':migrate' => 'https://www.drupal.org/upgrade/migrate']) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Preparing the site') . '</dt>';
        $output .= '<dd>' . $this->t('You need to install all modules on this site that are installed on the previous site. For example, if you have used the Book module on the previous site then you must install the Book module on this site for that data to be available on this site.') . '</dd>';
        $output .= '<dt>' . $this->t('Performing the upgrade') . '</dt>';
        $output .= '<dd>' . $this->t('On the <a href=":upgrade">Upgrade</a> page, you are guided through performing the upgrade in several steps.', [
          ':upgrade' => Url::fromRoute('migrate_drupal_ui.upgrade')->toString(),
        ]) . '</dd>';
        $output .= '<dd><ol><li>' . $this->t('If an upgrade has been performed on this site, you will be informed.') . '</li>';
        $output .= '<li>' . $this->t('You need to enter the database credentials of the Drupal site that you want to upgrade. You can also include its files directory in the upgrade. For example local files, /var/www/docroot, or remote files http://www.example.com.') . '</li>';
        $output .= '<li>' . $this->t('If there is existing content on the site that may be overwritten by this upgrade, you will be informed.') . '</li>';
        $output .= '<li>' . $this->t('The next page provides an overview of the modules that will be upgraded and those that will not be upgraded, before you proceed to perform the upgrade.') . '</li>';
        $output .= '<li>' . $this->t('Finally, a message is displayed about the number of upgrade tasks that were successful or failed.') . '</li></ol></dd>';
        $output .= '<dt>' . $this->t('Reviewing the upgrade log') . '</dt>';
        $output .= '<dd>' . $this->t('You can review a <a href=":log">log of upgrade messages</a> by clicking the link in the message provided after the upgrade or by filtering the messages for the type <em>migrate_drupal_ui</em> on the <a href=":messages">Recent log messages</a> page.', [
          ':log' => Url::fromRoute('migrate_drupal_ui.log')->toString(),
          ':messages' => Url::fromRoute('dblog.overview')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Rolling back an upgrade') . '</dt>';
        $output .= '<dd>' . $this->t('Rolling back an upgrade is not yet supported through the user interface.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

}
