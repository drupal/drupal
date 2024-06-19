<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the fields dialogs.
 *
 * @group views_ui
 */
class FieldDialogsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'views',
    'views_ui',
    'views_test_config',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var string[]
   */
  public static $testViews = ['test_content_ajax'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    ViewTestData::createTestViews(self::class, ['views_test_config']);

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer views',
      'access content overview',
    ]);

    // Disable automatic live preview to make the sequence of calls clearer.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.always_live_preview', FALSE)->save();
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests removing a field through the rearrange dialog.
   */
  public function testRemoveFieldHandler(): void {
    $this->drupalGet('admin/structure/views/view/test_content_ajax');
    $page = $this->getSession()->getPage();

    $this->openFieldDialog();
    $remove_link = $page->findAll('css', '.views-remove-link')[1];
    $parent = $remove_link->getParent();
    $this->assertTrue($remove_link->isVisible());
    $remove_checkbox = $this->assertSession()->fieldExists('fields[title][removed]', $parent);
    $this->assertFalse($remove_checkbox->isVisible());
    $this->assertFalse($remove_checkbox->isChecked());
    $remove_link->click();
    $this->assertFalse($remove_link->isVisible());
    $this->assertTrue($remove_checkbox->isChecked());
  }

  /**
   * Uses the 'And/Or Rearrange' link for fields to open a dialog.
   */
  protected function openFieldDialog() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $dropbutton = $page->find('css', '.views-ui-display-tab-bucket.field .dropbutton-toggle button');
    $dropbutton->click();
    $add_link = $page->findById('views-rearrange-field');
    $this->assertTrue($add_link->isVisible(), 'And/Or Rearrange button found.');
    $add_link->click();
    $assert_session->assertWaitOnAjaxRequest();
  }

}
