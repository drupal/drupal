<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Views;

use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the taxonomy term with argument from a node page.
 */
#[Group('taxonomy')]
#[RunTestsInSeparateProcesses]
class TaxonomyTermArgumentTidFromNodeTest extends TaxonomyTestBase {

  use BlockCreationTrait {
    placeBlock as drupalPlaceBlock;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'node',
    'taxonomy',
    'taxonomy_test_views',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_argument_tid_from_node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    // Remove the term field from the display, replace with the view.
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getViewDisplay('node', 'article')
      ->removeComponent('field_views_testing_tags')
      ->save();

    \Drupal::service(ThemeInstallerInterface::class)->install(['stark']);
    $this->config('system.theme')->set('default', 'stark')->save();

    $this->installSchema('node', ['node_access']);

    $this->drupalPlaceBlock('views_block:test_argument_tid_from_node-block_1');

    $this->setUpCurrentUser(permissions: ['access content']);
  }

  /**
   * Tests cache invalidation.
   */
  public function testCacheInvalidation(): void {
    $this->drupalGet($this->nodes[0]->toUrl());
    $this->assertSession()->pageTextContains($this->term1->label());

    $this->nodes[0]->set('field_views_testing_tags', [])->save();

    $this->drupalGet($this->nodes[0]->toUrl());
    $this->assertSession()->pageTextNotContains($this->term1->label());
  }

}
