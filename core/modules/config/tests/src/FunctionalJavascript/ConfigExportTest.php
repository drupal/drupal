<?php

declare(strict_types=1);

namespace Drupal\Tests\config\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the config export form.
 *
 * @group config
 */
class ConfigExportTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config', 'system'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests Ajax form functionality on the config export page.
   */
  public function testAjaxOnExportPage() {
    $this->drupalLogin($this->drupalCreateUser([
      'export configuration',
    ]));

    $page = $this->getSession()->getPage();

    // Check that the export is empty on load.
    $this->drupalGet('admin/config/development/configuration/single/export');
    $this->assertTrue($this->assertSession()->optionExists('edit-config-name', '- Select -')->isSelected());
    $this->assertSession()->fieldValueEquals('export', '');

    // Check that the export is filled when selecting a config name.
    $page->selectFieldOption('config_name', 'system.site');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldValueNotEquals('export', '');

    // Check that the export is empty when selecting "- Select -" option in
    // the config name.
    $page->selectFieldOption('config_name', '- Select -');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldValueEquals('export', '');

    // Check that the export is emptied again when selecting a config type.
    $page->selectFieldOption('config_type', 'Action');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldValueEquals('export', '');
  }

}
