<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Component\Serialization\Json;

/**
 * Create a node with revisions and test viewing, saving, reverting, and
 * deleting revisions for users with access for this content type.
 *
 * @group node
 */
class NodeRevisionsTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An array of node revisions.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * Revision log messages.
   *
   * @var array
   */
  protected $revisionLogs;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'contextual',
    'datetime',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable additional languages.
    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('it')->save();

    $field_storage_definition = [
      'field_name' => 'untranslatable_string_field',
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => FALSE,
    ];
    $field_storage = FieldStorageConfig::create($field_storage_definition);
    $field_storage->save();

    $field_definition = [
      'field_storage' => $field_storage,
      'bundle' => 'page',
    ];
    $field = FieldConfig::create($field_definition);
    $field->save();

    // Enable translation for page nodes.
    \Drupal::service('content_translation.manager')->setEnabled('node', 'page', TRUE);

    // Create and log in user.
    $web_user = $this->drupalCreateUser(
      [
        'view page revisions',
        'revert page revisions',
        'delete page revisions',
        'edit any page content',
        'delete any page content',
        'access contextual links',
        'translate any entity',
        'administer content types',
      ]
    );

    $this->drupalLogin($web_user);

    // Create initial node.
    $node = $this->drupalCreateNode();
    $settings = get_object_vars($node);
    $settings['revision'] = 1;
    $settings['isDefaultRevision'] = TRUE;

    $nodes = [];
    $logs = [];

    // Get original node.
    $nodes[] = clone $node;

    // Create three revisions.
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      $logs[] = $node->revision_log = $this->randomMachineName(32);

      // Create revision with a random title and body and update variables.
      $node->title = $this->randomMachineName();
      $node->body = [
        'value' => $this->randomMachineName(32),
        'format' => filter_default_format(),
      ];
      $node->untranslatable_string_field->value = $this->randomString();
      $node->setNewRevision();

      // Edit the 1st and 2nd revision with a different user.
      if ($i < 2) {
        $editor = $this->drupalCreateUser();
        $node->setRevisionUserId($editor->id());
      }
      else {
        $node->setRevisionUserId($web_user->id());
      }

      $node->save();

      // Make sure we get revision information.
      $node = Node::load($node->id());
      $nodes[] = clone $node;
    }

    $this->nodes = $nodes;
    $this->revisionLogs = $logs;
  }

  /**
   * Checks node revision related operations.
   */
  public function testRevisions() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $nodes = $this->nodes;
    $logs = $this->revisionLogs;

    // Get last node for simple checks.
    $node = $nodes[3];

    // Confirm the correct revision text appears on "view revisions" page.
    $this->drupalGet("node/" . $node->id() . "/revisions/" . $node->getRevisionId() . "/view");
    $this->assertSession()->pageTextContains($node->body->value);

    // Confirm the correct log message appears on "revisions overview" page.
    $this->drupalGet("node/" . $node->id() . "/revisions");
    foreach ($logs as $revision_log) {
      $this->assertSession()->pageTextContains($revision_log);
    }
    // Original author, and editor names should appear on revisions overview.
    $web_user = $nodes[0]->revision_uid->entity;
    $this->assertSession()->pageTextContains('by ' . $web_user->getAccountName());
    $editor = $nodes[2]->revision_uid->entity;
    $this->assertSession()->pageTextContains('by ' . $editor->getAccountName());

    // Confirm that this is the default revision.
    $this->assertTrue($node->isDefaultRevision(), 'Third node revision is the default one.');

    // Confirm that revisions revert properly.
    $this->drupalGet("node/" . $node->id() . "/revisions/" . $nodes[1]->getRevisionid() . "/revert");
    $this->submitForm([], 'Revert');
    $this->assertSession()->pageTextContains("Basic page {$nodes[1]->label()} has been reverted to the revision from {$this->container->get('date.formatter')->format($nodes[1]->getRevisionCreationTime())}.");
    $node_storage->resetCache([$node->id()]);
    $reverted_node = $node_storage->load($node->id());
    $this->assertSame($nodes[1]->body->value, $reverted_node->body->value, 'Node reverted correctly.');
    // Confirm the revision author is the user performing the revert.
    $this->assertSame($this->loggedInUser->id(), $reverted_node->getRevisionUserId(), 'Node revision author is user performing revert.');
    // And that its not the revision author.
    $this->assertNotSame($nodes[1]->getRevisionUserId(), $reverted_node->getRevisionUserId(), 'Node revision author is not original revision author.');

    // Confirm that this is not the default version.
    $node = node_revision_load($node->getRevisionId());
    $this->assertFalse($node->isDefaultRevision(), 'Third node revision is not the default one.');

    // Confirm revisions delete properly.
    $this->drupalGet("node/" . $node->id() . "/revisions/" . $nodes[1]->getRevisionId() . "/delete");
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains("Revision from {$this->container->get('date.formatter')->format($nodes[1]->getRevisionCreationTime())} of Basic page {$nodes[1]->label()} has been deleted.");
    $connection = Database::getConnection();
    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->allRevisions()
      ->condition('nid', $node->id())
      ->condition('vid', $nodes[1]->getRevisionId())
      ->execute();
    $this->assertCount(0, $nids);

    // Set the revision timestamp to an older date to make sure that the
    // confirmation message correctly displays the stored revision date.
    $old_revision_date = REQUEST_TIME - 86400;
    $connection->update('node_revision')
      ->condition('vid', $nodes[2]->getRevisionId())
      ->fields([
        'revision_timestamp' => $old_revision_date,
      ])
      ->execute();
    $this->drupalGet("node/" . $node->id() . "/revisions/" . $nodes[2]->getRevisionId() . "/revert");
    $this->submitForm([], 'Revert');
    $this->assertSession()->pageTextContains("Basic page {$nodes[2]->label()} has been reverted to the revision from {$this->container->get('date.formatter')->format($old_revision_date)}.");

    // Make a new revision and set it to not be default.
    // This will create a new revision that is not "front facing".
    $new_node_revision = clone $node;
    $new_body = $this->randomMachineName();
    $new_node_revision->body->value = $new_body;
    // Save this as a non-default revision.
    $new_node_revision->setNewRevision();
    $new_node_revision->isDefaultRevision = FALSE;
    $new_node_revision->save();

    // Verify that revision body text is not present on default version of node.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextNotContains($new_body);

    // Verify that the new body text is present on the revision.
    $this->drupalGet("node/" . $node->id() . "/revisions/" . $new_node_revision->getRevisionId() . "/view");
    $this->assertSession()->pageTextContains($new_body);

    // Verify that the non-default revision vid is greater than the default
    // revision vid.
    $default_revision = $connection->select('node', 'n')
      ->fields('n', ['vid'])
      ->condition('nid', $node->id())
      ->execute()
      ->fetchCol();
    $default_revision_vid = $default_revision[0];
    $this->assertGreaterThan($default_revision_vid, $new_node_revision->getRevisionId());

    // Create an 'EN' node with a revision log message.
    $node = $this->drupalCreateNode();
    $node->title = 'Node title in EN';
    $node->revision_log = 'Simple revision message (EN)';
    $node->save();

    $this->drupalGet("node/" . $node->id() . "/revisions");
    // Verify revisions is accessible since the type has revisions enabled.
    $this->assertSession()->statusCodeEquals(200);
    // Check initial revision is shown on the node revisions overview page.
    $this->assertSession()->pageTextContains('Simple revision message (EN)');

    // Verify that delete operation is inaccessible for the default revision.
    $this->drupalGet("node/" . $node->id() . "/revisions/" . $node->getRevisionId() . "/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Verify that revert operation is inaccessible for the default revision.
    $this->drupalGet("node/" . $node->id() . "/revisions/" . $node->getRevisionId() . "/revert");
    $this->assertSession()->statusCodeEquals(403);

    // Create a new revision and new log message.
    $node = Node::load($node->id());
    $node->body->value = 'New text (EN)';
    $node->revision_log = 'New revision message (EN)';
    $node->setNewRevision();
    $node->save();

    // Check both revisions are shown on the node revisions overview page.
    $this->drupalGet("node/" . $node->id() . "/revisions");
    $this->assertSession()->pageTextContains('Simple revision message (EN)');
    $this->assertSession()->pageTextContains('New revision message (EN)');

    // Create an 'EN' node with a revision log message.
    $node = $this->drupalCreateNode();
    $node->langcode = 'en';
    $node->title = 'Node title in EN';
    $node->revision_log = 'Simple revision message (EN)';
    $node->save();

    $this->drupalGet("node/" . $node->id() . "/revisions");
    // Verify revisions is accessible since the type has revisions enabled.
    $this->assertSession()->statusCodeEquals(200);
    // Check initial revision is shown on the node revisions overview page.
    $this->assertSession()->pageTextContains('Simple revision message (EN)');

    // Add a translation in 'DE' and create a new revision and new log message.
    $translation = $node->addTranslation('de');
    $translation->title->value = 'Node title in DE';
    $translation->body->value = 'New text (DE)';
    $translation->revision_log = 'New revision message (DE)';
    $translation->setNewRevision();
    $translation->save();

    // View the revision UI in 'IT', only the original node revision is shown.
    $this->drupalGet("it/node/" . $node->id() . "/revisions");
    $this->assertSession()->pageTextContains('Simple revision message (EN)');
    $this->assertSession()->pageTextNotContains('New revision message (DE)');

    // View the revision UI in 'DE', only the translated node revision is shown.
    $this->drupalGet("de/node/" . $node->id() . "/revisions");
    $this->assertSession()->pageTextNotContains('Simple revision message (EN)');
    $this->assertSession()->pageTextContains('New revision message (DE)');

    // View the revision UI in 'EN', only the original node revision is shown.
    $this->drupalGet("node/" . $node->id() . "/revisions");
    $this->assertSession()->pageTextContains('Simple revision message (EN)');
    $this->assertSession()->pageTextNotContains('New revision message (DE)');
  }

  /**
   * Checks that revisions are correctly saved without log messages.
   */
  public function testNodeRevisionWithoutLogMessage() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    // Create a node with an initial log message.
    $revision_log = $this->randomMachineName(10);
    $node = $this->drupalCreateNode(['revision_log' => $revision_log]);

    // Save over the same revision and explicitly provide an empty log message
    // (for example, to mimic the case of a node form submitted with no text in
    // the "log message" field), and check that the original log message is
    // preserved.
    $new_title = $this->randomMachineName(10) . 'testNodeRevisionWithoutLogMessage1';

    $node = clone $node;
    $node->title = $new_title;
    $node->revision_log = '';
    $node->setNewRevision(FALSE);

    $node->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains($new_title);
    $node_storage->resetCache([$node->id()]);
    $node_revision = $node_storage->load($node->id());
    $this->assertEquals($revision_log, $node_revision->revision_log->value, 'After an existing node revision is re-saved without a log message, the original log message is preserved.');

    // Create another node with an initial revision log message.
    $node = $this->drupalCreateNode(['revision_log' => $revision_log]);

    // Save a new node revision without providing a log message, and check that
    // this revision has an empty log message.
    $new_title = $this->randomMachineName(10) . 'testNodeRevisionWithoutLogMessage2';

    $node = clone $node;
    $node->title = $new_title;
    $node->setNewRevision();
    $node->revision_log = NULL;

    $node->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains($new_title);
    $node_storage->resetCache([$node->id()]);
    $node_revision = $node_storage->load($node->id());
    $this->assertTrue(empty($node_revision->revision_log->value), 'After a new node revision is saved with an empty log message, the log message for the node is empty.');
  }

  /**
   * Gets server-rendered contextual links for the given contextual links IDs.
   *
   * @param string[] $ids
   *   An array of contextual link IDs.
   * @param string $current_path
   *   The Drupal path for the page for which the contextual links are rendered.
   *
   * @return string
   *   The decoded JSON response body.
   */
  protected function renderContextualLinks(array $ids, $current_path) {
    $post = [];
    for ($i = 0; $i < count($ids); $i++) {
      $post['ids[' . $i . ']'] = $ids[$i];
    }
    $response = $this->drupalPost('contextual/render', 'application/json', $post, ['query' => ['destination' => $current_path]]);

    return Json::decode($response);
  }

  /**
   * Tests the revision translations are correctly reverted.
   */
  public function testRevisionTranslationRevert() {
    // Create a node and a few revisions.
    $node = $this->drupalCreateNode(['langcode' => 'en']);

    $initial_revision_id = $node->getRevisionId();
    $initial_title = $node->label();
    $this->createRevisions($node, 2);

    // Translate the node and create a few translation revisions.
    $translation = $node->addTranslation('it');
    $this->createRevisions($translation, 3);
    $revert_id = $node->getRevisionId();
    $translated_title = $translation->label();
    $untranslatable_string = $node->untranslatable_string_field->value;

    // Create a new revision for the default translation in-between a series of
    // translation revisions.
    $this->createRevisions($node, 1);
    $default_translation_title = $node->label();

    // And create a few more translation revisions.
    $this->createRevisions($translation, 2);
    $translation_revision_id = $translation->getRevisionId();

    // Now revert the a translation revision preceding the last default
    // translation revision, and check that the desired value was reverted but
    // the default translation value was preserved.
    $revert_translation_url = Url::fromRoute('node.revision_revert_translation_confirm', [
      'node' => $node->id(),
      'node_revision' => $revert_id,
      'langcode' => 'it',
    ]);
    $this->drupalGet($revert_translation_url);
    $this->submitForm([], 'Revert');
    /** @var \Drupal\node\NodeStorage $node_storage */
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $node_storage->resetCache();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($node->id());
    $this->assertGreaterThan($translation_revision_id, $node->getRevisionId());
    $this->assertEquals($default_translation_title, $node->label());
    $this->assertEquals($translated_title, $node->getTranslation('it')->label());
    $this->assertNotEquals($untranslatable_string, $node->untranslatable_string_field->value);

    $latest_revision_id = $translation->getRevisionId();

    // Now revert the a translation revision preceding the last default
    // translation revision again, and check that the desired value was reverted
    // but the default translation value was preserved. But in addition the
    // untranslated field will be reverted as well.
    $this->drupalGet($revert_translation_url);
    $this->submitForm(['revert_untranslated_fields' => TRUE], 'Revert');
    $node_storage->resetCache();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($node->id());
    $this->assertGreaterThan($latest_revision_id, $node->getRevisionId());
    $this->assertEquals($default_translation_title, $node->label());
    $this->assertEquals($translated_title, $node->getTranslation('it')->label());
    $this->assertEquals($untranslatable_string, $node->untranslatable_string_field->value);

    $latest_revision_id = $translation->getRevisionId();

    // Now revert the entity revision to the initial one where the translation
    // didn't exist.
    $revert_url = Url::fromRoute('node.revision_revert_confirm', [
      'node' => $node->id(),
      'node_revision' => $initial_revision_id,
    ]);
    $this->drupalGet($revert_url);
    $this->submitForm([], 'Revert');
    $node_storage->resetCache();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($node->id());
    $this->assertGreaterThan($latest_revision_id, $node->getRevisionId());
    $this->assertEquals($initial_title, $node->label());
    $this->assertFalse($node->hasTranslation('it'));
  }

  /**
   * Creates a series of revisions for the specified node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   * @param $count
   *   The number of revisions to be created.
   */
  protected function createRevisions(NodeInterface $node, $count) {
    for ($i = 0; $i < $count; $i++) {
      $node->title = $this->randomString();
      $node->untranslatable_string_field->value = $this->randomString();
      $node->setNewRevision(TRUE);
      $node->save();
    }
  }

}
