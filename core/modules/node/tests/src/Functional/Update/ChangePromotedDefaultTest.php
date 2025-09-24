<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional\Update;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests creating base field overrides for the promote field on node types.
 */
#[Group('Update')]
#[RunTestsInSeparateProcesses]
class ChangePromotedDefaultTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests run updates.
   *
   * @legacy-covers node_post_update_create_promote_base_field_overrides
   */
  public function testRunUpdates(): void {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
    $entityFieldManager = \Drupal::service(EntityFieldManagerInterface::class);
    $promoteFieldDefinition = $entityFieldManager->getBaseFieldDefinitions('node')['promote'];
    $false_result = [0 => ['value' => 0]];
    $true_result = [0 => ['value' => 1]];

    $article_promote_1 = $promoteFieldDefinition->getConfig('article');
    $this->assertTrue($article_promote_1->isNew());
    $page_promote_1 = $promoteFieldDefinition->getConfig('page');
    $this->assertEquals($false_result, $page_promote_1->getDefaultValueLiteral());

    $this->runUpdates();

    $article_promote_2 = $promoteFieldDefinition->getConfig('article');
    $this->assertEquals($true_result, $article_promote_2->getDefaultValueLiteral());
    $page_promote_2 = $promoteFieldDefinition->getConfig('page');
    $this->assertEquals($false_result, $page_promote_2->getDefaultValueLiteral());
  }

}
