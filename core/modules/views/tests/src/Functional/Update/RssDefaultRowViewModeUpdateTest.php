<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the update view mode when removing system.rss.
 *
 * @see system_post_update_delete_rss_config()
 */
#[Group('update')]
#[RunTestsInSeparateProcesses]
class RssDefaultRowViewModeUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/rss-default-view-mode.php',
    ];
  }

  /**
   * Tests the upgrade path setting rss row view mode.
   */
  public function testRssDefaultRowViewModeUpdate(): void {
    $views = View::loadMultiple();
    $data = $views['test_display_feed']->toArray();

    $this->assertEquals('default', $data['display']['feed_1']['display_options']['row']['options']['view_mode']);

    $this->runUpdates();

    $views = View::loadMultiple();
    $data = $views['test_display_feed']->toArray();

    $this->assertEquals('title', $data['display']['feed_1']['display_options']['row']['options']['view_mode']);

  }

}
