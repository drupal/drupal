<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Functional\Update;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests moving search from node to search_node.
 */
#[Group('search')]
#[RunTestsInSeparateProcesses]
#[IgnoreDeprecations]
class SearchUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-11.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests system_update_11401.
   *
   * @see system_update_11401()
   */
  public function testSearchHelpInstall(): void {
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('search_node'));

    $this->runUpdates();

    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('search_node'));
    $nodeSearchPage = $this->container->get(EntityTypeManagerInterface::class)->getStorage('search_page')->load('node_search');
    $this->assertSame(['search_node'], $nodeSearchPage->toArray()['dependencies']['module']);
  }

}
