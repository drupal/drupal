<?php

namespace Drupal\Tests\config_translation\Kernel\Migrate\d7;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Test migration of i18n user variables.
 *
 * @group migrate_drupal_7
 */
class MigrateUserConfigsTranslationTest extends MigrateDrupal7TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'locale',
    'config_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('locale', [
      'locales_source',
      'locales_target',
      'locales_location',
    ]);
    $this->executeMigrations([
      'd7_user_mail',
      'd7_user_settings',
      'd7_user_mail_translation',
      'd7_user_settings_translation',
    ]);
  }

  /**
   * Tests migration of i18n user variables to user.mail and user.settings.
   */
  public function testUserConfig() {
    // Tests migration of i18n user variables to user.mail.yml.
    $language_manager = \Drupal::service('language_manager');
    $config = $language_manager->getLanguageConfigOverride('is', 'user.mail');
    $this->assertSame('is - Are you sure?', $config->get('cancel_confirm.subject'));
    $this->assertSame('is - A little birdie said you wanted to cancel your account.', $config->get('cancel_confirm.body'));
    $this->assertSame('is - Fix your password', $config->get('password_reset.subject'));
    $this->assertSame("is - Nope! You're locked out forever.", $config->get('password_reset.body'));
    $this->assertSame('is - Gawd made you an account', $config->get('register_admin_created.subject'));
    $this->assertSame("is - ...and it could be taken away.\r\n[site:name], [site:url]", $config->get('register_admin_created.body'));
    $this->assertSame('is - Welcome!', $config->get('register_no_approval_required.subject'));
    $this->assertSame('is - You can now log in if you can figure out how to use Drupal!', $config->get('register_no_approval_required.body'));
    $this->assertSame('is - Soon...', $config->get('register_pending_approval.subject'));
    $this->assertSame('is - ...you will join our Circle. Let the Drupal flow through you.', $config->get('register_pending_approval.body'));
    $this->assertSame('is - Your account is approved!', $config->get('status_activated.subject'));
    $this->assertSame('is - Your account was activated, and there was much rejoicing.', $config->get('status_activated.body'));
    $this->assertSame('is - BEGONE!', $config->get('status_blocked.subject'));
    $this->assertSame('is - You no longer please the robot overlords. Go to your room and chill out.', $config->get('status_blocked.body'));
    $this->assertSame('is - So long, bub', $config->get('status_canceled.subject'));
    $this->assertSame('is - The gates of Drupal are closed to you. Now you will work in the salt mines.', $config->get('status_canceled.body'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'user.mail', $config->get());

    // Tests migration of i18n user variables to user.settings.yml.
    $config = $language_manager->getLanguageConfigOverride('is', 'user.settings');
    $this->assertSame('is - anonymous', $config->get('anonymous'));
  }

}
