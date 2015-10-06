<?php

/**
 * @file
 * Contains \Drupal\editor\Tests\Update\EditorUpdateTest.
 */

namespace Drupal\editor\Tests\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests Editor module database updates.
 *
 * @group editor
 */
class EditorUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      // Simulate an un-synchronized environment.
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.editor-editor_update_8001.php',
    ];
  }

  /**
   * Tests editor_update_8001().
   *
   * @see editor_update_8001()
   */
  public function testEditorUpdate8001() {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');

    $format_basic_html = $config_factory->get('filter.format.basic_html');
    $editor_basic_html = $config_factory->get('editor.editor.basic_html');
    $format_full_html = $config_factory->get('filter.format.full_html');
    $editor_full_html = $config_factory->get('editor.editor.full_html');

    // Checks if the 'basic_html' format and editor statuses differ.
    $this->assertTrue($format_basic_html->get('status'));
    $this->assertFalse($editor_basic_html->get('status'));
    $this->assertNotIdentical($format_basic_html->get('status'), $editor_basic_html->get('status'));

    // Checks if the 'full_html' format and editor statuses differ.
    $this->assertFalse($format_full_html->get('status'));
    $this->assertTrue($editor_full_html->get('status'));
    $this->assertNotIdentical($format_full_html->get('status'), $editor_full_html->get('status'));


    // Run updates.
    $this->runUpdates();

    // Reload text formats and editors.
    $format_basic_html = $config_factory->get('filter.format.basic_html');
    $editor_basic_html = $config_factory->get('editor.editor.basic_html');
    $format_full_html = $config_factory->get('filter.format.full_html');
    $editor_full_html = $config_factory->get('editor.editor.full_html');

    // Checks if the 'basic_html' format and editor statuses are in sync.
    $this->assertTrue($format_basic_html->get('status'));
    $this->assertTrue($editor_basic_html->get('status'));
    $this->assertIdentical($format_basic_html->get('status'), $editor_basic_html->get('status'));

    // Checks if the 'full_html' format and editor statuses are in sync.
    $this->assertFalse($format_full_html->get('status'));
    $this->assertFalse($editor_full_html->get('status'));
    $this->assertIdentical($format_full_html->get('status'), $editor_full_html->get('status'));
  }

}
