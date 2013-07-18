<?php

/**
 * @file
 * Tests for forum.module.
 */

namespace Drupal\forum\Tests;

use Drupal\Core\Language\Language;
use Drupal\Core\Entity\EntityInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the Forum module.
 */
class ForumTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'comment', 'forum', 'node', 'block', 'menu', 'help');

  /**
   * A user with various administrative privileges.
   */
  protected $admin_user;

  /**
   * A user that can create forum topics and edit its own topics.
   */
  protected $edit_own_topics_user;

  /**
   * A user that can create, edit, and delete forum topics.
   */
  protected $edit_any_topics_user;

  /**
   * A user with no special privileges.
   */
  protected $web_user;

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
  protected $root_forum;

  /**
   * An array of forum topic node IDs.
   */
  protected $nids;

  public static function getInfo() {
    return array(
      'name' => 'Forum functionality',
      'description' => 'Create, view, edit, delete, and change forum entries and verify its consistency in the database.',
      'group' => 'Forum',
    );
  }

  function setUp() {
    parent::setUp();

    // Create users.
    $this->admin_user = $this->drupalCreateUser(array(
      'access administration pages',
      'administer modules',
      'administer blocks',
      'administer forums',
      'administer menu',
      'administer taxonomy',
      'create forum content',
      'access comments',
    ));
    $this->edit_any_topics_user = $this->drupalCreateUser(array(
      'access administration pages',
      'create forum content',
      'edit any forum content',
      'delete any forum content',
    ));
    $this->edit_own_topics_user = $this->drupalCreateUser(array(
      'create forum content',
      'edit own forum content',
      'delete own forum content',
    ));
    $this->web_user = $this->drupalCreateUser();
    $this->post_comment_user = $this->drupalCreateUser(array(
      'administer content types',
      'create forum content',
      'post comments',
      'skip comment approval',
      'access comments',
    ));
  }

  /**
   * Tests disabling and re-enabling the Forum module.
   */
  function testEnableForumField() {
    $this->drupalLogin($this->admin_user);

    // Disable the Forum module.
    $edit = array();
    $edit['modules[Core][forum][enable]'] = FALSE;
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), 'Modules status has been updated.');
    $this->rebuildContainer();
    $this->assertFalse(module_exists('forum'), 'Forum module is not enabled.');

    // Attempt to re-enable the Forum module and ensure it does not try to
    // recreate the taxonomy_forums field.
    $edit = array();
    $edit['modules[Core][forum][enable]'] = 'forum';
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), 'Modules status has been updated.');
    $this->rebuildContainer();
    $this->assertTrue(module_exists('forum'), 'Forum module is enabled.');
  }

  /**
   * Tests forum functionality through the admin and user interfaces.
   */
  function testForum() {
    //Check that the basic forum install creates a default forum topic
    $this->drupalGet("/forum");
    // Look for the "General discussion" default forum
    $this->assertText(t("General discussion"), "Found the default forum at the /forum listing");

    // Do the admin tests.
    $this->doAdminTests($this->admin_user);

    $this->generateForumTopics($this->forum);

    // Login an unprivileged user to view the forum topics and generate an
    // active forum topics list.
    $this->drupalLogin($this->web_user);
    // Verify that this user is shown a message that they may not post content.
    $this->drupalGet('forum/' . $this->forum['tid']);
    $this->assertText(t('You are not allowed to post new content in the forum'), "Authenticated user without permission to post forum content is shown message in local tasks to that effect.");


    // Log in, and do basic tests for a user with permission to edit any forum
    // content.
    $this->doBasicTests($this->edit_any_topics_user, TRUE);
    // Create a forum node authored by this user.
    $any_topics_user_node = $this->createForumTopic($this->forum, FALSE);

    // Log in, and do basic tests for a user with permission to edit only its
    // own forum content.
    $this->doBasicTests($this->edit_own_topics_user, FALSE);
    // Create a forum node authored by this user.
    $own_topics_user_node = $this->createForumTopic($this->forum, FALSE);
    // Verify that this user cannot edit forum content authored by another user.
    $this->verifyForums($this->edit_any_topics_user, $any_topics_user_node, FALSE, 403);

    // Verify that this user is shown a local task to add new forum content.
    $this->drupalGet('forum');
    $this->assertLink(t('Add new Forum topic'));
    $this->drupalGet('forum/' . $this->forum['tid']);
    $this->assertLink(t('Add new Forum topic'));

    // Login a user with permission to edit any forum content.
    $this->drupalLogin($this->edit_any_topics_user);
    // Verify that this user can edit forum content authored by another user.
    $this->verifyForums($this->edit_own_topics_user, $own_topics_user_node, TRUE);

    // Verify the topic and post counts on the forum page.
    $this->drupalGet('forum');

    // Verify row for testing forum.
    $forum_arg = array(':forum' => 'forum-list-' . $this->forum['tid']);

    // Topics cell contains number of topics and number of unread topics.
    $xpath = $this->buildXPathQuery('//tr[@id=:forum]//td[@class="topics"]', $forum_arg);
    $topics = $this->xpath($xpath);
    $topics = trim($topics[0]);
    $this->assertEqual($topics, '6', 'Number of topics found.');

    // Verify the number of unread topics.
    $unread_topics = _forum_topics_unread($this->forum['tid'], $this->edit_any_topics_user->id());
    $unread_topics = format_plural($unread_topics, '1 new post', '@count new posts');
    $xpath = $this->buildXPathQuery('//tr[@id=:forum]//td[@class="topics"]//a', $forum_arg);
    $this->assertFieldByXPath($xpath, $unread_topics, 'Number of unread topics found.');
    // Verify that the forum name is in the unread topics text.
    $xpath = $this->buildXPathQuery('//tr[@id=:forum]//em[@class="placeholder"]', $forum_arg);
    $this->assertFieldByXpath($xpath, $this->forum['name'], 'Forum name found in unread topics text.');

    // Verify total number of posts in forum.
    $xpath = $this->buildXPathQuery('//tr[@id=:forum]//td[@class="posts"]', $forum_arg);
    $this->assertFieldByXPath($xpath, '6', 'Number of posts found.');

    // Test loading multiple forum nodes on the front page.
    $this->drupalLogin($this->drupalCreateUser(array('administer content types', 'create forum content', 'post comments')));
    $this->drupalPost('admin/structure/types/manage/forum', array('settings[node][options][promote]' => 'promote'), t('Save content type'));
    $this->createForumTopic($this->forum, FALSE);
    $this->createForumTopic($this->forum, FALSE);
    $this->drupalGet('node');

    // Test adding a comment to a forum topic.
    $node = $this->createForumTopic($this->forum, FALSE);
    $edit = array();
    $edit['comment_body[' . Language::LANGCODE_NOT_SPECIFIED . '][0][value]'] = $this->randomName();
    $this->drupalPost("node/$node->nid", $edit, t('Save'));
    $this->assertResponse(200);

    // Test editing a forum topic that has a comment.
    $this->drupalLogin($this->edit_any_topics_user);
    $this->drupalGet('forum/' . $this->forum['tid']);
    $this->drupalPost("node/$node->nid/edit", array(), t('Save'));
    $this->assertResponse(200);

    // Test the root forum page title change.
    $this->drupalGet('forum');
    $this->assertTitle(t('Forums | Drupal'));
    $vocabulary = entity_load('taxonomy_vocabulary', $this->forum['vid']);
    $vocabulary->set('name', 'Discussions');
    $vocabulary->save();
    $this->drupalGet('forum');
    $this->assertTitle(t('Discussions | Drupal'));
  }

  /**
   * Tests that forum nodes can't be added without a parent.
   *
   * Verifies that forum nodes are not created without choosing "forum" from the
   * select list.
   */
  function testAddOrphanTopic() {
    // Must remove forum topics to test creating orphan topics.
    $vid = config('forum.settings')->get('vocabulary');
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vid)
      ->execute();
    entity_delete_multiple('taxonomy_term', $tids);

    // Create an orphan forum item.
    $this->drupalLogin($this->admin_user);
    $this->drupalPost('node/add/forum', array('title' => $this->randomName(10), 'body[' . Language::LANGCODE_NOT_SPECIFIED .'][0][value]' => $this->randomName(120)), t('Save'));

    $nid_count = db_query('SELECT COUNT(nid) FROM {node}')->fetchField();
    $this->assertEqual(0, $nid_count, 'A forum node was not created when missing a forum vocabulary.');

    // Reset the defaults for future tests.
    module_enable(array('forum'));
  }

  /**
   * Runs admin tests on the admin user.
   *
   * @param object $user
   *   The logged-in user.
   */
  private function doAdminTests($user) {
    // Login the user.
    $this->drupalLogin($user);

    // Retrieve forum menu id.
    $mlid = db_query_range("SELECT mlid FROM {menu_links} WHERE link_path = 'forum' AND menu_name = 'tools' AND module = 'system' ORDER BY mlid ASC", 0, 1)->fetchField();

    // Add forum to the Tools menu.
    $edit = array();
    $this->drupalPost('admin/structure/menu/manage/tools', $edit, t('Save'));
    $this->assertResponse(200);

    // Edit forum taxonomy.
    // Restoration of the settings fails and causes subsequent tests to fail.
    $this->forumContainer = $this->editForumVocabulary();
    // Create forum container.
    $this->forumContainer = $this->createForum('container');
    // Verify "edit container" link exists and functions correctly.
    $this->drupalGet('admin/structure/forum');
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
    // Create second forum in container.
    $this->delete_forum = $this->createForum('forum', $this->forumContainer['tid']);
    // Save forum overview.
    $this->drupalPost('admin/structure/forum/', array(), t('Save'));
    $this->assertRaw(t('The configuration options have been saved.'));
    // Delete this second forum.
    $this->deleteForum($this->delete_forum['tid']);
    // Create forum at the top (root) level.
    $this->root_forum = $this->createForum('forum');

    // Test vocabulary form alterations.
    $this->drupalGet('admin/structure/taxonomy/manage/forums/edit');
    $this->assertFieldByName('op', t('Save'), 'Save button found.');
    $this->assertNoFieldByName('op', t('Delete'), 'Delete button not found.');

    // Test term edit form alterations.
    $this->drupalGet('taxonomy/term/' . $this->forumContainer['tid'] . '/edit');
    // Test parent field been hidden by forum module.
    $this->assertNoField('parent[]', 'Parent field not found.');

    // Create a default vocabulary named "Tags".
    $description = 'Use tags to group articles on similar topics into categories.';
    $help = 'Enter a comma-separated list of words to describe your content.';
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => 'Tags',
      'description' => $description,
      'vid' => 'tags',
      'langcode' => language_default()->id,
      'help' => $help,
    ));
    $vocabulary->save();
    // Test tags vocabulary form is not affected.
    $this->drupalGet('admin/structure/taxonomy/manage/tags/edit');
    $this->assertFieldByName('op', t('Save'), 'Save button found.');
    $this->assertFieldByName('op', t('Delete'), 'Delete button found.');
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
    $vid = config('forum.settings')->get('vocabulary');
    $original_vocabulary = entity_load('taxonomy_vocabulary', $vid);

    // Generate a random name and description.
    $edit = array(
      'name' => $this->randomName(10),
      'description' => $this->randomName(100),
    );

    // Edit the vocabulary.
    $this->drupalPost('admin/structure/taxonomy/manage/' . $original_vocabulary->id() . '/edit', $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertRaw(t('Updated vocabulary %name.', array('%name' => $edit['name'])), 'Vocabulary was edited');

    // Grab the newly edited vocabulary.
    $current_vocabulary = entity_load('taxonomy_vocabulary', $vid);

    // Make sure we actually edited the vocabulary properly.
    $this->assertEqual($current_vocabulary->name, $edit['name'], 'The name was updated');
    $this->assertEqual($current_vocabulary->description, $edit['description'], 'The description was updated');

    // Restore the original vocabulary's name and description.
    $current_vocabulary->set('name', $original_vocabulary->name);
    $current_vocabulary->set('description', $original_vocabulary->description);
    $current_vocabulary->save();
    // Reload vocabulary to make sure changes are saved.
    $current_vocabulary = entity_load('taxonomy_vocabulary', $vid);
    $this->assertEqual($current_vocabulary->name, $original_vocabulary->name, 'The original vocabulary settings were restored');
  }

  /**
   * Creates a forum container or a forum.
   *
   * @param $type
   *   The forum type (forum container or forum).
   * @param $parent
   *   The forum parent. This defaults to 0, indicating a root forum.
   *
   * @return
   *   The created taxonomy term data.
   */
  function createForum($type, $parent = 0) {
    // Generate a random name/description.
    $name = $this->randomName(10);
    $description = $this->randomName(100);

    $edit = array(
      'name' => $name,
      'description[value]' => $description,
      'parent[0]' => $parent,
      'weight' => '0',
    );

    // Create forum.
    $this->drupalPost('admin/structure/forum/add/' . $type, $edit, t('Save'));
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
    $term = db_query("SELECT * FROM {taxonomy_term_data} t WHERE t.vid = :vid AND t.name = :name AND t.description = :desc", array(':vid' => config('forum.settings')->get('vocabulary'), ':name' => $name, ':desc' => $description))->fetchAssoc();
    $this->assertTrue(!empty($term), 'The ' . $type . ' exists in the database');

    // Verify forum hierarchy.
    $tid = $term['tid'];
    $parent_tid = db_query("SELECT t.parent FROM {taxonomy_term_hierarchy} t WHERE t.tid = :tid", array(':tid' => $tid))->fetchField();
    $this->assertTrue($parent == $parent_tid, 'The ' . $type . ' is linked to its container');

    return $term;
  }

  /**
   * Deletes a forum.
   *
   * @param $tid
   *   The forum ID.
   */
  function deleteForum($tid) {
    // Delete the forum.
    $this->drupalPost('admin/structure/forum/edit/forum/' . $tid, array(), t('Delete'));
    $this->assertText('Are you sure you want to delete the forum');
    $this->assertNoText('Add forum');
    $this->assertNoText('Add forum container');
    $this->drupalPost(NULL, array(), t('Delete'));

    // Assert that the forum no longer exists.
    $this->drupalGet('forum/' . $tid);
    $this->assertResponse(404, 'The forum was not found');

    // Assert that the associated term has been removed from the
    // forum_containers variable.
    $containers = config('forum.settings')->get('containers');
    $this->assertFalse(in_array($tid, $containers), 'The forum_containers variable has been updated.');
  }

  /**
   * Runs basic tests on the indicated user.
   *
   * @param $user
   *   The logged in user.
   * @param $admin
   *   User has 'access administration pages' privilege.
   */
  private function doBasicTests($user, $admin) {
    // Login the user.
    $this->drupalLogin($user);
    // Attempt to create forum topic under a container.
    $this->createForumTopic($this->forumContainer, TRUE);
    // Create forum node.
    $node = $this->createForumTopic($this->forum, FALSE);
    // Verify the user has access to all the forum nodes.
    $this->verifyForums($user, $node, $admin);
  }

  /**
   * Tests a forum with a new post displays properly.
   */
  function testForumWithNewPost() {
    // Login as the first user.
    $this->drupalLogin($this->admin_user);
    // Create a forum container.
    $this->forumContainer = $this->createForum('container');
    // Create a forum.
    $this->forum = $this->createForum('forum');
    // Create a topic.
    $node = $this->createForumTopic($this->forum, FALSE);

    // Login as a second user.
    $this->drupalLogin($this->post_comment_user);
    // Post a reply to the topic.
    $edit = array();
    $edit['subject'] = $this->randomName();
    $edit['comment_body[' . Language::LANGCODE_NOT_SPECIFIED . '][0][value]'] = $this->randomName();
    $this->drupalPost("node/$node->nid", $edit, t('Save'));
    $this->assertResponse(200);

    // Login as the first user.
    $this->drupalLogin($this->admin_user);
    // Check that forum renders properly.
    $this->drupalGet("forum/{$this->forum['tid']}");
    $this->assertResponse(200);
  }

  /**
   * Creates a forum topic.
   *
   * @param array $forum
   *   A forum array.
   * @param boolean $container
   *   TRUE if $forum is a container; FALSE otherwise.
   *
   * @return object
   *   The created topic node.
   */
  function createForumTopic($forum, $container = FALSE) {
    // Generate a random subject/body.
    $title = $this->randomName(20);
    $body = $this->randomName(200);

    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $edit = array(
      "title" => $title,
      "body[$langcode][0][value]" => $body,
    );
    $tid = $forum['tid'];

    // Create the forum topic, preselecting the forum ID via a URL parameter.
    $this->drupalPost('node/add/forum/' . $tid, $edit, t('Save'));

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
    $this->assertEqual($node->taxonomy_forums[Language::LANGCODE_NOT_SPECIFIED][0]['target_id'], $tid, 'Saved forum topic was in the expected forum');

    // View forum topic.
    $this->drupalGet('node/' . $node->nid);
    $this->assertRaw($title, 'Subject was found');
    $this->assertRaw($body, 'Body was found');

    return $node;
  }

  /**
   * Verifies that the logged in user has access to a forum node.
   *
   * @param $node_user
   *   The user who creates the node.
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node being checked.
   * @param $admin
   *   Boolean to indicate whether the user can 'access administration pages'.
   * @param $response
   *   The exptected HTTP response code.
   */
  private function verifyForums($node_user, EntityInterface $node, $admin, $response = 200) {
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
    $this->verifyForumView($this->root_forum);

    // View forum node.
    $this->drupalGet('node/' . $node->nid);
    $this->assertResponse(200);
    $this->assertTitle($node->label() . ' | Drupal', 'Forum node was displayed');
    $breadcrumb = array(
      l(t('Home'), NULL),
      l(t('Forums'), 'forum'),
      l($this->forumContainer['name'], 'forum/' . $this->forumContainer['tid']),
      l($this->forum['name'], 'forum/' . $this->forum['tid']),
    );
    $this->assertRaw(theme('breadcrumb', array('breadcrumb' => $breadcrumb)), 'Breadcrumbs were displayed');

    // View forum edit node.
    $this->drupalGet('node/' . $node->nid . '/edit');
    $this->assertResponse($response);
    if ($response == 200) {
      $this->assertTitle('Edit Forum topic ' . $node->label() . ' | Drupal', 'Forum edit node was displayed');
    }

    if ($response == 200) {
      // Edit forum node (including moving it to another forum).
      $edit = array();
      $langcode = Language::LANGCODE_NOT_SPECIFIED;
      $edit["title"] = 'node/' . $node->nid;
      $edit["body[$langcode][0][value]"] = $this->randomName(256);
      // Assume the topic is initially associated with $forum.
      $edit["taxonomy_forums[$langcode]"] = $this->root_forum['tid'];
      $edit['shadow'] = TRUE;
      $this->drupalPost('node/' . $node->nid . '/edit', $edit, t('Save'));
      $this->assertRaw(t('Forum topic %title has been updated.', array('%title' => $edit["title"])), 'Forum node was edited');

      // Verify topic was moved to a different forum.
      $forum_tid = db_query("SELECT tid FROM {forum} WHERE nid = :nid AND vid = :vid", array(
        ':nid' => $node->nid,
        ':vid' => $node->vid,
      ))->fetchField();
      $this->assertTrue($forum_tid == $this->root_forum['tid'], 'The forum topic is linked to a different forum');

      // Delete forum node.
      $this->drupalPost('node/' . $node->nid . '/delete', array(), t('Delete'));
      $this->assertResponse($response);
      $this->assertRaw(t('Forum topic %title has been deleted.', array('%title' => $edit['title'])), 'Forum node was deleted');
    }
  }

  /**
   * Verifies the display of a forum page.
   *
   * @param $forum
   *   A row from the taxonomy_term_data table in an array.
   * @param $parent
   *   (optional) An array representing the forum's parent.
   */
  private function verifyForumView($forum, $parent = NULL) {
    // View forum page.
    $this->drupalGet('forum/' . $forum['tid']);
    $this->assertResponse(200);
    $this->assertTitle($forum['name'] . ' | Drupal');

    $breadcrumb = array(
      l(t('Home'), NULL),
      l(t('Forums'), 'forum'),
    );
    if (isset($parent)) {
      $breadcrumb[] = l($parent['name'], 'forum/' . $parent['tid']);
    }

    $this->assertRaw(theme('breadcrumb', array('breadcrumb' => $breadcrumb)));
  }

  /**
   * Generates forum topics.
   *
   * @param array $forum
   *   The forum array (a row from taxonomy_term_data table).
   */
  private function generateForumTopics($forum) {
    $this->nids = array();
    for ($i = 0; $i < 5; $i++) {
      $node = $this->createForumTopic($this->forum, FALSE);
      $this->nids[] = $node->nid;
    }
  }
}
