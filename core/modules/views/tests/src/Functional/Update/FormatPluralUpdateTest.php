<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the upgrade path for converting format_plural from integer to boolean.
 *
 * @see views_post_update_format_plural()
 */
#[Group('Update')]
#[RunTestsInSeparateProcesses]
class FormatPluralUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/format-plural.php',
    ];
  }

  /**
   * Tests that fields with the format_plural option are updated properly.
   */
  public function testViewsFieldFormatPluralConversion(): void {
    $view = View::load('test_format_plural_update');
    $data = $view->toArray();
    $this->assertSame(0, $data['display']['default']['display_options']['fields']['uid']['format_plural']);

    $this->runUpdates();

    $view = View::load('test_format_plural_update');
    $data = $view->toArray();
    // Ensure that integer 0 has become a boolean false.
    $this->assertFalse($data['display']['default']['display_options']['fields']['uid']['format_plural']);
  }

}
