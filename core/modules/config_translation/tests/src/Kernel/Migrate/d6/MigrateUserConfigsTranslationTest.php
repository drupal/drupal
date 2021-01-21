<?php

namespace Drupal\Tests\config_translation\Kernel\Migrate\d6;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade i18n variables to user.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateUserConfigsTranslationTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

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
    $this->installSchema('locale',
      ['locales_source', 'locales_target', 'locales_location']);
    $this->executeMigrations(['d6_user_mail_translation', 'd6_user_settings_translation']);
  }

  /**
   * Tests migration of i18n user variables to user.mail.yml.
   */
  public function testUserMail() {
    $config = \Drupal::service('language_manager')->getLanguageConfigOverride('fr', 'user.mail');
    $this->assertSame('fr - Account details for [user:name] at [site:name] (approved)', $config->get('status_activated.subject'));
    $this->assertSame("fr - [user:name],\r\n\r\nYour account at [site:name] has been activated.\r\n\r\nYou may now log in by clicking on this link or copying and pasting it in your browser:\r\n\r\n[user:one-time-login-url]\r\n\r\nThis is a one-time login, so it can be used only once.\r\n\r\nAfter logging in, you will be redirected to [user:edit-url] so you can change your password.\r\n\r\nOnce you have set your own password, you will be able to log in to [site:login-url] in the future using:\r\n\r\nusername: [user:name]\r\n", $config->get('status_activated.body'));
    $this->assertSame('fr - Replacement login information for [user:name] at [site:name]', $config->get('password_reset.subject'));
    $this->assertSame("fr - [user:name],\r\n\r\nA request to reset the password for your account has been made at [site:name].\r\n\r\nYou may now log in to [site:url-brief] by clicking on this link or copying and pasting it in your browser:\r\n\r\n[user:one-time-login-url]\r\n\r\nThis is a one-time login, so it can be used only once. It expires after one day and nothing will happen if it's not used.\r\n\r\nAfter logging in, you will be redirected to [user:edit-url] so you can change your password.", $config->get('password_reset.body'));
    $this->assertSame('fr - Account details for [user:name] at [site:name] (deleted)', $config->get('cancel_confirm.subject'));
    $this->assertSame("fr - [user:name],\r\n\r\nYour account on [site:name] has been deleted.", $config->get('cancel_confirm.body'));
    $this->assertSame('fr - An administrator created an account for you at [site:name]', $config->get('register_admin_created.subject'));
    $this->assertSame("fr - [user:name],\r\n\r\nA site administrator at [site:name] has created an account for you. You may now log in to [site:login-url] using the following username and password:\r\n\r\nusername: [user:name]\r\npassword: \r\n\r\nYou may also log in by clicking on this link or copying and pasting it in your browser:\r\n\r\n[user:one-time-login-url]\r\n\r\nThis is a one-time login, so it can be used only once.\r\n\r\nAfter logging in, you will be redirected to [user:edit-url] so you can change your password.\r\n\r\n\r\n--  [site:name] team", $config->get('register_admin_created.body'));
    $this->assertSame('fr - Account details for [user:name] at [site:name]', $config->get('register_no_approval_required.subject'));
    $this->assertSame("fr - [user:name],\r\n\r\nThank you for registering at [site:name]. You may now log in to [site:login-url] using the following username and password:\r\n\r\nusername: [user:name]\r\npassword: \r\n\r\nYou may also log in by clicking on this link or copying and pasting it in your browser:\r\n\r\n[user:one-time-login-url]\r\n\r\nThis is a one-time login, so it can be used only once.\r\n\r\nAfter logging in, you will be redirected to [user:edit-url] so you can change your password.\r\n\r\n\r\n--  [site:name] team", $config->get('register_no_approval_required.body'));
    $this->assertSame('fr - Account details for [user:name] at [site:name] (pending admin approval)', $config->get('register_pending_approval.subject'));
    $this->assertSame("fr - [user:name],\r\n\r\nThank you for registering at [site:name]. Your application for an account is currently pending approval. Once it has been approved, you will receive another email containing information about how to log in, set your password, and other details.\r\n\r\n\r\n--  [site:name] team", $config->get('register_pending_approval.body'));
    $this->assertSame('fr - Account details for [user:name] at [site:name] (blocked)', $config->get('status_blocked.subject'));
    $this->assertSame("fr - [user:name],\r\n\r\nYour account on [site:name] has been blocked.", $config->get('status_blocked.body'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'user.mail', $config->get());

    $config = \Drupal::service('language_manager')->getLanguageConfigOverride('zu', 'user.mail');
    $this->assertSame('zu - An administrator created an account for you at [site:name]', $config->get('register_admin_created.subject'));
    $this->assertSame("zu - [user:name],\r\n\r\nA site administrator at [site:name] has created an account for you. You may now log in to [site:login-url] using the following username and password:\r\n\r\nusername: [user:name]\r\npassword: \r\n\r\nYou may also log in by clicking on this link or copying and pasting it in your browser:\r\n\r\n[user:one-time-login-url]\r\n\r\nThis is a one-time login, so it can be used only once.\r\n\r\nAfter logging in, you will be redirected to [user:edit-url] so you can change your password.\r\n\r\n\r\n--  [site:name] team", $config->get('register_admin_created.body'));
  }

  /**
   * Tests migration of i18n user variables to user.settings.yml.
   */
  public function testUserSettings() {
    $config = \Drupal::service('language_manager')->getLanguageConfigOverride('fr', 'user.settings');
    $this->assertSame(1, $config->get('notify.status_blocked'));
    $this->assertSame(0, $config->get('notify.status_activated'));
    $this->assertSame(0, $config->get('verify_mail'));
    $this->assertSame('admin_only', $config->get('register'));
    $this->assertSame('fr Guest', $config->get('anonymous'));

    $config = \Drupal::service('language_manager')->getLanguageConfigOverride('zu', 'user.settings');
    $this->assertSame(1, $config->get('notify.status_blocked'));
    $this->assertSame(0, $config->get('notify.status_activated'));
    $this->assertSame(0, $config->get('verify_mail'));
    $this->assertSame('admin_only', $config->get('register'));
    $this->assertSame('Guest', $config->get('anonymous'));
  }

}
