<?php

namespace Drupal\Tests\forum\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for forum.module.
 *
 * Create, view, edit, delete, and change forum entries and verify its
 * consistency in the database.
 *
 * @group forum
 * @group #slow
 */
class ForumTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'taxonomy',
    'comment',
    'forum',
    'node',
    'block',
    'menu_ui',
    'help',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * A user with various administrative privileges.
   */
  protected $adminUser;

  /**
   * A user that can create forum topics and edit its own topics.
   */
  protected $editOwnTopicsUser;

  /**
   * A user that can create, edit, and delete forum topics.
   */
  protected $editAnyTopicsUser;

  /**
   * A user with no special privileges.
   */
  protected $webUser;

  /**
   * An administrative user who can bypass comment approval.
   */
  protected $postCommentUser;

  /**
   * An array representing a forum container.
   */
  protected $forumContainer;

  /**
   * An array representing a forum.
   */
  protected $forum;

  /**
   * An array representing a root forum.
   */
  protected $rootForum;

  /**
   * An array of forum topic node IDs.
   */
  protected $nids;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('page_title_block');

    // Create users.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'access help pages',
      'administer modules',
      'administer blocks',
      'administer forums',
      'administer menu',
      'administer taxonomy',
      'create forum content',
      'access comments',
    ]);
    $this->editAnyTopicsUser = $this->drupalCreateUser([
      'access administration pages',
      'access help pages',
      'create forum content',
      'edit any forum content',
      'delete any forum content',
    ]);
    $this->editOwnTopicsUser = $this->drupalCreateUser([
      'create forum content',
      'edit own forum content',
      'delete own forum content',
    ]);
    $this->webUser = $this->drupalCreateUser();
    $this->postCommentUser = $this->drupalCreateUser([
      'administer content types',
      'create forum content',
      'post comments',
      'skip comment approval',
      'access comments',
    ]);
    $this->drupalPlaceBlock('help_block', ['region' => 'help']);
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests forum functionality through the admin and user interfaces.
   */
  public function testForum() {
    // Check that the basic forum install creates a default forum topic
    $this->drupalGet('/forum');
    // Look for the "General discussion" default forum
    $this->assertSession()->linkExists('General discussion');
    $this->assertSession()->linkByHrefExists('/forum/1');
    // Check the presence of expected cache tags.
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:forum.settings');

    $this->drupalGet(Url::fromRoute('forum.page', ['taxonomy_term' => 1]));
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:forum.settings');

    // Do the admin tests.
    $this->doAdminTests($this->adminUser);

    // Check display order.
    $display = EntityViewDisplay::load('node.forum.default');
    $body = $display->getComponent('body');
    $comment = $display->getComponent('comment_forum');
    $taxonomy = $display->getComponent('taxonomy_forums');

    // Assert field order is body » taxonomy » comments.
    $this->assertLessThan($body['weight'], $taxonomy['weight']);
    $this->assertLessThan($comment['weight'], $body['weight']);

    // Check form order.
    $display = EntityFormDisplay::load('node.forum.default');
    $body = $display->getComponent('body');
    $comment = $display->getComponent('comment_forum');
    $taxonomy = $display->getComponent('taxonomy_forums');

    // Assert category comes before body in order.
    $this->assertLessThan($body['weight'], $taxonomy['weight']);

    $this->generateForumTopics();

    // Log in an unprivileged user to view the forum topics and generate an
    // active forum topics list.
    $this->drupalLogin($this->webUser);
    // Verify that this user is shown a message that they may not post content.
    $this->drupalGet('forum/' . $this->forum['tid']);
    $this->assertSession()->pageTextContains('You are not allowed to post new content in the forum');

    // Log in, and do basic tests for a user with permission to edit any forum
    // content.
    $this->doBasicTests($this->editAnyTopicsUser, TRUE);
    // Create a forum node authored by this user.
    $any_topics_user_node = $this->createForumTopic($this->forum, FALSE);

    // Log in, and do basic tests for a user with permission to edit only its
    // own forum content.
    $this->doBasicTests($this->editOwnTopicsUser, FALSE);
    // Create a forum node authored by this user.
    $own_topics_user_node = $this->createForumTopic($this->forum, FALSE);
    // Verify that this user cannot edit forum content authored by another user.
    $this->verifyForums($any_topics_user_node, FALSE, 403);

    // Verify that this user is shown a local task to add new forum content.
    $this->drupalGet('forum');
    $this->assertSession()->linkExists('Add new Forum topic');
    $this->drupalGet('forum/' . $this->forum['tid']);
    $this->assertSession()->linkExists('Add new Forum topic');

    // Log in a user with permission to edit any forum content.
    $this->drupalLogin($this->editAnyTopicsUser);
    // Verify that this user can edit forum content authored by another user.
    $this->verifyForums($own_topics_user_node, TRUE);

    // Verify the topic and post counts on the forum page.
    $this->drupalGet('forum');

    // Find the table row for the forum that has new posts. This cannot be
    // reliably identified by any CSS selector or its position in the table,
    // so look for an element with the "New posts" title and traverse up the
    // document tree until we get to the table row that contains it.
    $row = $this->assertSession()->elementExists('css', '[title="New posts"]');
    while ($row && $row->getTagName() !== 'tr') {
      $row = $row->getParent();
    }
    $this->assertNotEmpty($row);
    $cells = $row->findAll('css', 'td');
    $this->assertCount(4, $cells);

    // Topics cell contains number of topics (6), number of unread topics (also
    // 6), and the forum name.
    $this->assertEquals('6 6 new posts in forum ' . $this->forum['name'], $cells[1]->getText(), 'Number of topics found.');

    // Verify total number of posts in forum.
    $this->assertEquals('6', $cells[2]->getText(), 'Number of posts found.');

    // Test loading multiple forum nodes on the front page.
    $this->drupalLogin($this->drupalCreateUser([
      'administer content types',
      'create forum content',
      'post comments',
    ]));
    $this->drupalGet('admin/structure/types/manage/forum');
    $this->submitForm(['options[promote]' => 'promote'], 'Save');
    $this->createForumTopic($this->forum, FALSE);
    $this->createForumTopic($this->forum, FALSE);
    $this->drupalGet('node');

    // Test adding a comment to a forum topic.
    $node = $this->createForumTopic($this->forum, FALSE);
    $edit = [];
    $edit['comment_body[0][value]'] = $this->randomMachineName();
    $this->drupalGet('node/' . $node->id());
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Test editing a forum topic that has a comment.
    $this->drupalLogin($this->editAnyTopicsUser);
    $this->drupalGet('forum/' . $this->forum['tid']);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([], 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Test the root forum page title change.
    $this->drupalGet('forum');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:taxonomy.vocabulary.' . $this->forum['vid']);
    $this->assertSession()->titleEquals('Forums | Drupal');
    $vocabulary = Vocabulary::load($this->forum['vid']);
    $vocabulary->set('name', 'Discussions');
    $vocabulary->save();
    $this->drupalGet('forum');
    $this->assertSession()->titleEquals('Discussions | Drupal');

    // Test anonymous action link.
    $this->drupalLogout();
    $this->drupalGet('forum/' . $this->forum['tid']);
    $this->assertSession()->linkExists('Log in to post new content in the forum.');
  }

  /**
   * Tests that forum nodes can't be added without a parent.
   *
   * Verifies that forum nodes are not created without choosing "forum" from the
   * select list.
   */
  public function testAddOrphanTopic() {
    // Must remove forum topics to test creating orphan topics.
    $vid = $this->config('forum.settings')->get('vocabulary');
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->accessCheck(FALSE)
      ->condition('vid', $vid)
      ->execute();
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $terms = $term_storage->loadMultiple($tids);
    $term_storage->delete($terms);

    // Create an orphan forum item.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(10);
    $edit['body[0][value]'] = $this->randomMachineName(120);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/add/forum');
    $this->submitForm($edit, 'Save');

    $nid_count = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $this->assertEquals(0, $nid_count, 'A forum node was not created when missing a forum vocabulary.');

    // Reset the defaults for future tests.
    \Drupal::service('module_installer')->install(['forum']);
  }

  /**
   * Runs admin tests on the admin user.
   *
   * @param object $user
   *   The logged-in user.
   */
  private function doAdminTests($user) {
    // Log in the user.
    $this->drupalLogin($user);

    // Add forum to the Tools menu.
    $edit = [];
    $this->drupalGet('admin/structure/menu/manage/tools');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Edit forum taxonomy.
    // Restoration of the settings fails and causes subsequent tests to fail.
    $this->editForumVocabulary();
    // Create forum container.
    $this->forumContainer = $this->createForum('container');
    // Verify "edit container" link exists and functions correctly.
    $this->drupalGet('admin/structure/forum');
    // Verify help text is shown.
    $this->assertSession()->pageTextContains('Forums contain forum topics. Use containers to group related forums');
    // Verify action links are there.
    $this->assertSession()->linkExists('Add forum');
    $this->assertSession()->linkExists('Add container');
    $this->clickLink('edit container');
    $this->assertSession()->pageTextContains('Edit container');
    // Create forum inside the forum container.
    $this->forum = $this->createForum('forum', $this->forumContainer['tid']);
    // Verify the "edit forum" link exists and functions correctly.
    $this->drupalGet('admin/structure/forum');
    $this->clickLink('edit forum');
    $this->assertSession()->pageTextContains('Edit forum');
    // Navigate back to forum structure page.
    $this->drupalGet('admin/structure/forum');
    // Create second forum in container, destined to be deleted below.
    $delete_forum = $this->createForum('forum', $this->forumContainer['tid']);
    // Save forum overview.
    $this->drupalGet('admin/structure/forum/');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    // Delete this second forum.
    $this->deleteForum($delete_forum['tid']);
    // Create forum at the top (root) level.
    $this->rootForum = $this->createForum('forum');

    // Test vocabulary form alterations.
    $this->drupalGet('admin/structure/taxonomy/manage/forums');
    $this->assertSession()->buttonExists('Save');
    $this->assertSession()->buttonNotExists('Delete');

    // Test term edit form alterations.
    $this->drupalGet('taxonomy/term/' . $this->forumContainer['tid'] . '/edit');
    // Test parent field been hidden by forum module.
    $this->assertSession()->fieldNotExists('parent[]');

    // Create a default vocabulary named "Tags".
    $description = 'Use tags to group articles on similar topics into categories.';
    $help = 'Enter a comma-separated list of words to describe your content.';
    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'description' => $description,
      'vid' => 'tags',
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
      'help' => $help,
    ]);
    $vocabulary->save();
    // Test tags vocabulary form is not affected.
    $this->drupalGet('admin/structure/taxonomy/manage/tags');
    $this->assertSession()->buttonExists('Save');
    $this->assertSession()->linkExists('Delete');
    // Test tags vocabulary term form is not affected.
    $this->drupalGet('admin/structure/taxonomy/manage/tags/add');
    $this->assertSession()->fieldExists('parent[]');
    // Test relations widget exists.
    $this->assertSession()->elementExists('xpath', "//details[@id='edit-relations']");
  }

  /**
   * Edits the forum taxonomy.
   */
  public function editForumVocabulary() {
    // Backup forum taxonomy.
    $vid = $this->config('forum.settings')->get('vocabulary');
    $original_vocabulary = Vocabulary::load($vid);

    // Generate a random name and description.
    $edit = [
      'name' => $this->randomMachineName(10),
      'description' => $this->randomMachineName(100),
    ];

    // Edit the vocabulary.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $original_vocabulary->id());
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("Updated vocabulary {$edit['name']}.");

    // Grab the newly edited vocabulary.
    $current_vocabulary = Vocabulary::load($vid);

    // Make sure we actually edited the vocabulary properly.
    $this->assertEquals($edit['name'], $current_vocabulary->label(), 'The name was updated');
    $this->assertEquals($edit['description'], $current_vocabulary->getDescription(), 'The description was updated');

    // Restore the original vocabulary's name and description.
    $current_vocabulary->set('name', $original_vocabulary->label());
    $current_vocabulary->set('description', $original_vocabulary->getDescription());
    $current_vocabulary->save();
    // Reload vocabulary to make sure changes are saved.
    $current_vocabulary = Vocabulary::load($vid);
    $this->assertEquals($original_vocabulary->label(), $current_vocabulary->label(), 'The original vocabulary settings were restored');
  }

  /**
   * Creates a forum container or a forum.
   *
   * @param string $type
   *   The forum type (forum container or forum).
   * @param int $parent
   *   The forum parent. This defaults to 0, indicating a root forum.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   The created taxonomy term data.
   */
  public function createForum($type, $parent = 0) {
    // Generate a random name/description.
    $name = $this->randomMachineName(10);
    $description = $this->randomMachineName(100);

    $edit = [
      'name[0][value]' => $name,
      'description[0][value]' => $description,
      'parent[0]' => $parent,
      'weight' => '0',
    ];

    // Create forum.
    $this->drupalGet('admin/structure/forum/add/' . $type);
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $type = ($type == 'container') ? 'forum container' : 'forum';
    $this->assertSession()->pageTextContains('Created new ' . $type . ' ' . $name . '.');

    // Verify that the creation message contains a link to a term.
    $this->assertSession()->elementExists('xpath', '//div[@data-drupal-messages]//a[contains(@href, "term/")]');

    /** @var \Drupal\taxonomy\TermStorageInterface $taxonomy_term_storage */
    $taxonomy_term_storage = $this->container->get('entity_type.manager')->getStorage('taxonomy_term');
    // Verify forum.
    $term = $taxonomy_term_storage->loadByProperties([
      'vid' => $this->config('forum.settings')->get('vocabulary'),
      'name' => $name,
      'description__value' => $description,
    ]);
    $term = array_shift($term);
    $this->assertNotEmpty($term, "The forum type '$type' should exist in the database.");

    // Verify forum hierarchy.
    $tid = $term->id();
    $parent_tid = $taxonomy_term_storage->loadParents($tid);
    $parent_tid = empty($parent_tid) ? 0 : array_shift($parent_tid)->id();
    $this->assertSame($parent, $parent_tid, 'The ' . $type . ' is linked to its container');

    $forum = $taxonomy_term_storage->load($tid);
    $this->assertEquals(($type == 'forum container'), (bool) $forum->forum_container->value);
    return [
      'tid' => $tid,
      'name' => $term->getName(),
      'vid' => $term->bundle(),
    ];
  }

  /**
   * Deletes a forum.
   *
   * @param int $tid
   *   The forum ID.
   */
  public function deleteForum($tid) {
    // Delete the forum.
    $this->drupalGet('admin/structure/forum/edit/forum/' . $tid);
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains('Are you sure you want to delete the forum');
    $this->assertSession()->pageTextNotContains('Add forum');
    $this->assertSession()->pageTextNotContains('Add forum container');
    $this->submitForm([], 'Delete');

    // Assert that the forum no longer exists.
    $this->drupalGet('forum/' . $tid);
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Runs basic tests on the indicated user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The logged in user.
   * @param bool $admin
   *   User has 'access administration pages' privilege.
   */
  private function doBasicTests($user, $admin) {
    // Log in the user.
    $this->drupalLogin($user);
    // Attempt to create forum topic under a container.
    $this->createForumTopic($this->forumContainer, TRUE);
    // Create forum node.
    $node = $this->createForumTopic($this->forum, FALSE);
    // Verify the user has access to all the forum nodes.
    $this->verifyForums($node, $admin);
  }

  /**
   * Tests a forum with a new post displays properly.
   */
  public function testForumWithNewPost() {
    // Log in as the first user.
    $this->drupalLogin($this->adminUser);
    // Create a forum container.
    $this->forumContainer = $this->createForum('container');
    // Create a forum.
    $this->forum = $this->createForum('forum');
    // Create a topic.
    $node = $this->createForumTopic($this->forum, FALSE);

    // Log in as a second user.
    $this->drupalLogin($this->postCommentUser);
    // Post a reply to the topic.
    $edit = [];
    $edit['subject[0][value]'] = $this->randomMachineName();
    $edit['comment_body[0][value]'] = $this->randomMachineName();
    $this->drupalGet('node/' . $node->id());
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Test adding a new comment.
    $this->clickLink('Add new comment');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('comment_body[0][value]');

    // Log in as the first user.
    $this->drupalLogin($this->adminUser);
    // Check that forum renders properly.
    $this->drupalGet("forum/{$this->forum['tid']}");
    $this->assertSession()->statusCodeEquals(200);

    // Verify there is no unintentional HTML tag escaping.
    $this->assertSession()->assertNoEscaped('<');
  }

  /**
   * Creates a forum topic.
   *
   * @param array $forum
   *   A forum array.
   * @param bool $container
   *   TRUE if $forum is a container; FALSE otherwise.
   *
   * @return object|null
   *   The created topic node or NULL if the forum is a container.
   */
  public function createForumTopic($forum, $container = FALSE) {
    // Generate a random subject/body.
    $title = $this->randomMachineName(20);
    $body = $this->randomMachineName(200);

    $edit = [
      'title[0][value]' => $title,
      'body[0][value]' => $body,
    ];
    $tid = $forum['tid'];

    // Create the forum topic, preselecting the forum ID via a URL parameter.
    $this->drupalGet('node/add/forum', ['query' => ['forum_id' => $tid]]);
    $this->submitForm($edit, 'Save');

    if ($container) {
      $this->assertSession()->pageTextNotContains("Forum topic $title has been created.");
      $this->assertSession()->pageTextContains("The item {$forum['name']} is a forum container, not a forum.");
      return;
    }
    else {
      $this->assertSession()->pageTextContains("Forum topic $title has been created.");
      $this->assertSession()->pageTextNotContains("The item {$forum['name']} is a forum container, not a forum.");

      // Verify that the creation message contains a link to a node.
      $this->assertSession()->elementExists('xpath', '//div[@data-drupal-messages]//a[contains(@href, "node/")]');
    }

    // Retrieve node object, ensure that the topic was created and in the proper forum.
    $node = $this->drupalGetNodeByTitle($title);
    $this->assertNotNull($node, new FormattableMarkup('Node @title was loaded', ['@title' => $title]));
    $this->assertEquals($tid, $node->taxonomy_forums->target_id, 'Saved forum topic was in the expected forum');

    // View forum topic.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->pageTextContains($body);

    return $node;
  }

  /**
   * Verifies that the logged in user has access to a forum node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node being checked.
   * @param bool $admin
   *   Boolean to indicate whether the user can 'access administration pages'.
   * @param int $response
   *   The expected HTTP response code.
   */
  private function verifyForums(EntityInterface $node, $admin, $response = 200) {
    $response2 = ($admin) ? 200 : 403;

    // View forum help node.
    $this->drupalGet('admin/help/forum');
    $this->assertSession()->statusCodeEquals($response2);
    if ($response2 == 200) {
      $this->assertSession()->titleEquals('Forum | Drupal');
      $this->assertSession()->pageTextContains('Forum');
    }

    // View forum container page.
    $this->verifyForumView($this->forumContainer);
    // View forum page.
    $this->verifyForumView($this->forum, $this->forumContainer);
    // View root forum page.
    $this->verifyForumView($this->rootForum);

    // View forum node.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals($node->label() . ' | Drupal');
    $breadcrumb_build = [
      Link::createFromRoute('Home', '<front>'),
      Link::createFromRoute('Forums', 'forum.index'),
      Link::createFromRoute($this->forumContainer['name'], 'forum.page', ['taxonomy_term' => $this->forumContainer['tid']]),
      Link::createFromRoute($this->forum['name'], 'forum.page', ['taxonomy_term' => $this->forum['tid']]),
    ];
    $breadcrumb = [
      '#theme' => 'breadcrumb',
      '#links' => $breadcrumb_build,
    ];
    $this->assertSession()->responseContains(\Drupal::service('renderer')->renderRoot($breadcrumb));

    // View forum edit node.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals($response);
    if ($response == 200) {
      $this->assertSession()->titleEquals('Edit Forum topic ' . $node->label() . ' | Drupal');
    }

    if ($response == 200) {
      // Edit forum node (including moving it to another forum).
      $edit = [];
      $edit['title[0][value]'] = 'node/' . $node->id();
      $edit['body[0][value]'] = $this->randomMachineName(256);
      // Assume the topic is initially associated with $forum.
      $edit['taxonomy_forums'] = $this->rootForum['tid'];
      $edit['shadow'] = TRUE;
      $this->drupalGet('node/' . $node->id() . '/edit');
      $this->submitForm($edit, 'Save');
      $this->assertSession()->pageTextContains('Forum topic ' . $edit['title[0][value]'] . ' has been updated.');

      // Verify topic was moved to a different forum.
      $forum_tid = $this->container
        ->get('database')
        ->select('forum', 'f')
        ->fields('f', ['tid'])
        ->condition('nid', $node->id())
        ->condition('vid', $node->getRevisionId())
        ->execute()
        ->fetchField();
      $this->assertSame($this->rootForum['tid'], $forum_tid, 'The forum topic is linked to a different forum');

      // Delete forum node.
      $this->drupalGet('node/' . $node->id() . '/delete');
      $this->submitForm([], 'Delete');
      $this->assertSession()->statusCodeEquals($response);
      $this->assertSession()->pageTextContains("Forum topic {$edit['title[0][value]']} has been deleted.");
    }
  }

  /**
   * Verifies the display of a forum page.
   *
   * @param array $forum
   *   A row from the taxonomy_term_data table in an array.
   * @param array $parent
   *   (optional) An array representing the forum's parent.
   */
  private function verifyForumView($forum, $parent = NULL) {
    // View forum page.
    $this->drupalGet('forum/' . $forum['tid']);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals($forum['name'] . ' | Drupal');

    $breadcrumb_build = [
      Link::createFromRoute('Home', '<front>'),
      Link::createFromRoute('Forums', 'forum.index'),
    ];
    if (isset($parent)) {
      $breadcrumb_build[] = Link::createFromRoute($parent['name'], 'forum.page', ['taxonomy_term' => $parent['tid']]);
    }

    $breadcrumb = [
      '#theme' => 'breadcrumb',
      '#links' => $breadcrumb_build,
    ];
    $this->assertSession()->responseContains(\Drupal::service('renderer')->renderRoot($breadcrumb));
  }

  /**
   * Generates forum topics.
   */
  private function generateForumTopics() {
    $this->nids = [];
    for ($i = 0; $i < 5; $i++) {
      $node = $this->createForumTopic($this->forum, FALSE);
      $this->nids[] = $node->id();
    }
  }

  /**
   * Evaluate whether "Add new Forum topic" button is present or not.
   */
  public function testForumTopicButton() {
    $this->drupalLogin($this->adminUser);

    // Validate that link doesn't exist on the forum container page.
    $forum_container = $this->createForum('container');
    $this->drupalGet('forum/' . $forum_container['tid']);
    $this->assertSession()->linkNotExists('Add new Forum topic');

    // Validate that link exists on forum page.
    $forum = $this->createForum('forum');
    $this->drupalGet('forum/' . $forum['tid']);
    $this->assertSession()->linkExists('Add new Forum topic');
  }

}
