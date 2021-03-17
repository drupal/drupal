<?php

namespace Drupal\Tests\user\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests update of user mail change configurations.
 *
 * @group user
 * @group legacy
 */
class UpdateMailChangeTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests user_post_update_mail_change().
   *
   * @see user_post_update_mail_change()
   */
  public function testMailChangeUpdate() {
    $user_settings = $this->config('user.settings');
    $user_mail = $this->config('user.mail');

    // Check that mail change notifications settings are not set.
    $this->assertNull($user_settings->get('notify.mail_change_notification'));
    $this->assertNull($user_settings->get('notify.mail_change_verification'));
    $this->assertNull($user_settings->get('mail_change_timeout'));

    // Check that mail change configurations are not set.
    $this->assertNull($user_mail->get('mail_change_notification'));
    $this->assertNull($user_mail->get('mail_change_verification'));

    $this->runUpdates();

    $user_settings = $this->config('user.settings');
    $user_mail = $this->config('user.mail');

    // Check that mail change notifications were set to FALSE.
    $this->assertFalse($user_settings->get('notify.mail_change_notification'));
    $this->assertFalse($user_settings->get('notify.mail_change_verification'));

    $config = Yaml::parse(file_get_contents(__DIR__ . '/../../../../config/install/user.mail.yml'));

    // Check that mail change configurations were set to default values.
    $this->assertSame($config['mail_change_notification'], $user_mail->get('mail_change_notification'));
    $this->assertSame($config['mail_change_verification'], $user_mail->get('mail_change_verification'));
    // Check that mail change timeout was set.
    $this->assertEquals(86400, $user_settings->get('mail_change_timeout'));
  }

}
