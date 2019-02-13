<?php

namespace Drupal\Tests\layout_builder\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests context-aware blocks after the context changes to section storage.
 *
 * @group layout_builder
 * @group legacy
 */
class LayoutBuilderContextMappingUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/layout-builder.php',
      __DIR__ . '/../../../fixtures/update/layout-builder-field-block.php',
    ];
  }

  /**
   * Tests the upgrade path for enabling Layout Builder.
   */
  public function testRunUpdates() {
    $assert_session = $this->assertSession();

    $this->runUpdates();

    $this->drupalLogin($this->rootUser);
    // Ensure that defaults and overrides display the body field within the
    // content region of the one column layout.
    $paths = [
      // Overrides.
      'node/1',
      // Defaults.
      'admin/structure/types/manage/article/display/default/layout',
    ];
    foreach ($paths as $path) {
      $this->drupalGet($path);
      $assert_session->statusCodeEquals(200);
      $assert_session->elementExists('css', '.layout--onecol .layout__region--content .field--name-body');
    }
  }

}
