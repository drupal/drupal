<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeFormSaveChangedTimeTest.
 */

namespace Drupal\node\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests updating the changed time after API and FORM entity save.
 *
 * @group node
 */
class NodeFormSaveChangedTimeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'node',
  );

  /**
   * An user with permissions to create and edit articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authorUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a node type.
    $this->drupalCreateContentType(array(
      'type' => 'article',
      'name' => 'Article',
    ));

    $this->authorUser = $this->drupalCreateUser(['access content', 'create article content', 'edit any article content'], 'author');
    $this->drupalLogin($this->authorUser);

    // Create one node of the above node type .
    $this->drupalCreateNode(array(
      'type' => 'article',
    ));
  }

  /**
   * Test the changed time after API and FORM save without changes.
   */
  public function testChangedTimeAfterSaveWithoutChanges() {
    $node = entity_load('node', 1);
    $changed_timestamp = $node->getChangedTime();

    $node->save();
    $node = entity_load('node', 1, TRUE);
    $this->assertEqual($changed_timestamp, $node->getChangedTime(), "The entity's changed time wasn't updated after API save without changes.");

    // Ensure different save timestamps.
    sleep(1);

    // Save the node on the regular node edit form.
    $this->drupalPostForm('node/1/edit', array(), t('Save'));

    $node = entity_load('node', 1, TRUE);
    $this->assertNotEqual($changed_timestamp, $node->getChangedTime(), "The entity's changed time was updated after form save without changes.");
  }
}
