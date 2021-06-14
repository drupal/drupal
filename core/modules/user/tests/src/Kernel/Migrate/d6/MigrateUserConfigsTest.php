<?php

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\user\AccountSettingsForm;
use Drupal\Core\Database\Database;
use Drupal\user\UserInterface;

/**
 * Upgrade variables to user.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateUserConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigrations(['d6_user_mail', 'd6_user_settings']);
  }

  /**
   * Tests migration of user variables to user.mail.yml.
   */
  public function testUserMail() {
    $config = $this->config('user.mail');

    $this->assertSame('Account details for [user:name] at [site:name] (approved)', $config->get('status_activated.subject'));
    $this->assertSame("[user:name],\n\nYour account at [site:name] has been activated.\n\nYou may now log in by clicking on this link or copying and pasting it in your browser:\n\n[user:one-time-login-url]\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to [user:edit-url] so you can change your password.\n\nOnce you have set your own password, you will be able to log in to [site:login-url] in the future using:\n\nusername: [user:name]\n", $config->get('status_activated.body'));
    $this->assertSame('Replacement login information for [user:name] at [site:name]', $config->get('password_reset.subject'));
    $this->assertSame("[user:name],\n\nA request to reset the password for your account has been made at [site:name].\n\nYou may now log in to [site:url-brief] by clicking on this link or copying and pasting it in your browser:\n\n[user:one-time-login-url]\n\nThis is a one-time login, so it can be used only once. It expires after one day and nothing will happen if it's not used.\n\nAfter logging in, you will be redirected to [user:edit-url] so you can change your password.", $config->get('password_reset.body'));
    $this->assertSame('Account details for [user:name] at [site:name] (deleted)', $config->get('cancel_confirm.subject'));
    $this->assertSame("[user:name],\n\nYour account on [site:name] has been deleted.", $config->get('cancel_confirm.body'));
    $this->assertSame('An administrator created an account for you at [site:name]', $config->get('register_admin_created.subject'));
    $this->assertSame("[user:name],\n\nA site administrator at [site:name] has created an account for you. You may now log in to [site:login-url] using the following username and password:\n\nusername: [user:name]\npassword: \n\nYou may also log in by clicking on this link or copying and pasting it in your browser:\n\n[user:one-time-login-url]\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to [user:edit-url] so you can change your password.\n\n\n--  [site:name] team", $config->get('register_admin_created.body'));
    $this->assertSame('Account details for [user:name] at [site:name]', $config->get('register_no_approval_required.subject'));
    $this->assertSame("[user:name],\n\nThank you for registering at [site:name]. You may now log in to [site:login-url] using the following username and password:\n\nusername: [user:name]\npassword: \n\nYou may also log in by clicking on this link or copying and pasting it in your browser:\n\n[user:one-time-login-url]\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to [user:edit-url] so you can change your password.\n\n\n--  [site:name] team", $config->get('register_no_approval_required.body'));
    $this->assertSame('Account details for [user:name] at [site:name] (pending admin approval)', $config->get('register_pending_approval.subject'));
    $this->assertSame("[user:name],\n\nThank you for registering at [site:name]. Your application for an account is currently pending approval. Once it has been approved, you will receive another email containing information about how to log in, set your password, and other details.\n\n\n--  [site:name] team", $config->get('register_pending_approval.body'));
    $this->assertSame('Account details for [user:name] at [site:name] (blocked)', $config->get('status_blocked.subject'));
    $this->assertSame("[user:name],\n\nYour account on [site:name] has been blocked.", $config->get('status_blocked.body'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'user.mail', $config->get());

    // Tests migration of user variables to user.settings.yml.
    $config = $this->config('user.settings');
    $this->assertTrue($config->get('notify.status_blocked'));
    $this->assertFalse($config->get('notify.status_activated'));
    $this->assertFalse($config->get('verify_mail'));
    $this->assertSame('admin_only', $config->get('register'));
    $this->assertSame('Guest', $config->get('anonymous'));

    // Tests migration of user_register using the AccountSettingsForm.

    // Map D6 value to D8 value
    $user_register_map = [
      [0, UserInterface::REGISTER_ADMINISTRATORS_ONLY],
      [1, UserInterface::REGISTER_VISITORS],
      [2, UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL],
    ];

    foreach ($user_register_map as $map) {
      // Tests migration of user_register = 1
      Database::getConnection('default', 'migrate')
        ->update('variable')
        ->fields(['value' => serialize($map[0])])
        ->condition('name', 'user_register')
        ->execute();

      /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
      $migration = $this->getMigration('d6_user_settings');
      // Indicate we're rerunning a migration that's already run.
      $migration->getIdMap()->prepareUpdate();
      $this->executeMigration($migration);
      $form = $this->container->get('form_builder')->getForm(AccountSettingsForm::create($this->container));
      $this->assertSame($map[1], $form['registration_cancellation']['user_register']['#value']);
    }
  }

}
