<?php

namespace Drupal\Tests\user\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrates user mail configuration.
 *
 * @group user
 */
class MigrateUserMailTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['user']);
    $this->executeMigration('d7_user_mail');
  }

  /**
   * Tests the migration.
   */
  public function testMigration() {
    $config = $this->config('user.mail');
    $this->assertIdentical('Your account is approved!', $config->get('status_activated.subject'));
    $this->assertIdentical('Your account was activated, and there was much rejoicing.', $config->get('status_activated.body'));
    $this->assertIdentical('Fix your password', $config->get('password_reset.subject'));
    $this->assertIdentical("Nope! You're locked out forever.", $config->get('password_reset.body'));
    $this->assertIdentical('So long, bub', $config->get('cancel_confirm.subject'));
    $this->assertIdentical('The gates of Drupal are closed to you. Now you will work in the salt mines.', $config->get('cancel_confirm.body'));
    $this->assertIdentical('Gawd made you an account', $config->get('register_admin_created.subject'));
    $this->assertIdentical('...and she could take it away.', $config->get('register_admin_created.body'));
    $this->assertIdentical('Welcome!', $config->get('register_no_approval_required.subject'));
    $this->assertIdentical('You can now log in if you can figure out how to use Drupal!', $config->get('register_no_approval_required.body'));
    $this->assertIdentical('Soon...', $config->get('register_pending_approval.subject'));
    $this->assertIdentical('...you will join our Circle. Let the Drupal flow through you.', $config->get('register_pending_approval.body'));
    $this->assertIdentical('BEGONE!', $config->get('status_blocked.subject'));
    $this->assertIdentical('You no longer please the robot overlords. Go to your room and chill out.', $config->get('status_blocked.body'));
  }

}
