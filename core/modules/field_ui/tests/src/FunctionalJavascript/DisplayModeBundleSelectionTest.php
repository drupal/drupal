<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the bundle selection for view & form display modes.
 */
#[Group('field_ui')]
#[RunTestsInSeparateProcesses]
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
   */
  #[DataProvider('providerBundleSelection')]
  public function testBundleSelection($display_mode, $path, $custom_mode): void {
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
    if ($display_mode === 'view') {
      $assert_session->elementExists('css', '#enabled-display-modes-wrapper #display-mode-node-article-test');
    }
    else {
      $details = $page->find('css', 'details[data-drupal-selector="edit-modes"]');
      if ($details && !$details->hasAttribute('open')) {
        $summary = $details->find('css', 'summary');
        if ($summary) {
          $summary->click();
        }
      }
      $checkbox = $page->find('css', '[data-drupal-selector="edit-display-modes-custom-test"]');
      $this->assertTrue($checkbox->isChecked());
    }

    // Verify that test display mode is not selected for page content type.
    $this->drupalGet("/admin/structure/types/manage/page/$path");
    if ($display_mode === 'view') {
      $enabled = $page->find('css', '#enabled-display-modes-wrapper #display-mode-node-page-test');
      $this->assertNull($enabled, 'The "test" display mode should not be enabled for the page content type.');
    }
    else {
      $details = $page->find('css', 'details[data-drupal-selector="edit-modes"]');
      if ($details && !$details->hasAttribute('open')) {
        $summary = $details->find('css', 'summary');
        if ($summary) {
          $summary->click();
        }
      }
      $checkbox = $page->find('css', '[data-drupal-selector="edit-display-modes-custom-test"]');
      $this->assertFalse($checkbox->isChecked());
    }

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
    if ($display_mode === 'view') {
      $assert_session->waitForElement('css', '#display-mode-node-article-test2', 10000);
      $enabled_element = $page->find('css', '#enabled-display-modes-wrapper #display-mode-node-article-test2');
      $this->assertNotNull($enabled_element, 'The "test2" display mode should be in the enabled table.');
    }
    else {
      $details = $page->find('css', 'details[data-drupal-selector="edit-modes"]');
      if ($details && !$details->hasAttribute('open')) {
        $summary = $details->find('css', 'summary');
        if ($summary) {
          $summary->click();
        }
      }
      $checkbox = $page->find('css', '[data-drupal-selector="edit-display-modes-custom-test2"]');
      $this->assertTrue($checkbox->isChecked());
    }

    // Verify that test2 display mode is not selected for page content type.
    if ($display_mode === 'view') {
      $this->drupalGet("/admin/structure/types/manage/page/$path");
      $enabled = $page->find('css', '#enabled-display-modes-wrapper #display-mode-node-page-test2');
      $this->assertNull($enabled, 'The "test2" display mode should not be enabled for the page content type.');
    }
    else {
      $this->drupalGet("/admin/structure/types/manage/page/$path/default");
      $details = $page->find('css', 'details[data-drupal-selector="edit-modes"]');
      if ($details && !$details->hasAttribute('open')) {
        $summary = $details->find('css', 'summary');
        if ($summary) {
          $summary->click();
        }
      }
      $checkbox = $page->find('css', '[data-drupal-selector="edit-display-modes-custom-test2"]');
      $this->assertFalse($checkbox->isChecked());
    }

    // Verify that display mode is not selected on article content type.
    if ($display_mode === 'view') {
      $this->drupalGet("/admin/structure/types/manage/article/$path");
      $enabled = $page->find('css', "#enabled-display-modes-wrapper #display-mode-node-article-$custom_mode");
      $this->assertNull($enabled, "The \"$custom_mode\" display mode should not be enabled for the article content type.");
    }
    else {
      $this->drupalGet("/admin/structure/types/manage/article/$path/default");
      $details = $page->find('css', 'details[data-drupal-selector="edit-modes"]');
      if ($details && !$details->hasAttribute('open')) {
        $summary = $details->find('css', 'summary');
        if ($summary) {
          $summary->click();
        }
      }
      $checkbox = $page->find('css', "[data-drupal-selector='edit-display-modes-custom-$custom_mode']");
      $this->assertFalse($checkbox->isChecked());
    }

    // Edit existing display mode and enable it for article content type.
    $this->drupalGet("/admin/structure/display-modes/$display_mode");
    $this->assertNotEmpty($assert_session->waitForText("Add $display_mode mode"));
    $page->find('xpath', '//ul[@class = "dropbutton"]/li[1]/a')->click();
    $this->assertNotEmpty($assert_session->waitForText("This $display_mode mode will still be available for the rest of the Content types if not checked here, but it will not be enabled by default."));
    $page->find('css', '[data-drupal-selector="edit-bundles-by-entity-article"]')->check();
    $page->find('css', '.ui-dialog-buttonset')->pressButton('Save');

    // Verify that display mode is selected on article content type.
    if ($display_mode === 'view') {
      $this->drupalGet("/admin/structure/types/manage/article/$path");
      $enabled = $page->find('css', "#enabled-display-modes-wrapper #display-mode-node-article-$custom_mode");
      $this->assertNotNull($enabled, "The \"$custom_mode\" display mode should be enabled for the article content type.");
    }
    else {
      $this->drupalGet("/admin/structure/types/manage/article/$path/default");
      $details = $page->find('css', 'details[data-drupal-selector="edit-modes"]');
      if ($details && !$details->hasAttribute('open')) {
        $summary = $details->find('css', 'summary');
        if ($summary) {
          $summary->click();
        }
      }
      $checkbox = $page->find('css', "[data-drupal-selector='edit-display-modes-custom-$custom_mode']");
      $this->assertTrue($checkbox->isChecked());
    }
  }

  /**
   * Data provider for testBundleSelection().
   */
  public static function providerBundleSelection() {
    return [
      'view display' => ['view', 'display', 'full'],
      'form display' => ['form', 'form-display', 'foobar'],
    ];
  }

}
