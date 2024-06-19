<?php

declare(strict_types=1);

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
  protected static $modules = [
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permissions to create and edit articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authorUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $this->authorUser = $this->drupalCreateUser([
      'access content',
      'create article content',
      'edit any article content',
    ], 'author');
    $this->drupalLogin($this->authorUser);

    // Create one node of the above node type .
    $this->drupalCreateNode([
      'type' => 'article',
    ]);
  }

  /**
   * Tests the changed time after API and FORM save without changes.
   */
  public function testChangedTimeAfterSaveWithoutChanges(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $storage->resetCache([1]);
    $node = $storage->load(1);
    $changed_timestamp = $node->getChangedTime();
    $node->save();
    $storage->resetCache([1]);
    $node = $storage->load(1);
    $this->assertEquals($changed_timestamp, $node->getChangedTime(), "The entity's changed time wasn't updated after API save without changes.");

    // Ensure different save timestamps.
    sleep(1);

    // Save the node on the regular node edit form.
    $this->drupalGet('node/1/edit');
    $this->submitForm([], 'Save');

    $storage->resetCache([1]);
    $node = $storage->load(1);
    $this->assertNotEquals($node->getChangedTime(), $changed_timestamp, "The entity's changed time was updated after form save without changes.");
  }

}
