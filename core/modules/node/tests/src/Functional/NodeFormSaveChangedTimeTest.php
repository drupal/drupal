<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests updating the changed time after API and FORM entity save.
 *
 * @group node
 */
class NodeFormSaveChangedTimeTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
  ];

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
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $this->authorUser = $this->drupalCreateUser(['access content', 'create article content', 'edit any article content'], 'author');
    $this->drupalLogin($this->authorUser);

    // Create one node of the above node type .
    $this->drupalCreateNode([
      'type' => 'article',
    ]);
  }

  /**
   * Test the changed time after API and FORM save without changes.
   */
  public function testChangedTimeAfterSaveWithoutChanges() {
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $storage->resetCache([1]);
    $node = $storage->load(1);
    $changed_timestamp = $node->getChangedTime();
    $node->save();
    $storage->resetCache([1]);
    $node = $storage->load(1);
    $this->assertEqual($changed_timestamp, $node->getChangedTime(), "The entity's changed time wasn't updated after API save without changes.");

    // Ensure different save timestamps.
    sleep(1);

    // Save the node on the regular node edit form.
    $this->drupalPostForm('node/1/edit', [], t('Save'));

    $storage->resetCache([1]);
    $node = $storage->load(1);
    $this->assertNotEqual($changed_timestamp, $node->getChangedTime(), "The entity's changed time was updated after form save without changes.");
  }

}
