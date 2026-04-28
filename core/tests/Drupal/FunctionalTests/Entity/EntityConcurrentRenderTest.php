<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that the same entity can be rendered multiple times on a page.
 */
#[Group('Entity')]
#[RunTestsInSeparateProcesses]
class EntityConcurrentRenderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'entity_test',
    'field',
    'filter',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Add a formatted text field. The text format processing creates filter
    // placeholders during rendering, which causes the block's Fiber to
    // suspend and allows other block Fibers to interleave.
    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'body',
      'type' => 'text_long',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'body',
      'label' => 'Body',
    ])->save();
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('entity_test', 'entity_test')
      ->setComponent('body')
      ->save();

    $this->drupalLogin($this->drupalCreateUser(['view test entity']));
  }

  /**
   * Tests that two blocks rendering the same entity both produce output.
   */
  public function testSameEntityInMultipleBlocks(): void {
    $entity = EntityTest::create([
      'name' => 'Unique entity content',
      'body' => ['value' => 'Body text', 'format' => 'plain_text'],
    ]);
    $entity->save();

    $this->drupalPlaceBlock('entity_test_block', [
      'id' => 'first',
      'label' => 'First',
      'entity_id' => $entity->id(),
    ]);
    $this->drupalPlaceBlock('entity_test_block', [
      'id' => 'second',
      'label' => 'Second',
      'entity_id' => $entity->id(),
    ]);

    $this->drupalGet('<front>');

    // Both blocks should render the entity content.
    $first = $this->assertSession()->elementExists('css', '#block-first');
    $second = $this->assertSession()->elementExists('css', '#block-second');
    $this->assertStringContainsString('Unique entity content', $first->getText());
    $this->assertStringContainsString('Unique entity content', $second->getText());
  }

}
