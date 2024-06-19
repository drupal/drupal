<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests the upgrade path for making content types' help and description NULL.
 *
 * @group node
 */
class NullHelpAndDescriptionTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/remove-description-from-article-content-type.php',
    ];
  }

  /**
   * Tests the upgrade path for updating empty help and description to NULL.
   */
  public function testRunUpdates(): void {
    $node_type = NodeType::load('article');
    $this->assertInstanceOf(NodeType::class, $node_type);

    $this->assertSame('', $node_type->get('help'));
    $this->assertSame("\n", $node_type->get('description'));
    $this->runUpdates();

    $node_type = NodeType::load('article');
    $this->assertInstanceOf(NodeType::class, $node_type);

    $this->assertNull($node_type->get('help'));
    $this->assertNull($node_type->get('description'));
    $this->assertSame('', $node_type->getHelp());
    $this->assertSame('', $node_type->getDescription());
  }

}
