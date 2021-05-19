<?php

namespace Drupal\Tests\path\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Tests path aliases with Content Moderation.
 *
 * @group content_moderation
 * @group path
 */
class PathContentModerationTest extends BrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'path',
    'content_moderation',
    'content_translation',
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
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->rebuildContainer();

    // Created a content type.
    $this->drupalCreateContentType([
      'name' => 'moderated',
      'type' => 'moderated',
    ]);

    // Set the content type as moderated.
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'moderated');
    $workflow->save();

    $this->drupalLogin($this->rootUser);

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => 1];
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, 'Save settings');

    // Enable translation for moderated node.
    $edit = [
      'entity_types[node]' => 1,
      'settings[node][moderated][translatable]' => 1,
      'settings[node][moderated][fields][path]' => 1,
      'settings[node][moderated][fields][body]' => 1,
      'settings[node][moderated][settings][language][language_alterable]' => 1,
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, 'Save configuration');
    \Drupal::entityTypeManager()->clearCachedDefinitions();
  }

  /**
   * Tests node path aliases on a moderated content type.
   */
  public function testNodePathAlias() {
    // Create some moderated content with a path alias.
    $this->drupalGet('node/add/moderated');
    $this->assertSession()->fieldValueEquals('path[0][alias]', '');
    $this->submitForm([
      'title[0][value]' => 'moderated content',
      'path[0][alias]' => '/moderated-content',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $node = $this->getNodeByTitle('moderated content');

    // Add a pending revision with the same alias.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('path[0][alias]', '/moderated-content');
    $this->submitForm([
      'title[0][value]' => 'pending revision',
      'path[0][alias]' => '/moderated-content',
      'moderation_state[0][state]' => 'draft',
    ], 'Save');
    $this->assertSession()->pageTextNotContains('You can only change the URL alias for the published version of this content.');

    // Create some moderated content with no path alias.
    $this->drupalGet('node/add/moderated');
    $this->assertSession()->fieldValueEquals('path[0][alias]', '');
    $this->submitForm([
      'title[0][value]' => 'moderated content 2',
      'path[0][alias]' => '',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $node = $this->getNodeByTitle('moderated content 2');

    // Add a pending revision with a new alias.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('path[0][alias]', '');
    $this->submitForm([
      'title[0][value]' => 'pending revision',
      'path[0][alias]' => '/pending-revision',
      'moderation_state[0][state]' => 'draft',
    ], 'Save');
    $this->assertSession()->pageTextContains('You can only change the URL alias for the published version of this content.');

    // Create some moderated content with no path alias.
    $this->drupalGet('node/add/moderated');
    $this->assertSession()->fieldValueEquals('path[0][alias]', '');
    $this->submitForm([
      'title[0][value]' => 'moderated content 3',
      'path[0][alias]' => '',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $node = $this->getNodeByTitle('moderated content 3');

    // Add a pending revision with no path alias.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('path[0][alias]', '');
    $this->submitForm([
      'title[0][value]' => 'pending revision',
      'path[0][alias]' => '',
      'moderation_state[0][state]' => 'draft',
    ], 'Save');
    $this->assertSession()->pageTextNotContains('You can only change the URL alias for the published version of this content.');
  }

  /**
   * Tests that translated and moderated node can get new draft revision.
   */
  public function testTranslatedModeratedNodeAlias() {
    // Create one node with a random alias.
    $default_node = $this->drupalCreateNode([
      'type' => 'moderated',
      'langcode' => 'en',
      'moderation_state' => 'published',
      'path' => '/' . $this->randomMachineName(),
    ]);

    // Add published translation with another alias.
    $this->drupalGet('node/' . $default_node->id());
    $this->drupalGet('node/' . $default_node->id() . '/translations');
    $this->clickLink('Add');
    $edit_translation = [
      'body[0][value]' => $this->randomMachineName(),
      'moderation_state[0][state]' => 'published',
      'path[0][alias]' => '/' . $this->randomMachineName(),
    ];
    $this->submitForm($edit_translation, 'Save (this translation)');
    // Confirm that the alias works.
    $this->drupalGet('fr' . $edit_translation['path[0][alias]']);
    $this->assertSession()->pageTextContains($edit_translation['body[0][value]']);

    $default_path = $default_node->path->alias;
    $translation_path = 'fr' . $edit_translation['path[0][alias]'];

    $this->assertPathsAreAccessible([$default_path, $translation_path]);

    // Try to create new draft revision for translation with a new path alias.
    $edit_new_translation_draft_with_alias = [
      'moderation_state[0][state]' => 'draft',
      'path[0][alias]' => '/' . $this->randomMachineName(),
    ];
    $this->drupalPostForm('fr/node/' . $default_node->id() . '/edit', $edit_new_translation_draft_with_alias, 'Save (this translation)');
    // Confirm the expected error.
    $this->assertSession()->pageTextContains('You can only change the URL alias for the published version of this content.');

    // Create new draft revision for translation without changing path alias.
    $edit_new_translation_draft = [
      'body[0][value]' => $this->randomMachineName(),
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('fr/node/' . $default_node->id() . '/edit', $edit_new_translation_draft, 'Save (this translation)');
    // Confirm that the new draft revision was created.
    $this->assertSession()->pageTextNotContains('You can only change the URL alias for the published version of this content.');
    $this->assertSession()->pageTextContains($edit_new_translation_draft['body[0][value]']);
    $this->assertPathsAreAccessible([$default_path, $translation_path]);

    // Try to create a new draft revision for translation with path alias from
    // the original language's default revision.
    $edit_new_translation_draft_with_defaults_alias = [
      'moderation_state[0][state]' => 'draft',
      'path[0][alias]' => $default_node->path->alias,
    ];
    $this->drupalPostForm('fr/node/' . $default_node->id() . '/edit', $edit_new_translation_draft_with_defaults_alias, 'Save (this translation)');
    // Verify the expected error.
    $this->assertSession()->pageTextContains('You can only change the URL alias for the published version of this content.');

    // Try to create new draft revision for translation with deleted (empty)
    // path alias.
    $edit_new_translation_draft_empty_alias = [
      'body[0][value]' => $this->randomMachineName(),
      'moderation_state[0][state]' => 'draft',
      'path[0][alias]' => '',
    ];
    $this->drupalPostForm('fr/node/' . $default_node->id() . '/edit', $edit_new_translation_draft_empty_alias, 'Save (this translation)');
    // Confirm the expected error.
    $this->assertSession()->pageTextContains('You can only change the URL alias for the published version of this content.');

    // Create new default (published) revision for translation with new path
    // alias.
    $edit_new_translation = [
      'body[0][value]' => $this->randomMachineName(),
      'moderation_state[0][state]' => 'published',
      'path[0][alias]' => '/' . $this->randomMachineName(),
    ];
    $this->drupalPostForm('fr/node/' . $default_node->id() . '/edit', $edit_new_translation, 'Save (this translation)');
    // Confirm that the new published revision was created.
    $this->assertSession()->pageTextNotContains('You can only change the URL alias for the published version of this content.');
    $this->assertSession()->pageTextContains($edit_new_translation['body[0][value]']);
    $this->assertSession()->addressEquals('fr' . $edit_new_translation['path[0][alias]']);
    $this->assertPathsAreAccessible([$default_path]);
  }

  /**
   * Helper callback to verify paths are responding with status 200.
   *
   * @param string[] $paths
   *   An array of paths to check for.
   */
  public function assertPathsAreAccessible(array $paths) {
    foreach ($paths as $path) {
      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(200);
    }
  }

}
