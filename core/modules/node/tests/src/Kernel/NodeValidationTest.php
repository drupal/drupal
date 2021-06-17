<?php

namespace Drupal\Tests\node\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests node validation constraints.
 *
 * @group node
 */
class NodeValidationTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node'];

  /**
   * Set the default field storage backend for fields created during tests.
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a node type for testing.
    $type = NodeType::create(['type' => 'page', 'name' => 'page']);
    $type->save();
  }

  /**
   * Tests the node validation constraints.
   */
  public function testValidation() {
    $this->createUser();
    $node = Node::create(['type' => 'page', 'title' => 'test', 'uid' => 1]);
    $violations = $node->validate();
    $this->assertCount(0, $violations, 'No violations when validating a default node.');

    $node->set('title', $this->randomString(256));
    $violations = $node->validate();
    $this->assertCount(1, $violations, 'Violation found when title is too long.');
    $this->assertEquals('title.0.value', $violations[0]->getPropertyPath());
    $this->assertEquals('<em class="placeholder">Title</em>: may not be longer than 255 characters.', $violations[0]->getMessage());

    $node->set('title', NULL);
    $violations = $node->validate();
    $this->assertCount(1, $violations, 'Violation found when title is not set.');
    $this->assertEquals('title', $violations[0]->getPropertyPath());
    $this->assertEquals('This value should not be null.', $violations[0]->getMessage());

    $node->set('title', '');
    $violations = $node->validate();
    $this->assertCount(1, $violations, 'Violation found when title is set to an empty string.');
    $this->assertEquals('title', $violations[0]->getPropertyPath());

    // Make the title valid again.
    $node->set('title', $this->randomString());
    // Save the node so that it gets an ID and a changed date.
    $node->save();
    // Set the changed date to something in the far past.
    $node->set('changed', 433918800);
    $violations = $node->validate();
    $this->assertCount(1, $violations, 'Violation found when changed date is before the last changed date.');
    $this->assertEquals('', $violations[0]->getPropertyPath());
    $this->assertEquals('The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.', $violations[0]->getMessage());
  }

}
