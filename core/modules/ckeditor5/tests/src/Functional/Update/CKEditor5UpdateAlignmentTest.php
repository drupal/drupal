<?php

namespace Drupal\Tests\ckeditor5\Functional\Update;

use Drupal\editor\Entity\Editor;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;

/**
 * Tests the update path for CKEditor 5 alignment.
 *
 * @group Update
 */
class CKEditor5UpdateAlignmentTest extends UpdatePathTestBase {

  use CKEditor5TestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.3.0.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/ckeditor5-3259593.php',
    ];
  }

  /**
   * Tests that CKEditor 5 alignment configurations that are individual buttons
   * are updated to be in dropdown form in the toolbar.
   */
  public function testUpdateAlignmentButtons() {
    $editor = Editor::load('test_format');
    $settings = $editor->getSettings();
    $this->assertContains('alignment:center', $settings['toolbar']['items']);

    $this->runUpdates();

    $expected_toolbar_items = [
      'link',
      'bold',
      'italic',
      'sourceEditing',
      'alignment',
    ];
    $expected_alignment_plugin = [
      'enabled_alignments' => [
        'center',
      ],
    ];
    $editor = Editor::load('test_format');
    $settings = $editor->getSettings();
    $this->assertEquals($expected_toolbar_items, $settings['toolbar']['items']);
    $this->assertEquals($expected_alignment_plugin, $settings['plugins']['ckeditor5_alignment']);
  }

}
