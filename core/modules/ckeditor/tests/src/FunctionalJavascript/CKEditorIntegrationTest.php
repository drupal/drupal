<?php

namespace Drupal\Tests\ckeditor\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests the integration of CKEditor.
 *
 * @group ckeditor
 */
class CKEditorIntegrationTest extends WebDriverTestBase {

  /**
   * The account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The FilterFormat config entity used for testing.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $filterFormat;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'ckeditor', 'filter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a text format and associate CKEditor.
    $this->filterFormat = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
    ]);
    $this->filterFormat->save();

    Editor::create([
      'format' => 'filtered_html',
      'editor' => 'ckeditor',
    ])->save();

    // Create a node type for testing.
    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    $field_storage = FieldStorageConfig::loadByName('node', 'body');

    // Create a body field instance for the 'page' node type.
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'label' => 'Body',
      'settings' => ['display_summary' => TRUE],
      'required' => TRUE,
    ])->save();

    // Assign widget settings for the 'default' form mode.
    EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('body', ['type' => 'text_textarea_with_summary'])
      ->save();

    $this->account = $this->drupalCreateUser([
      'administer nodes',
      'create page content',
      'use text format filtered_html',
    ]);
    $this->drupalLogin($this->account);
  }

  /**
   * Tests if the fragment link to a textarea works with CKEditor enabled.
   */
  public function testFragmentLink() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();
    $ckeditor_id = '#cke_edit-body-0-value';

    $this->drupalGet('node/add/page');

    $session->getPage();

    // Add a bottom margin to the title field to be sure the body field is not
    // visible.
    $session->executeScript("document.getElementById('edit-title-0-value').style.marginBottom = window.innerHeight*2 +'px';");

    $this->assertSession()->waitForElementVisible('css', $ckeditor_id);
    // Check that the CKEditor-enabled body field is currently not visible in
    // the viewport.
    $web_assert->assertNotVisibleInViewport('css', $ckeditor_id, 'topLeft', 'CKEditor-enabled body field is visible.');

    $before_url = $session->getCurrentUrl();

    // Trigger a hash change with as target the hidden textarea.
    $session->executeScript("location.hash = '#edit-body-0-value';");

    // Check that the CKEditor-enabled body field is visible in the viewport.
    $web_assert->assertVisibleInViewport('css', $ckeditor_id, 'topLeft', 'CKEditor-enabled body field is not visible.');

    // Use JavaScript to go back in the history instead of
    // \Behat\Mink\Session::back() because that function doesn't work after a
    // hash change.
    $session->executeScript("history.back();");

    $after_url = $session->getCurrentUrl();

    // Check that going back in the history worked.
    self::assertEquals($before_url, $after_url, 'History back works.');
  }

  /**
   * Tests if the Image button appears and works as expected.
   */
  public function testDrupalImageDialog() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();

    $this->drupalGet('node/add/page');
    $session->getPage();

    // Asserts the Image button is present in the toolbar.
    $web_assert->elementExists('css', '#cke_edit-body-0-value .cke_button__drupalimage');

    // Asserts the image dialog opens when clicking the Image button.
    $this->click('.cke_button__drupalimage');
    $this->assertNotEmpty($web_assert->waitForElement('css', '.ui-dialog'));

    $web_assert->elementContains('css', '.ui-dialog .ui-dialog-titlebar', 'Insert Image');
  }

  /**
   * Tests if the Drupal Image Caption plugin appears and works as expected.
   */
  public function testDrupalImageCaptionDialog() {
    $web_assert = $this->assertSession();

    // Disable the caption filter.
    $this->filterFormat->setFilterConfig('filter_caption', [
      'status' => FALSE,
    ]);
    $this->filterFormat->save();

    // If the caption filter is disabled, its checkbox should be absent.
    $this->drupalGet('node/add/page');
    $this->click('.cke_button__drupalimage');
    $this->assertNotEmpty($web_assert->waitForElement('css', '.ui-dialog'));
    $web_assert->elementNotExists('css', '.ui-dialog input[name="attributes[hasCaption]"]');

    // Enable the caption filter again.
    $this->filterFormat->setFilterConfig('filter_caption', [
      'status' => TRUE,
    ]);
    $this->filterFormat->save();

    // If the caption filter is enabled,  its checkbox should be present.
    $this->drupalGet('node/add/page');
    $this->click('.cke_button__drupalimage');
    $this->assertNotEmpty($web_assert->waitForElement('css', '.ui-dialog'));
    $web_assert->elementExists('css', '.ui-dialog input[name="attributes[hasCaption]"]');
  }

}
