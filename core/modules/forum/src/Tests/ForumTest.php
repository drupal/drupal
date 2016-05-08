<?php

namespace Drupal\forum\Tests;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests for forum.module.
 *
 * Create, view, edit, delete, and change forum entries and verify its
 * consistency in the database.
 *
 * @group forum
 */
class ForumTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'comment', 'forum', 'node', 'block', 'menu_ui', 'help');

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
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('page_title_block');

    // Create users.
    $this->adminUser = $this->drupalCreateUser(array(
      'access administration pages',
      'administer modules',
      'administer blocks',
      'administer forums',
      'administer menu',
      'administer taxonomy',
      'create forum content',
      'access comments',
    ));
    $this->editAnyTopicsUser = $this->drupalCreateUser(array(
      'access administration pages',
      'create forum content',
      'edit any forum content',
      'delete any forum content',
    ));
    $this->editOwnTopicsUser = $this->drupalCreateUser(array(
      'create forum content',
      'edit own forum content',
      'delete own forum content',
    ));
    $this->webUser = $this->drupalCreateUser();
    $this->postCommentUser = $this->drupalCreateUser(array(
      'administer content types',
      'create forum content',
      'post comments',
      'skip comment approval',
      'access comments',
    ));
    $this->drupalPlaceBlock('help_block', array('region' => 'help'));
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests forum functionality through the admin and user interfaces.
   */
  function testForum() {
    //Check that the basic forum install creates a default forum topic
    $this->drupalGet('/forum');
    // Look for the "General discussion" default forum
    $this->assertRaw(Link::createFromRoute(t('General discussion'), 'forum.page', ['taxonomy_term' => 1])->toString(), "Found the default forum at the /forum listing");
    // Check the presence of expected cache tags.
    $this->assertCacheTag('config:forum.settings');

    $this->drupalGet(Url::fromRoute('forum.page', ['taxonomy_term' => 1]));
    $this->assertCacheTag('config:forum.settings');

    // Do the admin tests.
    $this->doAdminTests($this->adminUser);

    // Check display order.
    $display = EntityViewDisplay::load('node.forum.default');
    $body = $display->getComponent('body');
    $comment = $display->getComponent('comment_forum');
    $taxonomy = $display->getComponent('taxonomy_forums');

    // Assert field order is body » taxonomy » comments.
    $this->assertTrue($taxonomy['weight'] < $body['weight']);
    $this->assertTrue($body['weight'] < $comment['weight']);

    // Check form order.
    $display = EntityFormDisplay::load('node.forum.default');
    $body = $display->getComponent('body');
    $comment = $display->getComponent('comment_forum');
    $taxonomy = $display->getComponent('taxonomy_forums');

    // Assert category comes before body in order.
    $this->assertTrue($taxonomy['weight'] < $body['weight']);

    $this->generateForumTopics();

    // Log in an unprivileged user to view the forum topics and generate an
    // active forum topics list.
    $this->drupalLogin($this->webUser);
    // Verify that this user is shown a message that they may not post content.
    $this->drupalGet('forum/' . $this->forum['tid']);
    $this->assertText(t('You are not allowed to post new content in the forum'), "Authenticated user without permission to post forum content is shown message in local tasks to that effect.");

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
    $this->assertLink(t('Add new Forum topic'));
    $this->drupalGet('forum/' . $this->forum['tid']);
    $this->assertLink(t('Add new Forum topic'));

    // Log in a user with permission to edit any forum content.
    $this->drupalLogin($this->editAnyTopicsUser);
    // Verify that this user can edit forum content authored by another user.
    $this->verifyForums($own_topics_user_node, TRUE);

    // Verify the topic and post counts on the forum page.
    $this->drupalGet('forum');

    // Verify row for testing forum.
    $forum_arg = array(':forum' => 'forum-list-' . $this->forum['tid']);

    // Topics cell contains number of topics and number of unread topics.
    $xpath = $this->buildXPathQuery('//tr[@id=:forum]//td[@class="forum__topics"]', $forum_arg);
    $topics = $this->xpath($xpath);
    $topics = trim($topics[0]);
    $this->assertEqual($topics, '6', 'Number of topics found.');

    // Verify the number of unread topics.
    $unread_topics = $this->container->get('forum_manager')->unreadTopics($this->forum['tid'], $this->editAnyTopicsUser->id());
    $unread_topics = \Drupal::translation()->formatPlural($unread_topics, '1 new post', '@count new posts');
    $xpath = $this->buildXPathQuery('//tr[@id=:forum]//td[@class="forum__topics"]//a', $forum_arg);
    $this->assertFieldByXPath($xpath, $unread_topics, 'Number of unread topics found.');
    // Verify that the forum name is in the unread topics text.
    $xpath = $this->buildXPathQuery('//tr[@id=:forum]//em[@class="placeholder"]', $forum_arg);
    $this->assertFieldByXpath($xpath, $this->forum['name'], 'Forum name found in unread topics text.');

    // Verify total number of posts in forum.
    $xpath = $this->buildXPathQuery('//tr[@id=:forum]//td[@class="forum__posts"]', $forum_arg);
    $this->assertFieldByXPath($xpath, '6', 'Number of posts found.');

    // Test loading multiple forum nodes on the front page.
    $this->drupalLogin($this->drupalCreateUser(array('administer content types', 'create forum content', 'post comments')));
    $this->drupalPostForm('admin/structure/types/manage/forum', array('options[promote]' => 'promote'), t('Save content type'));
    $this->createForumTopic($this->forum, FALSE);
    $this->createForumTopic($this->forum, FALSE);
    $this->drupalGet('node');

    // Test adding a comment to a forum topic.
    $node = $this->createForumTopic($this->forum, FALSE);
    $edit = array();
    $edit['comment_body[0][value]'] = $this->randomMachineName();
    $this->drupalPostForm('node/' . $node->id(), $edit, t('Save'));
    $this->assertResponse(200);

    // Test editing a forum topic that has a comment.
    $this->drupalLogin($this->editAnyTopicsUser);
    $this->drupalGet('forum/' . $this->forum['tid']);
    $this->drupalPostForm('node/' . $node->id() . '/edit', array(), t('Save'));
    $this->assertResponse(200);

    // Test the root forum page title change.
    $this->drupalGet('forum');
    $this->assertCacheTag('config:taxonomy.vocabulary.' . $this->forum['vid']);
    $this->assertTitle(t('Forums | Drupal'));
    $vocabulary = Vocabulary::load($this->forum['vid']);
    $vocabulary->set('name', 'Discussions');
    $vocabulary->save();
    $this->drupalGet('forum');
    $this->assertTitle(t('Discussions | Drupal'));

    // Test anonymous action link.
    $this->drupalLogout();
    $this->drupalGet('forum/' . $this->forum['tid']);
    $this->assertLink(t('Log in to post new content in the forum.'));
  }

  /**
   * Tests that forum nodes can't be added without a parent.
   *
   * Verifies that forum nodes are not created without choosing "forum" from the
   * select list.
   */
  function testAddOrphanTopic() {
    // Must remove forum topics to test creating orphan topics.
    $vid = $this->config('forum.settings')->get('vocabulary');
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vid)
      ->execute();
    entity_delete_multiple('taxonomy_term', $tids);

    // Create an orphan forum item.
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName(10);
    $edit['body[0][value]'] = $this->randomMachineName(120);
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('node/add/forum', $edit, t('Save'));

    $nid_count = db_query('SELECT COUNT(nid) FROM {node}')->fetchField();
    $this->assertEqual(0, $nid_count, 'A forum node was not created when missing a forum vocabulary.');

    // Reset the defaults for future tests.
    \Drupal::service('module_installer')->install(array('forum'));
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
    $edit = array();
    $this->drupalPostForm('admin/structure/menu/manage/tools', $edit, t('Save'));
    $this->assertResponse(200);

    // Edit forum taxonomy.
    // Restoration of the settings fails and causes subsequent tests to fail.
    $this->editForumVocabulary();
    // Create forum container.
    $this->forumContainer = $this->createForum('container');
    // Verify "edit container" link exists and functions correctly.
    $this->drupalGet('admin/structure/forum');
    // Verify help text is shown.
    $this->assertText(t('Forums contain forum topics. Use containers to group related forums'));
    // Verify action links are there.
    $this->assertLink('Add forum');
    $this->assertLink('Add container');
    $this->clickLink('edit container');
    $this->assertRaw('Edit container', 'Followed the link to edit the container');
    // Create forum inside the forum container.
    $this->forum = $this->createForum('forum', $this->forumContainer['tid']);
    // Verify the "edit forum" link exists and functions correctly.
    $this->drupalGet('admin/structure/forum');
    $this->clickLink('edit forum');
    $this->assertRaw('Edit forum', 'Followed the link to edit the forum');
    // Navigate back to forum structure page.
    $this->drupalGet('admin/structure/forum');
    // Create second forum in container, destined to be deleted below.
    $delete_forum = $this->createForum('forum', $this->forumContainer['tid']);
    // Save forum overview.
    $this->drupalPostForm('admin/structure/forum/', array(), t('Save'));
    $this->assertRaw(t('The configuration options have been saved.'));
    // Delete this second forum.
    $this->deleteForum($delete_forum['tid']);
    // Create forum at the top (root) level.
    $this->rootForum = $this->createForum('forum');

    // Test vocabulary form alterations.
    $this->drupalGet('admin/structure/taxonomy/manage/forums');
    $this->assertFieldByName('op', t('Save'), 'Save button found.');
    $this->assertNoFieldByName('op', t('Delete'), 'Delete button not found.');

    // Test term edit form alterations.
    $this->drupalGet('taxonomy/term/' . $this->forumContainer['tid'] . '/edit');
    // Test parent field been hidden by forum module.
    $this->assertNoField('parent[]', 'Parent field not found.');

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
    $this->assertFieldByName('op', t('Save'), 'Save button found.');
    $this->assertLink(t('Delete'));
    // Test tags vocabulary term form is not affected.
    $this->drupalGet('admin/structure/taxonomy/manage/tags/add');
    $this->assertField('parent[]', 'Parent field found.');
    // Test relations widget exists.
    $relations_widget = $this->xpath("//details[@id='edit-relations']");
    $this->assertTrue(isset($relations_widget[0]), 'Relations widget element found.');
  }

  /**
   * Edits the forum taxonomy.
   */
  function editForumVocabulary() {
    // Backup forum taxonomy.
    $vid = $this->config('forum.settings')->get('vocabulary');
    $original_vocabulary = Vocabulary::load($vid);

    // Generate a random name and description.
    $edit = array(
      'name' => $this->randomMachineName(10),
      'description' => $this->randomMachineName(100),
    );

    // Edit the vocabulary.
    $this->drupalPostForm('admin/structure/taxonomy/manage/' . $original_vocabulary->id(), $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertRaw(t('Updated vocabulary %name.', array('%name' => $edit['name'])), 'Vocabulary was edited');

    // Grab the newly edited vocabulary.
    $current_vocabulary = Vocabulary::load($vid);

    // Make sure we actually edited the vocabulary properly.
    $this->assertEqual($current_vocabulary->label(), $edit['name'], 'The name was updated');
    $this->assertEqual($current_vocabulary->getDescription(), $edit['description'], 'The description was updated');

    // Restore the original vocabulary's name and description.
    $current_vocabulary->set('name', $original_vocabulary->label());
    $current_vocabulary->set('description', $original_vocabulary->getDescription());
    $current_vocabulary->save();
    // Reload vocabulary to make sure changes are saved.
    $current_vocabulary = Vocabulary::load($vid);
    $this->assertEqual($current_vocabulary->label(), $original_vocabulary->label(), 'The original vocabulary settings were restored');
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
  function createForum($type, $parent = 0) {
    // Generate a random name/description.
    $name = $this->randomMachineName(10);
    $description = $this->randomMachineName(100);

    $edit = array(
      'name[0][value]' => $name,
      'description[0][value]' => $description,
      'parent[0]' => $parent,
      'weight' => '0',
    );

    // Create forum.
    $this->drupalPostForm('admin/structure/forum/add/' . $type, $edit, t('Save'));
    $this->assertResponse(200);
    $type = ($type == 'container') ? 'forum container' : 'forum';
    $this->assertRaw(
      t(
        'Created new @type %term.',
        array('%term' => $name, '@type' => t($type))
      ),
      format_string('@type was created', array('@type' => ucfirst($type)))
    );

    // Verify forum.
    $term = db_query("SELECT * FROM {taxonomy_term_field_data} t WHERE t.vid = :vid AND t.name = :name AND t.description__value = :desc AND t.default_langcode = 1", array(':vid' => $this->config('forum.settings')->get('vocabulary'), ':name' => $name, ':desc' => $description))->fetchAssoc();
    $this->assertTrue(!empty($term), 'The ' . $type . ' exists in the database');

    // Verify forum hierarchy.
    $tid = $term['tid'];
    $parent_tid = db_query("SELECT t.parent FROM {taxonomy_term_hierarchy} t WHERE t.tid = :tid", array(':tid' => $tid))->fetchField();
    $this->assertTrue($parent == $parent_tid, 'The ' . $type . ' is linked to its container');

    $forum = $this->container->get('entity.manager')->getStorage('taxonomy_term')->load($tid);
    $this->assertEqual(($type == 'forum container'), (bool) $forum->forum_container->value);
    return $term;
  }

  /**
   * Deletes a forum.
   *
   * @param int $tid
   *   The forum ID.
   */
  function deleteForum($tid) {
    // Delete the forum.
    $this->drupalGet('admin/structure/forum/edit/forum/' . $tid);
    $this->clickLink(t('Delete'));
    $this->assertText('Are you sure you want to delete the forum');
    $this->assertNoText('Add forum');
    $this->assertNoText('Add forum container');
    $this->drupalPostForm(NULL, array(), t('Delete'));

    // Assert that the forum no longer exists.
    $this->drupalGet('forum/' . $tid);
    $this->assertResponse(404, 'The forum was not found');
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
  function testForumWithNewPost() {
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
    $edit = array();
    $edit['subject[0][value]'] = $this->randomMachineName();
    $edit['comment_body[0][value]'] = $this->randomMachineName();
    $this->drupalPostForm('node/' . $node->id(), $edit, t('Save'));
    $this->assertResponse(200);

    // Test replying to a comment.
    $this->clickLink('Reply');
    $this->assertResponse(200);
    $this->assertFieldByName('comment_body[0][value]');

    // Log in as the first user.
    $this->drupalLogin($this->adminUser);
    // Check that forum renders properly.
    $this->drupalGet("forum/{$this->forum['tid']}");
    $this->assertResponse(200);

    // Verify there is no unintentional HTML tag escaping.
    $this->assertNoEscaped('<', '');
  }

  /**
   * Creates a forum topic.
   *
   * @param array $forum
   *   A forum array.
   * @param bool $container
   *   TRUE if $forum is a container; FALSE otherwise.
   *
   * @return object
   *   The created topic node.
   */
  function createForumTopic($forum, $container = FALSE) {
    // Generate a random subject/body.
    $title = $this->randomMachineName(20);
    $body = $this->randomMachineName(200);

    $edit = array(
      'title[0][value]' => $title,
      'body[0][value]' => $body,
    );
    $tid = $forum['tid'];

    // Create the forum topic, preselecting the forum ID via a URL parameter.
    $this->drupalPostForm('node/add/forum', $edit, t('Save'), array('query' => array('forum_id' => $tid)));

    $type = t('Forum topic');
    if ($container) {
      $this->assertNoRaw(t('@type %title has been created.', array('@type' => $type, '%title' => $title)), 'Forum topic was not created');
      $this->assertRaw(t('The item %title is a forum container, not a forum.', array('%title' => $forum['name'])), 'Error message was shown');
      return;
    }
    else {
      $this->assertRaw(t('@type %title has been created.', array('@type' => $type, '%title' => $title)), 'Forum topic was created');
      $this->assertNoRaw(t('The item %title is a forum container, not a forum.', array('%title' => $forum['name'])), 'No error message was shown');
    }

    // Retrieve node object, ensure that the topic was created and in the proper forum.
    $node = $this->drupalGetNodeByTitle($title);
    $this->assertTrue($node != NULL, format_string('Node @title was loaded', array('@title' => $title)));
    $this->assertEqual($node->taxonomy_forums->target_id, $tid, 'Saved forum topic was in the expected forum');

    // View forum topic.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw($title, 'Subject was found');
    $this->assertRaw($body, 'Body was found');

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
    $this->assertResponse($response2);
    if ($response2 == 200) {
      $this->assertTitle(t('Forum | Drupal'), 'Forum help title was displayed');
      $this->assertText(t('Forum'), 'Forum help node was displayed');
    }

    // View forum container page.
    $this->verifyForumView($this->forumContainer);
    // View forum page.
    $this->verifyForumView($this->forum, $this->forumContainer);
    // View root forum page.
    $this->verifyForumView($this->rootForum);

    // View forum node.
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);
    $this->assertTitle($node->label() . ' | Drupal', 'Forum node was displayed');
    $breadcrumb_build = array(
      Link::createFromRoute(t('Home'), '<front>'),
      Link::createFromRoute(t('Forums'), 'forum.index'),
      Link::createFromRoute($this->forumContainer['name'], 'forum.page', array('taxonomy_term' => $this->forumContainer['tid'])),
      Link::createFromRoute($this->forum['name'], 'forum.page', array('taxonomy_term' => $this->forum['tid'])),
    );
    $breadcrumb = array(
      '#theme' => 'breadcrumb',
      '#links' => $breadcrumb_build,
    );
    $this->assertRaw(\Drupal::service('renderer')->renderRoot($breadcrumb), 'Breadcrumbs were displayed');

    // View forum edit node.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertResponse($response);
    if ($response == 200) {
      $this->assertTitle('Edit Forum topic ' . $node->label() . ' | Drupal', 'Forum edit node was displayed');
    }

    if ($response == 200) {
      // Edit forum node (including moving it to another forum).
      $edit = array();
      $edit['title[0][value]'] = 'node/' . $node->id();
      $edit['body[0][value]'] = $this->randomMachineName(256);
      // Assume the topic is initially associated with $forum.
      $edit['taxonomy_forums'] = $this->rootForum['tid'];
      $edit['shadow'] = TRUE;
      $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
      $this->assertRaw(t('Forum topic %title has been updated.', array('%title' => $edit['title[0][value]'])), 'Forum node was edited');

      // Verify topic was moved to a different forum.
      $forum_tid = db_query("SELECT tid FROM {forum} WHERE nid = :nid AND vid = :vid", array(
        ':nid' => $node->id(),
        ':vid' => $node->getRevisionId(),
      ))->fetchField();
      $this->assertTrue($forum_tid == $this->rootForum['tid'], 'The forum topic is linked to a different forum');

      // Delete forum node.
      $this->drupalPostForm('node/' . $node->id() . '/delete', array(), t('Delete'));
      $this->assertResponse($response);
      $this->assertRaw(t('Forum topic %title has been deleted.', array('%title' => $edit['title[0][value]'])), 'Forum node was deleted');
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
    $this->assertResponse(200);
    $this->assertTitle($forum['name'] . ' | Drupal');

    $breadcrumb_build = array(
      Link::createFromRoute(t('Home'), '<front>'),
      Link::createFromRoute(t('Forums'), 'forum.index'),
    );
    if (isset($parent)) {
      $breadcrumb_build[] = Link::createFromRoute($parent['name'], 'forum.page', array('taxonomy_term' => $parent['tid']));
    }

    $breadcrumb = array(
      '#theme' => 'breadcrumb',
      '#links' => $breadcrumb_build,
    );
    $this->assertRaw(\Drupal::service('renderer')->renderRoot($breadcrumb), 'Breadcrumbs were displayed');
  }

  /**
   * Generates forum topics.
   */
  private function generateForumTopics() {
    $this->nids = array();
    for ($i = 0; $i < 5; $i++) {
      $node = $this->createForumTopic($this->forum, FALSE);
      $this->nids[] = $node->id();
    }
  }

}
