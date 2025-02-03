<?php

declare(strict_types=1);

namespace Drupal\Tests\block\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JS functionality in the block add form.
 *
 * @group block
 */
class BlockAddTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the AJAX for the theme selector.
   */
  public function testBlockAddThemeSelector(): void {
    \Drupal::service('theme_installer')->install(['claro']);

    $this->drupalLogin($this->drupalCreateUser([
      'administer blocks',
    ]));

    $this->drupalGet('admin/structure/block/add/system_powered_by_block');
    $assert_session = $this->assertSession();
    // Pick a theme with a region that does not exist in another theme.
    $assert_session->selectExists('Theme')->selectOption('claro');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->selectExists('Region')->selectOption('pre_content');
    // Switch to a theme that doesn't contain the region selected above.
    $assert_session->selectExists('Theme')->selectOption('stark');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('The submitted value Pre-content in the Region element is not allowed.');
    $assert_session->optionExists('Region', '- Select -');
    // Check that the summary line is not present in the title.
    $summary_text = $this->getSession()->getPage()->find('css', 'li.vertical-tabs__menu-item:nth-child(1) > a:nth-child(1) > span:nth-child(2)')->getText();
    $assert_session->elementTextContains('css', '.vertical-tabs__menu-item-title', 'Response status');
    $assert_session->elementTextNotContains('css', '.vertical-tabs__menu-item-title', $summary_text);

    // Search for the "Pages" tab link and click it
    $this->getSession()->getPage()->find('css', 'a[href="#edit-visibility-request-path"]')->click();
    // Check that the corresponding form section is open and visible.
    $form_section = $this->getSession()->getPage()->find('css', '#edit-visibility-request-path');
    $this->assertNotEmpty($form_section, 'The "Pages" form section exists.');
    $this->assertTrue($form_section->isVisible(), 'The "Pages" form section is visible after clicking the tab.');
  }

}
