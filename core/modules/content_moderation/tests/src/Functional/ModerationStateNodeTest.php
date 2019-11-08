<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Tests general content moderation workflow for nodes.
 *
 * @group content_moderation
 */
class ModerationStateNodeTest extends ModerationStateTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $this->createContentTypeFromUi('Moderated content', 'moderated_content', TRUE);
    $this->grantUserPermissionToCreateContentOfType($this->adminUser, 'moderated_content');
  }

  /**
   * Tests creating and deleting content.
   */
  public function testCreatingContent() {
    $this->drupalPostForm('node/add/moderated_content', [
      'title[0][value]' => 'moderated content',
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));
    $node = $this->getNodeByTitle('moderated content');
    if (!$node) {
      $this->fail('Test node was not saved correctly.');
    }
    $this->assertEqual('draft', $node->moderation_state->value);

    $path = 'node/' . $node->id() . '/edit';
    // Set up published revision.
    $this->drupalPostForm($path, [
      'moderation_state[0][state]' => 'published',
    ], t('Save'));
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);
    /* @var \Drupal\node\NodeInterface $node */
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());
    $this->assertTrue($node->isPublished());
    $this->assertEqual('published', $node->moderation_state->value);

    // Verify that the state field is not shown.
    $this->assertNoText('Published');

    // Delete the node.
    $this->drupalPostForm('node/' . $node->id() . '/delete', [], t('Delete'));
    $this->assertText(t('The Moderated content moderated content has been deleted.'));

    // Disable content moderation.
    $edit['bundles[moderated_content]'] = FALSE;
    $this->drupalPostForm('admin/config/workflow/workflows/manage/editorial/type/node', $edit, t('Save'));
    // Ensure the parent environment is up-to-date.
    // @see content_moderation_workflow_insert()
    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Create a new node.
    $this->drupalPostForm('node/add/moderated_content', [
      'title[0][value]' => 'non-moderated content',
    ], t('Save'));

    $node = $this->getNodeByTitle('non-moderated content');
    if (!$node) {
      $this->fail('Non-moderated test node was not saved correctly.');
    }
    $this->assertFalse($node->hasField('moderation_state'));
  }

  /**
   * Tests edit form destinations.
   */
  public function testFormSaveDestination() {
    // Create new moderated content in draft.
    $this->drupalPostForm('node/add/moderated_content', [
      'title[0][value]' => 'Some moderated content',
      'body[0][value]' => 'First version of the content.',
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));

    $node = $this->drupalGetNodeByTitle('Some moderated content');
    $edit_path = sprintf('node/%d/edit', $node->id());

    // After saving, we should be at the canonical URL and viewing the first
    // revision.
    $this->assertUrl(Url::fromRoute('entity.node.canonical', ['node' => $node->id()]));
    $this->assertText('First version of the content.');

    // Create a new draft; after saving, we should still be on the canonical
    // URL, but viewing the second revision.
    $this->drupalPostForm($edit_path, [
      'body[0][value]' => 'Second version of the content.',
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));
    $this->assertUrl(Url::fromRoute('entity.node.canonical', ['node' => $node->id()]));
    $this->assertText('Second version of the content.');

    // Make a new published revision; after saving, we should be at the
    // canonical URL.
    $this->drupalPostForm($edit_path, [
      'body[0][value]' => 'Third version of the content.',
      'moderation_state[0][state]' => 'published',
    ], t('Save'));
    $this->assertUrl(Url::fromRoute('entity.node.canonical', ['node' => $node->id()]));
    $this->assertText('Third version of the content.');

    // Make a new pending revision; after saving, we should be on the "Latest
    // version" tab.
    $this->drupalPostForm($edit_path, [
      'body[0][value]' => 'Fourth version of the content.',
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));
    $this->assertUrl(Url::fromRoute('entity.node.latest_version', ['node' => $node->id()]));
    $this->assertText('Fourth version of the content.');
  }

  /**
   * Tests pagers aren't broken by content_moderation.
   */
  public function testPagers() {
    // Create 51 nodes to force the pager.
    foreach (range(1, 51) as $delta) {
      Node::create([
        'type' => 'moderated_content',
        'uid' => $this->adminUser->id(),
        'title' => 'Node ' . $delta,
        'status' => 1,
        'moderation_state' => 'published',
      ])->save();
    }
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/content');
    $element = $this->cssSelect('nav.pager li.is-active a');
    $url = $element[0]->getAttribute('href');
    $query = [];
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    $this->assertEqual(0, $query['page']);
  }

  /**
   * Tests the workflow when a user has no Content Moderation permissions.
   */
  public function testNoContentModerationPermissions() {
    $session_assert = $this->assertSession();

    // Create a user with quite advanced node permissions but no content
    // moderation permissions.
    $limited_user = $this->createUser([
      'administer nodes',
      'bypass node access',
    ]);
    $this->drupalLogin($limited_user);

    // Check the user can see the content entity form, but can't see the
    // moderation state select or save the entity form.
    $this->drupalGet('node/add/moderated_content');
    $session_assert->statusCodeEquals(200);
    $session_assert->fieldNotExists('moderation_state[0][state]');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'moderated content',
    ], 'Save');
    $session_assert->pageTextContains('You do not have access to transition from Draft to Draft');
  }

}
