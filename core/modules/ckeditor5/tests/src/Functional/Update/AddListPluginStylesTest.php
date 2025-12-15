<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Functional\Update;

use Drupal\editor\Entity\Editor;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests creating base field overrides for the promote field on node types.
 */
#[Group('Update')]
#[RunTestsInSeparateProcesses]
class AddListPluginStylesTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/remove-source-editing-from-full-html-editor.php',
    ];
  }

  /**
   * Tests update path that adds 'styles' property to editors with list plugin.
   */
  public function testRunUpdates(): void {
    // Basic HTML editor has list plugin and source editing plugin.
    $basic = Editor::load('basic_html');
    $basic_data = $basic->toArray();
    $this->assertArrayHasKey('ckeditor5_list', $basic_data['settings']['plugins']);
    $this->assertArrayHasKey('ckeditor5_sourceEditing', $basic_data['settings']['plugins']);
    $this->assertArrayNotHasKey('styles', $basic_data['settings']['plugins']['ckeditor5_list']['properties']);

    // Full HTML editor has list plugin, but does not have source editing
    // plugin.
    $full = Editor::load('full_html');
    $full_data = $full->toArray();
    $this->assertArrayHasKey('ckeditor5_list', $full_data['settings']['plugins']);
    $this->assertArrayNotHasKey('ckeditor5_sourceEditing', $full_data['settings']['plugins']);
    $this->assertArrayNotHasKey('styles', $full_data['settings']['plugins']['ckeditor5_list']['properties']);

    // After updates, both Basic and Full HTML editors have the 'styles'
    // property set for the list plugin.
    $this->runUpdates();
    $basic = Editor::load('basic_html');
    $basic_data = $basic->toArray();
    $this->assertArrayHasKey('ckeditor5_list', $basic_data['settings']['plugins']);
    $this->assertTrue($basic_data['settings']['plugins']['ckeditor5_list']['properties']['styles']);
    $this->assertArrayHasKey('ckeditor5_sourceEditing', $basic_data['settings']['plugins']);

    $full = Editor::load('full_html');
    $full_data = $full->toArray();
    $this->assertArrayHasKey('ckeditor5_list', $full_data['settings']['plugins']);
    $this->assertFalse($full_data['settings']['plugins']['ckeditor5_list']['properties']['styles']);
    $this->assertArrayNotHasKey('ckeditor5_sourceEditing', $full_data['settings']['plugins']);
  }

}
