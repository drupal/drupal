<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUserConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;

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
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('d6_user_mail');
    $this->executeMigration('d6_user_settings');
  }

  /**
   * Tests migration of user variables to user.mail.yml.
   */
  public function testUserMail() {
    $config = $this->config('user.mail');
    $this->assertIdentical('Account details for !username at !site (approved)', $config->get('status_activated.subject'));
    $this->assertIdentical("!username,\n\nYour account at !site has been activated.\n\nYou may now log in by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\n\nOnce you have set your own password, you will be able to log in to !login_uri in the future using:\n\nusername: !username\n", $config->get('status_activated.body'));
    $this->assertIdentical('Replacement login information for !username at !site', $config->get('password_reset.subject'));
    $this->assertIdentical("!username,\n\nA request to reset the password for your account has been made at !site.\n\nYou may now log in to !uri_brief by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once. It expires after one day and nothing will happen if it's not used.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.", $config->get('password_reset.body'));
    $this->assertIdentical('Account details for !username at !site (deleted)', $config->get('cancel_confirm.subject'));
    $this->assertIdentical("!username,\n\nYour account on !site has been deleted.", $config->get('cancel_confirm.body'));
    $this->assertIdentical('An administrator created an account for you at !site', $config->get('register_admin_created.subject'));
    $this->assertIdentical("!username,\n\nA site administrator at !site has created an account for you. You may now log in to !login_uri using the following username and password:\n\nusername: !username\npassword: !password\n\nYou may also log in by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\n\n\n--  !site team", $config->get('register_admin_created.body'));
    $this->assertIdentical('Account details for !username at !site', $config->get('register_no_approval_required.subject'));
    $this->assertIdentical("!username,\n\nThank you for registering at !site. You may now log in to !login_uri using the following username and password:\n\nusername: !username\npassword: !password\n\nYou may also log in by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\n\n\n--  !site team", $config->get('register_no_approval_required.body'));
    $this->assertIdentical('Account details for !username at !site (pending admin approval)', $config->get('register_pending_approval.subject'));
    $this->assertIdentical("!username,\n\nThank you for registering at !site. Your application for an account is currently pending approval. Once it has been approved, you will receive another email containing information about how to log in, set your password, and other details.\n\n\n--  !site team", $config->get('register_pending_approval.body'));
    $this->assertIdentical('Account details for !username at !site (blocked)', $config->get('status_blocked.subject'));
    $this->assertIdentical("!username,\n\nYour account on !site has been blocked.", $config->get('status_blocked.body'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'user.mail', $config->get());
  }

  /**
   * Tests migration of user variables to user.settings.yml.
   */
  public function testUserSettings() {
    $config = $this->config('user.settings');
    $this->assertIdentical(TRUE, $config->get('notify.status_blocked'));
    $this->assertIdentical(FALSE, $config->get('notify.status_activated'));
    $this->assertIdentical(FALSE, $config->get('verify_mail'));
    $this->assertIdentical('admin_only', $config->get('register'));
    $this->assertIdentical('Guest', $config->get('anonymous'));
  }

}
