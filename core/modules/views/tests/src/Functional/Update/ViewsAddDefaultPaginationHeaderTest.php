<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the addition of the pagination_heading_level setting.
 *
 * @see views_post_update_pager_heading()
 *
 * @group Update
 * @group legacy
 */
class ViewsAddDefaultPaginationHeaderTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'taxonomy', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/add_pagination_heading.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installModulesFromClassProperty($this->container);
  }

  /**
   * Tests the upgrade path adding pagination_heading_level.
   */
  public function testViewsPostUpdatePaginationHeadingLevel(): void {
    $view = View::load('add_pagination_heading');
    $data = $view->toArray();
    $counter = 0;
    foreach ($data['display'] as $display) {
      if (array_key_exists('pager', $display['display_options'])) {
        $pager = $display['display_options']['pager'];
        // The pager has type and options so we need to ensure we are reviewing options.
        if (is_array($pager) && array_key_exists('options', $pager)) {
          $this->assertArrayNotHasKey('pagination_heading_level', $pager['options']);
          $counter++;
        }
      }
    }

    $this->assertGreaterThan(0, $counter);
    $this->runUpdates();

    $view = View::load('add_pagination_heading');
    $data = $view->toArray();

    $allow_pager_type_update = [
      'mini',
      'full',
    ];

    $counterHasPager = 0;
    $counterNoPager = 0;
    foreach ($data['display'] as $display) {
      if (array_key_exists('pager', $display['display_options'])) {
        $pager = $display['display_options']['pager'];
        // The pager has type and options so we need to ensure we are reviewing options.
        if (is_array($pager) && array_key_exists('options', $pager)) {
          // We need different assertions for the different types of pagers.
          // We ensure we get the whole pager handler.
          if (in_array($pager['type'], $allow_pager_type_update)) {
            $this->assertArrayHasKey('pagination_heading_level', $pager['options']);
            $counterHasPager++;
          }
          else {
            $this->assertArrayNotHasKey('pagination_heading_level', $pager['options']);
            $counterNoPager++;
          }
        }
      }
    }

    $this->assertGreaterThan(0, $counterHasPager);
    $this->assertGreaterThan(0, $counterNoPager);
  }

}
