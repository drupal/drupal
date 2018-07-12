<?php

namespace Drupal\Tests\user\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests user email token upgrade path.
 *
 * @group Update
 * @group legacy
 */
class UserUpdateEmailToken extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.user-email-token-2587275.php',
    ];
  }

  /**
   * Tests that email token in status_blocked of user.mail is updated.
   */
  public function testEmailToken() {
    $mail = \Drupal::config('user.mail')->get('status_blocked');
    $this->assertTrue(strpos($mail['body'], '[site:account-name]'));
    $this->runUpdates();
    $mail = \Drupal::config('user.mail')->get('status_blocked');
    $this->assertFalse(strpos($mail['body'], '[site:account-name]'));
  }

}
