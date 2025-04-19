<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;
use Drupal\Tests\layout_builder\FunctionalJavascript\LayoutBuilderSortTrait;
use Drupal\Tests\system\Traits\OffCanvasTestTrait;
use Drupal\user\UserInterface;

/**
 * Tests that the navigation block UI exists and stores data correctly.
 *
 * @group navigation
 */
class NavigationBlockUiTest extends WebDriverTestBase {

  use BlockCreationTrait;
  use ContextualLinkClickTrait;
  use LayoutBuilderSortTrait;
  use OffCanvasTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'navigation',
    'block_content',
    'layout_builder',
    'layout_test',
    'layout_builder_form_block_test',
    'node',
    'field_ui',
    'shortcut',
    'off_canvas_test',
    'navigation_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * An administrative user to configure the test environment.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('page_title_block', ['id' => 'title']);
    // Create an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'configure navigation layout',
      'access administration pages',
      'access navigation',
      'access shortcuts',
      'access contextual links',
      'administer shortcuts',
      'administer site configuration',
      'access administration pages',
    ]);
  }

  /**
   * Tests navigation block admin page exists and functions correctly.
   */
  public function testNavigationBlockAdminUiPageNestedForm(): void {
    $layout_url = '/admin/config/user-interface/navigation-block';
    $this->drupalLogin($this->adminUser);

    // Edit the layout and add a block that contains a form.
    $this->drupalGet($layout_url);
    $this->getSession()->getPage()->pressButton('Enable edit mode');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->openAddBlockForm('Layout Builder form block test form api form block');
    $this->getSession()->getPage()->checkField('settings[label_display]');

    // Save the new block, and ensure it is displayed on the page.
    $this->getSession()->getPage()->pressButton('Add block');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->assertNoElementAfterWait('css', '#drupal-off-canvas');
    $this->assertSession()->addressEquals($layout_url);
    $this->assertSession()->pageTextContains('Layout Builder form block test form api form block');
    $this->getSession()->getPage()->pressButton('Save');
    $unexpected_save_message = 'You have unsaved changes';
    $expected_save_message = 'Saved navigation blocks';
    $this->assertSession()->statusMessageNotContains($unexpected_save_message);
    $this->assertSession()->statusMessageContains($expected_save_message);

    // Try to save the layout again and confirm it can save because there are no
    // nested form tags.
    $this->drupalGet($layout_url);
    $this->getSession()->getPage()->pressButton('Enable edit mode');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->checkField('toggle_content_preview');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->statusMessageNotContains($unexpected_save_message);
    $this->assertSession()->statusMessageContains($expected_save_message);
  }

  /**
   * Tests navigation block admin page exists and functions correctly.
   */
  public function testNavigationBlockAdminUiPage(): void {
    $layout_url = '/admin/config/user-interface/navigation-block';
    $this->drupalGet($layout_url);
    $this->assertSession()->pageTextContains('Access denied');
    // Add at least one shortcut.
    $shortcut_set = \Drupal::entityTypeManager()
      ->getStorage('shortcut_set')
      ->getDisplayedToUser($this->adminUser);
    $shortcut = \Drupal::entityTypeManager()->getStorage('shortcut')->create([
      'title' => 'Run cron',
      'shortcut_set' => $shortcut_set->id(),
      'link' => [
        'uri' => 'internal:/admin/config/system/cron',
      ],
    ]);
    $shortcut->save();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($layout_url);
    $page = $this->getSession()->getPage();
    $this->getSession()->getPage()->pressButton('Enable edit mode');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add section should not be present
    $this->assertSession()->linkNotExists('Add section');
    // Configure section should not be present.
    $this->assertSession()->linkNotExists('Configure Section 1');
    // Remove section should not be present.
    $this->assertSession()->linkNotExists('Remove Section 1');

    // Remove the shortcut block.
    $this->assertSession()->pageTextContains('Shortcuts');
    $this->clickContextualLink('.layout-builder .block-navigation-shortcuts', 'Remove block');
    $this->assertOffCanvasFormAfterWait('layout_builder_remove_block');
    $this->assertSession()->pageTextContains('Are you sure you want to remove the Shortcuts block?');
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $page->pressButton('Remove');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->assertNoElementAfterWait('css', '#drupal-off-canvas');

    $this->assertSession()->elementNotExists('css', '.layout-builder .block-navigation-shortcuts');

    // Add a new block.
    $this->getSession()->getPage()->uncheckField('toggle_content_preview');
    $this->openAddBlockForm('Navigation Shortcuts');

    $page->fillField('settings[label]', 'New Shortcuts');
    $page->checkField('settings[label_display]');

    // Save the new block, and ensure it is displayed on the page.
    $page->pressButton('Add block');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->assertNoElementAfterWait('css', '#drupal-off-canvas');
    $this->assertSession()->addressEquals($layout_url);
    $this->assertSession()->pageTextContains('Shortcuts');
    $this->assertSession()->pageTextContains('New Shortcuts');

    // Until the layout is saved, the new block is not visible on the node page.
    $front = Url::fromRoute('<front>');
    $this->drupalGet($front);
    $this->assertSession()->pageTextNotContains('New Shortcuts');

    // When returning to the layout page, the new block is not visible.
    $this->drupalGet($layout_url);
    $this->assertSession()->pageTextNotContains('New Shortcuts');

    // When returning to the layout edit mode, the new block is visible.
    $this->getSession()->getPage()->pressButton('Enable edit mode');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('New Shortcuts');

    // Save the layout, and the new block is visible in the front page.
    $page->pressButton('Save');
    $this->drupalGet($front);
    $this->assertSession()->pageTextContains('New Shortcuts');

    // Reconfigure a block and ensure that the layout content is updated.
    $this->drupalGet($layout_url);
    $this->getSession()->getPage()->pressButton('Enable edit mode');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->clickContextualLink('.layout-builder .block-navigation-shortcuts', 'Configure');
    $this->assertOffCanvasFormAfterWait('layout_builder_update_block');

    $page->fillField('settings[label]', 'Newer Shortcuts');
    $page->pressButton('Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->assertNoElementAfterWait('css', '#drupal-off-canvas');

    $this->assertSession()->addressEquals($layout_url);
    $this->assertSession()->pageTextContains('Newer Shortcuts');
    $this->assertSession()->elementTextNotContains('css', 'form', 'New Shortcuts');
  }

  /**
   * Opens the add block form in the off-canvas dialog.
   *
   * @param string $block_title
   *   The block title which will be the link text.
   *
   * @todo move this from into a trait from
   *   \Drupal\Tests\layout_builder\FunctionalJavascript\LayoutBuilderTest
   */
  private function openAddBlockForm($block_title): void {
    $this->assertSession()->linkExists('Add block');
    $this->clickLink('Add block');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('named', ['link', $block_title]));
    $this->clickLink($block_title);
    $this->assertOffCanvasFormAfterWait('layout_builder_add_block');
  }

  /**
   * Waits for the specified form and returns it when available and visible.
   *
   * @param string $expected_form_id
   *   The expected form ID.
   *
   * @todo move this from into a trait from
   *    \Drupal\Tests\layout_builder\FunctionalJavascript\LayoutBuilderTest
   */
  private function assertOffCanvasFormAfterWait(string $expected_form_id): void {
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForOffCanvasArea();
    $off_canvas = $this->assertSession()->elementExists('css', '#drupal-off-canvas');
    $this->assertNotNull($off_canvas);
    $form_id_element = $off_canvas->find('hidden_field_selector', ['hidden_field', 'form_id']);
    // Ensure the form ID has the correct value and that the form is visible.
    $this->assertNotEmpty($form_id_element);
    $this->assertSame($expected_form_id, $form_id_element->getValue());
    $this->assertTrue($form_id_element->getParent()->isVisible());
  }

}
