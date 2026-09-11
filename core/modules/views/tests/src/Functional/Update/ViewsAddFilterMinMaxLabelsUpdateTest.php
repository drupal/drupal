<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests adding default min_label and max_label to exposed numeric-type filters.
 *
 * @see views_post_update_filter_min_max_labels()
 */
#[Group('Update')]
#[CoversFunction('views_post_update_filter_min_max_labels')]
#[RunTestsInSeparateProcesses]
class ViewsAddFilterMinMaxLabelsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-11.3.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/filter-min-max-labels.php',
    ];
  }

  /**
   * Tests that min_label and max_label are added to exposed numeric filters.
   */
  public function testViewsPostUpdateFilterMinMaxLabels(): void {
    $view = View::load('test_filter_min_max_labels');
    $data = $view->toArray();
    $filters = $data['display']['default']['display_options']['filters'];

    // Filter without labels should not have them yet.
    $this->assertArrayNotHasKey('min_label', $filters['nid']['expose']);
    $this->assertArrayNotHasKey('max_label', $filters['nid']['expose']);

    // Filter with existing labels should already have custom values.
    $this->assertSame('Custom min', $filters['nid_with_labels']['expose']['min_label']);
    $this->assertSame('Custom max', $filters['nid_with_labels']['expose']['max_label']);

    $this->runUpdates();

    $view = View::load('test_filter_min_max_labels');
    $data = $view->toArray();
    $filters = $data['display']['default']['display_options']['filters'];

    // Filter without labels should now have the legacy defaults.
    $this->assertSame('Min', $filters['nid']['expose']['min_label']);
    $this->assertSame('Max', $filters['nid']['expose']['max_label']);

    // Filter with existing labels should be unchanged.
    $this->assertSame('Custom min', $filters['nid_with_labels']['expose']['min_label']);
    $this->assertSame('Custom max', $filters['nid_with_labels']['expose']['max_label']);
  }

}
