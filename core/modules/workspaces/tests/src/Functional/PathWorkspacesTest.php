<?php

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_translation\Traits\ContentTranslationTestTrait;
use Drupal\Tests\WaitTerminateTestTrait;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests path aliases with workspaces.
 *
 * @group path
 * @group workspaces
 */
class PathWorkspacesTest extends BrowserTestBase {

  use ContentTranslationTestTrait;
  use WorkspaceTestUtilities;
  use WaitTerminateTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'content_translation',
    'node',
    'path',
    'workspaces',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    static::createLanguageFromLangcode('ro');
    $this->rebuildContainer();

    // Create a content type.
    $this->drupalCreateContentType([
      'name' => 'article',
      'type' => 'article',
    ]);

    $this->drupalLogin($this->rootUser);

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => 1];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    // Enable translation for article node.
    static::enableContentTranslation('node', 'article');

    $this->setupWorkspaceSwitcherBlock();

    // The \Drupal\path_alias\AliasWhitelist service performs cache clears after
    // Drupal has flushed the response to the client. We use
    // WaitTerminateTestTrait to wait for Drupal to do this before continuing.
    $this->setWaitForTerminate();
  }

  /**
   * Tests path aliases with workspaces.
   */
  public function testPathAliases() {
    // Create a published node in Live, without an alias.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'status' => TRUE,
    ]);

    // Switch to Stage and create an alias for the node.
    $stage = Workspace::load('stage');
    $this->switchToWorkspace($stage);

    $edit = [
      'path[0][alias]' => '/' . $this->randomMachineName(),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Check that the node can be accessed in Stage with the given alias.
    $path = $edit['path[0][alias]'];
    $this->assertAccessiblePaths([$path]);

    // Check that the 'preload-paths' cache includes the active workspace ID in
    // the cache key.
    $this->assertNotEmpty(\Drupal::cache('data')->get('preload-paths:stage:/node/1'));
    $this->assertFalse(\Drupal::cache('data')->get('preload-paths:/node/1'));

    // Check that the alias can not be accessed in Live.
    $this->switchToLive();
    $this->assertNotAccessiblePaths([$path]);
    $this->assertFalse(\Drupal::cache('data')->get('preload-paths:/node/1'));

    // Publish the workspace and check that the alias can be accessed in Live.
    $stage->publish();
    $this->assertAccessiblePaths([$path]);
    $this->assertNotEmpty(\Drupal::cache('data')->get('preload-paths:/node/1'));
  }

  /**
   * Tests path aliases with workspaces and user switching.
   */
  public function testPathAliasesUserSwitch() {
    // Create a published node in Live, without an alias.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'status' => TRUE,
    ]);

    // Switch to Stage and create an alias for the node.
    $stage = Workspace::load('stage');
    $this->switchToWorkspace($stage);

    $edit = [
      'path[0][alias]' => '/' . $this->randomMachineName(),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Check that the node can be accessed in Stage with the given alias.
    $path = $edit['path[0][alias]'];
    $this->assertAccessiblePaths([$path]);

    // Check that the 'preload-paths' cache includes the active workspace ID in
    // the cache key.
    $this->assertNotEmpty(\Drupal::cache('data')->get('preload-paths:stage:/node/1'));
    $this->assertFalse(\Drupal::cache('data')->get('preload-paths:/node/1'));

    // Check that the alias can not be accessed in Live, by logging out without
    // an explicit switch.
    $this->drupalLogout();
    $this->assertNotAccessiblePaths([$path]);
    $this->assertFalse(\Drupal::cache('data')->get('preload-paths:/node/1'));

    // Publish the workspace and check that the alias can be accessed in Live.
    $this->drupalLogin($this->rootUser);
    $stage->publish();

    $this->drupalLogout();
    $this->assertAccessiblePaths([$path]);
    $this->assertNotEmpty(\Drupal::cache('data')->get('preload-paths:/node/1'));
  }

  /**
   * Tests path aliases with workspaces for translatable nodes.
   */
  public function testPathAliasesWithTranslation() {
    $stage = Workspace::load('stage');

    // Create one node with a random alias.
    $default_node = $this->drupalCreateNode([
      'type' => 'article',
      'langcode' => 'en',
      'status' => TRUE,
      'path' => '/' . $this->randomMachineName(),
    ]);

    // Add published translation with another alias.
    $this->drupalGet('node/' . $default_node->id());
    $this->drupalGet('node/' . $default_node->id() . '/translations');
    $this->clickLink('Add');
    $edit_translation = [
      'body[0][value]' => $this->randomMachineName(),
      'status[value]' => TRUE,
      'path[0][alias]' => '/' . $this->randomMachineName(),
    ];
    $this->submitForm($edit_translation, 'Save (this translation)');
    // Confirm that the alias works.
    $this->drupalGet('ro' . $edit_translation['path[0][alias]']);
    $this->assertSession()->pageTextContains($edit_translation['body[0][value]']);

    $default_path = $default_node->path->alias;
    $translation_path = 'ro' . $edit_translation['path[0][alias]'];

    $this->assertAccessiblePaths([$default_path, $translation_path]);

    $this->switchToWorkspace($stage);

    $this->assertAccessiblePaths([$default_path, $translation_path]);

    // Create a workspace-specific revision for the translation with a new path
    // alias.
    $edit_new_translation_draft_with_alias = [
      'path[0][alias]' => '/' . $this->randomMachineName(),
    ];
    $this->drupalGet('ro/node/' . $default_node->id() . '/edit');
    $this->submitForm($edit_new_translation_draft_with_alias, 'Save (this translation)');
    $stage_translation_path = 'ro' . $edit_new_translation_draft_with_alias['path[0][alias]'];

    // The new alias of the translation should be available in Stage, but not
    // available in Live.
    $this->assertAccessiblePaths([$default_path, $stage_translation_path]);

    // Check that the previous (Live) path alias no longer works.
    $this->assertNotAccessiblePaths([$translation_path]);

    // Switch out of Stage and check that the initial path aliases still work.
    $this->switchToLive();
    $this->assertAccessiblePaths([$default_path, $translation_path]);
    $this->assertNotAccessiblePaths([$stage_translation_path]);

    // Switch back to Stage.
    $this->switchToWorkspace($stage);

    // Create new workspace-specific revision for translation without changing
    // the path alias.
    $edit_new_translation_draft = [
      'body[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalGet('ro/node/' . $default_node->id() . '/edit');
    $this->submitForm($edit_new_translation_draft, 'Save (this translation)');
    // Confirm that the new draft revision was created.
    $this->assertSession()->pageTextContains($edit_new_translation_draft['body[0][value]']);

    // Switch out of Stage and check that the initial path aliases still work.
    $this->switchToLive();
    $this->assertAccessiblePaths([$default_path, $translation_path]);
    $this->assertNotAccessiblePaths([$stage_translation_path]);

    // Switch back to Stage.
    $this->switchToWorkspace($stage);
    $this->assertAccessiblePaths([$default_path, $stage_translation_path]);
    $this->assertNotAccessiblePaths([$translation_path]);

    // Create a new workspace-specific revision for translation with path alias
    // from the original language's default revision.
    $edit_new_translation_draft_with_defaults_alias = [
      'path[0][alias]' => $default_node->path->alias,
    ];
    $this->drupalGet('ro/node/' . $default_node->id() . '/edit');
    $this->submitForm($edit_new_translation_draft_with_defaults_alias, 'Save (this translation)');

    // Switch out of Stage and check that the initial path aliases still work.
    $this->switchToLive();
    $this->assertAccessiblePaths([$default_path, $translation_path]);
    $this->assertNotAccessiblePaths([$stage_translation_path]);

    // Check that only one path alias (the original one) is available in Stage.
    $this->switchToWorkspace($stage);
    $this->assertAccessiblePaths([$default_path]);
    $this->assertNotAccessiblePaths([$translation_path, $stage_translation_path]);

    // Create new workspace-specific revision for translation with a deleted
    // (empty) path alias.
    $edit_new_translation_draft_empty_alias = [
      'body[0][value]' => $this->randomMachineName(),
      'path[0][alias]' => '',
    ];
    $this->drupalGet('ro/node/' . $default_node->id() . '/edit');
    $this->submitForm($edit_new_translation_draft_empty_alias, 'Save (this translation)');

    // Check that only one path alias (the original one) is available now.
    $this->switchToLive();
    $this->assertAccessiblePaths([$default_path, $translation_path]);
    $this->assertNotAccessiblePaths([$stage_translation_path]);

    $this->switchToWorkspace($stage);
    $this->assertAccessiblePaths([$default_path]);
    $this->assertNotAccessiblePaths([$translation_path, $stage_translation_path]);

    // Create a new workspace-specific revision for the translation with a new
    // path alias.
    $edit_new_translation = [
      'body[0][value]' => $this->randomMachineName(),
      'path[0][alias]' => '/' . $this->randomMachineName(),
    ];
    $this->drupalGet('ro/node/' . $default_node->id() . '/edit');
    $this->submitForm($edit_new_translation, 'Save (this translation)');

    // Confirm that the new revision was created.
    $this->assertSession()->pageTextContains($edit_new_translation['body[0][value]']);
    $this->assertSession()->addressEquals('ro' . $edit_new_translation['path[0][alias]']);

    // Check that only the new path alias of the translation can be accessed.
    $new_stage_translation_path = 'ro' . $edit_new_translation['path[0][alias]'];
    $this->assertAccessiblePaths([$default_path, $new_stage_translation_path]);
    $this->assertNotAccessiblePaths([$stage_translation_path]);

    // Switch out of Stage and check that none of the workspace-specific path
    // aliases can be accessed.
    $this->switchToLive();
    $this->assertAccessiblePaths([$default_path, $translation_path]);
    $this->assertNotAccessiblePaths([$stage_translation_path, $new_stage_translation_path]);

    // Publish Stage and check that its path alias for the translation can be
    // accessed.
    $stage->publish();
    $this->assertAccessiblePaths([$default_path, $new_stage_translation_path]);
    $this->assertNotAccessiblePaths([$stage_translation_path]);
  }

  /**
   * Helper callback to verify paths are responding with status 200.
   *
   * @param string[] $paths
   *   An array of paths to check for.
   *
   * @internal
   */
  protected function assertAccessiblePaths(array $paths): void {
    foreach ($paths as $path) {
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(200);
    }
  }

  /**
   * Helper callback to verify paths are responding with status 404.
   *
   * @param string[] $paths
   *   An array of paths to check for.
   *
   * @internal
   */
  protected function assertNotAccessiblePaths(array $paths): void {
    foreach ($paths as $path) {
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(404);
    }
  }

}
