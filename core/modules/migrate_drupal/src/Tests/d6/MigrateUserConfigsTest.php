<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to user.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateUserConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
    );
    $migration = entity_load('migration', 'd6_user_mail');
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $migration = entity_load('migration', 'd6_user_settings');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of user variables to user.mail.yml.
   */
  public function testUserMail() {
    $config = $this->config('user.mail');
    $this->assertIdentical($config->get('status_activated.subject'), 'Account details for !username at !site (approved)');
    $this->assertIdentical($config->get('status_activated.body'), "!username,\n\nYour account at !site has been activated.\n\nYou may now log in by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\n\nOnce you have set your own password, you will be able to log in to !login_uri in the future using:\n\nusername: !username\n");
    $this->assertIdentical($config->get('password_reset.subject'), 'Replacement login information for !username at !site');
    $this->assertIdentical($config->get('password_reset.body'), "!username,\n\nA request to reset the password for your account has been made at !site.\n\nYou may now log in to !uri_brief by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once. It expires after one day and nothing will happen if it's not used.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.");
    $this->assertIdentical($config->get('cancel_confirm.subject'), 'Account details for !username at !site (deleted)');
    $this->assertIdentical($config->get('cancel_confirm.body'), "!username,\n\nYour account on !site has been deleted.");
    $this->assertIdentical($config->get('register_admin_created.subject'), 'An administrator created an account for you at !site');
    $this->assertIdentical($config->get('register_admin_created.body'), "!username,\n\nA site administrator at !site has created an account for you. You may now log in to !login_uri using the following username and password:\n\nusername: !username\npassword: !password\n\nYou may also log in by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\n\n\n--  !site team");
    $this->assertIdentical($config->get('register_no_approval_required.subject'), 'Account details for !username at !site');
    $this->assertIdentical($config->get('register_no_approval_required.body'), "!username,\n\nThank you for registering at !site. You may now log in to !login_uri using the following username and password:\n\nusername: !username\npassword: !password\n\nYou may also log in by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\n\n\n--  !site team");
    $this->assertIdentical($config->get('register_pending_approval.subject'), 'Account details for !username at !site (pending admin approval)');
    $this->assertIdentical($config->get('register_pending_approval.body'), "!username,\n\nThank you for registering at !site. Your application for an account is currently pending approval. Once it has been approved, you will receive another email containing information about how to log in, set your password, and other details.\n\n\n--  !site team");
    $this->assertIdentical($config->get('status_blocked.subject'), 'Account details for !username at !site (blocked)');
    $this->assertIdentical($config->get('status_blocked.body'), "!username,\n\nYour account on !site has been blocked.");
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'user.mail', $config->get());
  }

  /**
   * Tests migration of user variables to user.settings.yml.
   */
  public function testUserSettings() {
    $config = $this->config('user.settings');
    $this->assertIdentical($config->get('notify.status_blocked'), TRUE);
    $this->assertIdentical($config->get('notify.status_activated'), FALSE);
    $this->assertIdentical($config->get('signatures'), TRUE);
    $this->assertIdentical($config->get('verify_mail'), FALSE);
    $this->assertIdentical($config->get('register'), 'admin_only');
    $this->assertIdentical($config->get('anonymous'), 'Guest');
  }

}
