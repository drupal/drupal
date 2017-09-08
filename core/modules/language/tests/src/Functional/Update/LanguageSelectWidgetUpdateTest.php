<?php

namespace Drupal\Tests\language\Functional\Update;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the update path for the language_select widget.
 *
 * @group Update
 */
class LanguageSelectWidgetUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.filled.standard.php.gz',
    ];
  }

  /**
   * Tests language_post_update_language_select_widget().
   */
  public function testLanguagePostUpdateLanguageSelectWidget() {
    // Tests before the update.
    $content_before = EntityFormDisplay::load('node.page.default')->get('content');
    $this->assertEqual([], $content_before['langcode']['settings']);

    // Run the update.
    $this->runUpdates();

    // Tests after the update.
    $content_after = EntityFormDisplay::load('node.page.default')->get('content');
    $this->assertEqual(['include_locked' => TRUE], $content_after['langcode']['settings']);
  }

}
