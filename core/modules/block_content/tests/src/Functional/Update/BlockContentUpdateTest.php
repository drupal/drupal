<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional\Update;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\user\Entity\User;
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
   * Tests converting block types' `revision` flag to boolean.
   */
  public function testConvertBlockContentTypeRevisionFlagToBoolean(): void {
    $no_new_revisions = BlockContentType::create([
      'id' => 'no_new_revisions',
      'label' => 'Does not create new revisions',
      'revision' => 0,
    ]);
    $no_new_revisions->trustData()->save();
    $new_revisions = BlockContentType::create([
      'id' => 'new_revisions',
      'label' => 'Creates new revisions',
      'revision' => 1,
    ]);
    $new_revisions->trustData()->save();
    // Ensure that an integer was stored, so we can be sure that the update
    // path converts it to a boolean.
    $this->assertSame(0, $no_new_revisions->get('revision'));
    $this->assertSame(1, $new_revisions->get('revision'));

    $this->runUpdates();
    $this->assertFalse(BlockContentType::load('no_new_revisions')->get('revision'));
    $this->assertTrue(BlockContentType::load('new_revisions')->get('revision'));
  }

  /**
   * Tests moving the content block library to Content.
   *
   * @see block_content_post_update_move_custom_block_library()
   */
  public function testMoveCustomBlockLibraryToContent(): void {
    $user = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($user);
    $this->drupalGet('admin/structure/block/block-content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Custom blocks');
    $this->assertSession()->pageTextContains('Custom block library');
    $this->drupalGet('admin/content/block');
    $this->assertSession()->statusCodeEquals(404);

    $this->runUpdates();

    // Load and initialize the block_content view.
    $view = View::load('block_content');
    $data = $view->toArray();
    // Check that the path, description, and menu options have been updated.
    $this->assertEquals('admin/content/block', $data['display']['page_1']['display_options']['path']);
    $this->assertEquals('Create and edit block content.', $data['display']['page_1']['display_options']['menu']['description']);
    $this->assertFalse($data['display']['page_1']['display_options']['menu']['expanded']);
    $this->assertEquals('system.admin_content', $data['display']['page_1']['display_options']['menu']['parent']);
    $this->assertEquals('Content blocks', $view->label());
    $this->assertEquals('Blocks', $data['display']['page_1']['display_options']['menu']['title']);

    // Check the new path is accessible.
    $user = $this->drupalCreateUser(['access block library']);
    $this->drupalLogin($user);
    $this->drupalGet('admin/content/block');
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

  /**
   * Tests the permissions are updated for users with "administer blocks".
   *
   * @see block_content_post_update_sort_permissions()
   */
  public function testBlockLibraryPermissionsUpdate(): void {
    $user = $this->drupalCreateUser(['administer blocks']);
    $this->assertTrue($user->hasPermission('administer blocks'));
    $this->assertFalse($user->hasPermission('administer block content'));
    $this->assertFalse($user->hasPermission('administer block types'));
    $this->assertFalse($user->hasPermission('access block library'));

    $this->runUpdates();

    $user = User::load($user->id());
    $this->assertTrue($user->hasPermission('administer blocks'));
    $this->assertTrue($user->hasPermission('administer block content'));
    $this->assertTrue($user->hasPermission('administer block types'));
    $this->assertTrue($user->hasPermission('access block library'));
  }

}
