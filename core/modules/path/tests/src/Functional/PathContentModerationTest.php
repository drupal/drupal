<?php

namespace Drupal\Tests\path\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests path aliases with Content Moderation.
 *
 * @group content_moderation
 * @group path
 */
class PathContentModerationTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['node', 'path', 'content_moderation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Created a content type.
    $node_type = NodeType::create(['name' => 'moderated', 'type' => 'moderated']);
    $node_type->save();

    // Set the content type as moderated.
    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'moderated');
    $workflow->save();

    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests node path aliases on a moderated content type.
   */
  public function testNodePathAlias() {
    // Create some moderated content with a path alias.
    $this->drupalGet('node/add/moderated');
    $this->assertSession()->fieldValueEquals('path[0][alias]', '');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'moderated content',
      'path[0][alias]' => '/moderated-content',
    ], t('Save and Publish'));
    $node = $this->getNodeByTitle('moderated content');

    // Add a forward revision with the same alias.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('path[0][alias]', '/moderated-content');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'forward revision',
      'path[0][alias]' => '/moderated-content',
    ], t('Save and Create New Draft'));
    $this->assertSession()->pageTextNotContains('You can only change the URL alias for the published version of this content.');

    // Create some moderated content with no path alias.
    $this->drupalGet('node/add/moderated');
    $this->assertSession()->fieldValueEquals('path[0][alias]', '');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'moderated content 2',
      'path[0][alias]' => '',
    ], t('Save and Publish'));
    $node = $this->getNodeByTitle('moderated content 2');

    // Add a forward revision with a new alias.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('path[0][alias]', '');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'forward revision',
      'path[0][alias]' => '/forward-revision',
    ], t('Save and Create New Draft'));
    $this->assertSession()->pageTextContains('You can only change the URL alias for the published version of this content.');

    // Create some moderated content with no path alias.
    $this->drupalGet('node/add/moderated');
    $this->assertSession()->fieldValueEquals('path[0][alias]', '');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'moderated content 3',
      'path[0][alias]' => '',
    ], t('Save and Publish'));
    $node = $this->getNodeByTitle('moderated content 3');

    // Add a forward revision with no path alias.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('path[0][alias]', '');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'forward revision',
      'path[0][alias]' => '',
    ], t('Save and Create New Draft'));
    $this->assertSession()->pageTextNotContains('You can only change the URL alias for the published version of this content.');
  }

}
