<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the output of node links (read more, add new comment, etc).
 */
#[Group('node')]
#[RunTestsInSeparateProcesses]
class NodeLinksTest extends KernelTestBase {

  use UserCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'datetime',
    'filter',
    'text',
    'node',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['filter']);
    $this->installConfig(['node']);

    $this->setUpCurrentUser(permissions: [
      'access content',
    ]);

    DateFormat::create([
      'id' => 'fallback',
      'label' => 'Fallback',
      'pattern' => 'Y-m-d',
    ])->save();

    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type->save();
  }

  /**
   * Tests that the links can be hidden in the view display settings.
   */
  public function testHideLinks(): void {
    $node = $this->createNode([
      'type' => 'article',
      'promote' => NodeInterface::PROMOTED,
    ]);

    // Full view doesn't have read more link.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->linkNotExists('Read more');

    // Teaser has read more link.
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $teaser_build = $view_builder->view($node, 'teaser');
    $teaser_output = \Drupal::service('renderer')->renderInIsolation($teaser_build);
    $this->assertStringContainsString('Read more', (string) $teaser_output);

    // Hide links.
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'article', 'teaser')
      ->removeComponent('links')
      ->save();

    // Test teaser view after hiding links.
    $teaser_build = $view_builder->view($node, 'teaser');
    $teaser_output = \Drupal::service('renderer')->renderInIsolation($teaser_build);
    $this->assertStringNotContainsString('Read more', (string) $teaser_output);
  }

}
