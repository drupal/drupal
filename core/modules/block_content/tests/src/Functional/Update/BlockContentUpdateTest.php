<?php

namespace Drupal\Tests\block_content\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests update functions for the Block Content module.
 *
 * @group block_content
 */
class BlockContentUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests moving the custom block library to Content.
   *
   * @see block_content_post_update_move_custom_block_library()
   */
  public function testMoveCustomBlockLibraryToContent(): void {
    $user = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($user);
    $this->drupalGet('admin/structure/block/block-content');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/content/block-content');
    $this->assertSession()->statusCodeEquals(404);

    $this->runUpdates();

    // Load and initialize the block_content view.
    $view = View::load('block_content');
    $data = $view->toArray();
    // Check that the path, description, and menu options have been updated.
    $this->assertEquals('admin/content/block-content', $data['display']['page_1']['display_options']['path']);
    $this->assertEquals('Create and edit custom block content.', $data['display']['page_1']['display_options']['menu']['description']);
    $this->assertFalse($data['display']['page_1']['display_options']['menu']['expanded']);
    $this->assertEquals('system.admin_content', $data['display']['page_1']['display_options']['menu']['parent']);

    // Check the new path is accessible.
    $user = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($user);
    $this->drupalGet('admin/content/block-content');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the block_content view isn't updated if the path has been modified.
   *
   * @see block_content_post_update_move_custom_block_library()
   */
  public function testCustomBlockLibraryPathOverridden(): void {
    $view = View::load('block_content');
    $display =& $view->getDisplay('page_1');
    $display['display_options']['path'] = 'some/custom/path';
    $view->save();

    $this->runUpdates();

    $view = View::load('block_content');
    $data = $view->toArray();
    $this->assertEquals('some/custom/path', $data['display']['page_1']['display_options']['path']);
  }

}
