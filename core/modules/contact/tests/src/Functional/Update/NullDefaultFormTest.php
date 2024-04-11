<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for making 'default_form' in 'contact.settings' config to NULL.
 *
 * @group contact
 * @see contact_post_update_set_empty_default_form_to_null()
 */
class NullDefaultFormTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests the upgrade path for updating empty 'default_form' to NULL.
   */
  public function testRunUpdates(): void {
    $this->config('contact.settings')->set('default_form', '')->save();
    $this->assertSame('', $this->config('contact.settings')->get('default_form'));
    $this->runUpdates();
    $this->assertNull($this->config('contact.settings')->get('default_form'));
  }

}
