<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6UserSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing user.settings.yml migration.
 */
class Drupal6UserSettings extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('variable');
    $this->database->insert('variable')->fields(array(
      'name',
      'value',
    ))
    ->values(array(
      'name' => 'user_mail_status_blocked_notify',
      'value' => 'i:1;',
    ))
    ->values(array(
      'name' => 'user_mail_status_activated_notify',
      'value' => 'i:0;',
    ))
    ->values(array(
      'name' => 'user_signatures',
      'value' => 's:1:"1";',
    ))
    ->values(array(
      'name' => 'user_email_verification',
      'value' => 'i:0;',
    ))
    ->values(array(
      'name' => 'user_register',
      'value' => 'i:0;',
    ))
    ->values(array(
      'name' => 'anonymous',
      'value' => 's:5:"Guest";',
    ))
    ->execute();
  }
}
