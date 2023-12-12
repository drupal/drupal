<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the bundle selection for view & form display modes.
 *
 * @group field_ui
 */
class DisplayModeBundleSelectionTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field_ui',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType([
      'name' => 'Article',
      'type' => 'article',
    ]);
    $this->drupalCreateContentType([
      'name' => 'Page',
      'type' => 'page',
    ]);
    $this->drupalPlaceBlock('local_actions_block');
    $user = $this->drupalCreateUser([
      'administer display modes',
      'administer node display',
      'administer node form display',
    ]);
    // Create a new form mode 'foobar' for content.
    EntityFormMode::create([
      'id' => 'node.foobar',
      'targetEntityType' => 'node',
      'label' => 'Foobar',
    ])->save();

    $this->drupalLogin($user);
  }

  /**
   * Tests the bundle selection.
   *
   * @param string $display_mode
   *   View or Form display mode.
   * @param string $path
   *   Display mode path.
   * @param string $custom_mode
   *   Custom mode to test.
   *
   * @dataProvider providerBundleSelection
   */
  public function testBundleSelection($display_mode, $path, $custom_mode) {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Add new display mode for content.
    $this->drupalGet("/admin/structure/display-modes/$display_mode");
    $this->assertNotEmpty($assert_session->waitForText("Add $display_mode mode"));
    $this->clickLink("Add $display_mode mode for Content");
    $this->assertNotEmpty($assert_session->waitForText("Add new Content $display_mode mode"));
    $page->find('css', '[data-drupal-selector="edit-label"]')->setValue('test');
    $page->find('css', '[data-drupal-selector="edit-bundles-by-entity-article"]')->check();
    $page->find('css', '.ui-dialog-buttonset')->pressButton('Save');

    // Verify that test display mode is selected for article content type.
    $this->drupalGet("/admin/structure/types/manage/article/$path");
    $page->find('css', '[data-drupal-selector="edit-modes"]')->pressButton('Custom display settings');
    $checkbox = $page->find('css', '[data-drupal-selector="edit-display-modes-custom-test"]');
    $this->assertTrue($checkbox->isChecked());

    // Verify that test display mode is not selected for page content type.
    $this->drupalGet("/admin/structure/types/manage/page/$path");
    $page->find('css', '[data-drupal-selector="edit-modes"]')->pressButton('Custom display settings');
    $checkbox = $page->find('css', '[data-drupal-selector="edit-display-modes-custom-test"]');
    $this->assertFalse($checkbox->isChecked());

    // Click Add view/form display mode button.
    $this->drupalGet("/admin/structure/display-modes/$display_mode");
    $this->assertNotEmpty($assert_session->waitForText("Add $display_mode mode"));
    $this->clickLink("Add $display_mode mode");
    $this->assertNotEmpty($assert_session->waitForText("Choose $display_mode mode entity type"));

    // Add new view/form display mode for content.
    $this->clickLink('Content');
    $this->assertNotEmpty($assert_session->waitForText("Add new Content $display_mode mode"));
    $page->find('css', '[data-drupal-selector="edit-label"]')->setValue('test2');
    $page->find('css', '[data-drupal-selector="edit-bundles-by-entity-article"]')->check();
    $page->find('css', '.ui-dialog-buttonset')->pressButton('Save');

    // Verify that test2 display mode is selected for article content type.
    $this->drupalGet("/admin/structure/types/manage/article/$path");
    $page->find('css', '[data-drupal-selector="edit-modes"]')->pressButton('Custom display settings');
    $checkbox = $page->find('css', '[data-drupal-selector="edit-display-modes-custom-test2"]');
    $this->assertTrue($checkbox->isChecked());

    // Verify that test2 display mode is not selected for page content type.
    $this->drupalGet("/admin/structure/types/manage/page/$path");
    $page->find('css', '[data-drupal-selector="edit-modes"]')->pressButton('Custom display settings');
    $checkbox = $page->find('css', '[data-drupal-selector="edit-display-modes-custom-test2"]');
    $this->assertFalse($checkbox->isChecked());

    // Verify that display mode is not selected on article content type.
    $this->drupalGet("/admin/structure/types/manage/article/$path");
    $page->find('css', '[data-drupal-selector="edit-modes"]')->pressButton('Custom display settings');
    $checkbox = $page->find('css', "[data-drupal-selector='edit-display-modes-custom-$custom_mode']");
    $this->assertFalse($checkbox->isChecked());

    // Edit existing display mode and enable it for article content type.
    $this->drupalGet("/admin/structure/display-modes/$display_mode");
    $this->assertNotEmpty($assert_session->waitForText("Add $display_mode mode"));
    $page->find('xpath', '//ul[@class = "dropbutton"]/li[1]/a')->click();
    $this->assertNotEmpty($assert_session->waitForText("This $display_mode mode will still be available for the rest of the Content types if not checked here, but it will not be enabled by default."));
    $page->find('css', '[data-drupal-selector="edit-bundles-by-entity-article"]')->check();
    $page->find('css', '.ui-dialog-buttonset')->pressButton('Save');

    // Verify that display mode is selected on article content type.
    $this->drupalGet("/admin/structure/types/manage/article/$path");
    $page->find('css', '[data-drupal-selector="edit-modes"]')->pressButton('Custom display settings');
    $checkbox = $page->find('css', "[data-drupal-selector='edit-display-modes-custom-$custom_mode']");
    $this->assertTrue($checkbox->isChecked());
  }

  /**
   * Data provider for testBundleSelection().
   */
  public function providerBundleSelection() {
    return [
      'view display' => ['view', 'display', 'full'],
      'form display' => ['form', 'form-display', 'foobar'],
    ];
  }

}
