<?php

namespace Drupal\Tests\help\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\block\Entity\Block;
use Drupal\search\Entity\SearchPage;

/**
 * Tests merging help topics module when the module is not installed.
 *
 * @group Update
 */
class HelpTopicsMerge extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests upgrading help module for help topics.
   *
   * @see \help_update_10200()
   * @see \help_post_update_help_topics_search()
   * @see \help_post_update_help_topics_disable()
   */
  public function testHelpTopicsMerge() {
    $moduleHandler = \Drupal::moduleHandler();
    $this->assertTrue($moduleHandler->moduleExists('help'));
    $this->assertFalse($moduleHandler->moduleExists('help_topics'));
    $this->assertTrue($moduleHandler->moduleExists('search'));

    $this->assertFalse(\Drupal::database()->schema()->tableExists('help_search_items'));

    // No configuration present.
    $this->assertNull(SearchPage::load('help_search'));
    $this->assertNull(Block::load('claro_help_search'));

    // Run updates.
    $this->runUpdates();

    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('help_topics'));

    $this->assertTrue(\Drupal::database()->schema()->tableExists('help_search_items'));

    // Search module's configuration is installed.
    $this->assertNotNull(Block::load('claro_help_search'));
    $this->assertNotNull(SearchPage::load('help_search'));
  }

}
