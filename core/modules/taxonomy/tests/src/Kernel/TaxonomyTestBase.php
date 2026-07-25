<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Provides common helper methods for Taxonomy module tests.
 */
abstract class TaxonomyTestBase extends KernelTestBase {

  use TaxonomyTestTrait;
  use EntityReferenceFieldCreationTrait;

  use BlockCreationTrait {
    placeBlock as drupalPlaceBlock;
  }

  use ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }

  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  use NodeCreationTrait {
    createNode as drupalCreateNode;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'taxonomy',
    'block',
    'node',
    'system',
    'user',
    'text',
    'filter',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['node', 'system', 'taxonomy', 'filter']);

    \Drupal::service(ThemeInstallerInterface::class)->install(['stark']);
    $this->drupalPlaceBlock('system_breadcrumb_block', [
      'theme' => 'stark',
      'region' => 'content',
    ]);

    // Create Article node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

}
