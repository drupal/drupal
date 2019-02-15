<?php

namespace Drupal\Tests\layout_builder\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for Layout Builder tempstore keys.
 *
 * @group layout_builder
 * @group legacy
 */
class TempstoreKeyUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/layout-builder.php',
      __DIR__ . '/../../../fixtures/update/layout-builder-field-block.php',
      __DIR__ . '/../../../fixtures/update/layout-builder-tempstore.php',
    ];
  }

  /**
   * Tests the upgrade path for Layout Builder extra fields.
   */
  public function testRunUpdates() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->runUpdates();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));
    $this->drupalGet('node/1');
    $assert_session->elementExists('css', '.layout--onecol');
    $assert_session->elementNotExists('css', '.layout--twocol-section');

    $page->clickLink('Layout');
    $assert_session->pageTextContains('You have unsaved changes.');
    $assert_session->elementNotExists('css', '.layout--onecol');
    $assert_session->elementExists('css', '.layout--twocol-section');
  }

}
