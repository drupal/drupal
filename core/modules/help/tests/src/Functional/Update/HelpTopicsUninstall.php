<?php

namespace Drupal\Tests\help\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests merging help topics module when the module is enabled.
 *
 * @group Update
 */
class HelpTopicsUninstall extends UpdatePathTestBase {

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
      __DIR__ . '/../../../../tests/fixtures/update/help-topics-3087499.php',
    ];
  }

  /**
   * Tests upgrading help module for help topics.
   *
   * @see \help_update_10200()
   * @see \help_post_update_help_topics_search()
   * @see \help_post_update_help_topics_uninstall()
   */
  public function testHelpTopicsMerge() {
    $module_handler = \Drupal::moduleHandler();
    $this->assertTrue($module_handler->moduleExists('help'));
    $this->assertTrue($module_handler->moduleExists('help_topics'));
    $this->assertTrue($module_handler->moduleExists('search'));

    $this->assertFalse(\Drupal::database()->schema()->tableExists('help_search_items'));

    $dependencies = $this
      ->config('search.page.help_search')
      ->get('dependencies.module');
    $this->assertTrue(in_array('help_topics', $dependencies, TRUE));
    $this->assertFalse(in_array('help', $dependencies, TRUE));

    // Run updates.
    $this->runUpdates();

    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('help_topics'));

    $this->assertTrue(\Drupal::database()->schema()->tableExists('help_search_items'));

    $dependencies = $this
      ->config('search.page.help_search')
      ->get('dependencies.module');
    $this->assertFalse(in_array('help_topics', $dependencies, TRUE));
    $this->assertTrue(in_array('help', $dependencies, TRUE));
  }

}
