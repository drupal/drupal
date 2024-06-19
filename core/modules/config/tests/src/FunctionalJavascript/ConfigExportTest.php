<?php

declare(strict_types=1);

namespace Drupal\Tests\config\FunctionalJavascript;

use Drupal\block_content\Entity\BlockContent;
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
  protected static $modules = ['config', 'system', 'block', 'block_content'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @var string
   *  A prefix string used in naming the test blocks.
   */
  protected string $blockNamePrefix = 'aaaaaa_config_export_test_block';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'administer blocks',
      'access block library',
      'administer block types',
      'administer block content',
    ]));

    // Create test blocks, so we know what the titles will be to check their order.
    foreach ([1, 2, 3, 4] as $num) {
      $block_name = $this->blockNamePrefix . $num;
      $new_block = $this->createBlockContent($block_name);
      $this->drupalPlaceBlock('block_content:' . $new_block->uuid(), [
        'id' => $block_name,
        'label' => $this->randomMachineName(),
        'theme' => $this->defaultTheme,
        'region' => 'sidebar_first',
      ]);
    }
  }

  /**
   * Creates test blocks.
   *
   * @param $title
   *   Title of the block.
   *
   * @return \Drupal\block_content\Entity\BlockContent
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createBlockContent($title) {
    $block_content = BlockContent::create([
      'info' => $title,
      'type' => 'basic',
    ]);
    $block_content->save();
    return $block_content;
  }

  /**
   * Tests Ajax form functionality on the config export page.
   */
  public function testAjaxOnExportPage(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'export configuration',
      'administer blocks',
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

    // Check that the 'Configuration name' list is sorted alphabetically by ID,
    // which always begins with our prefix, and not the label, which is randomized.
    $page->selectFieldOption('config_type', 'Block');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $options = $page->findField('config_name')->findAll('css', 'option');
    foreach ([1, 2, 3, 4] as $num) {
      $block_name = $this->blockNamePrefix . $num;
      $this->assertStringStartsWith($block_name, $options[$num]->getText());
      $this->assertEquals($block_name, $options[$num]->getValue());
    }
  }

}
