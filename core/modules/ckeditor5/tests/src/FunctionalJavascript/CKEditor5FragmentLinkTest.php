<?php

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Tests that the fragment link points to CKEditor 5.
 *
 * @group ckeditor5
 * @internal
 */
class CKEditor5FragmentLinkTest extends WebDriverTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'ckeditor5'];

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a text format and associate CKEditor 5.
    FilterFormat::create([
      'format' => 'ckeditor5',
      'name' => 'CKEditor 5 with image upload',
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ])->save();
    Editor::create([
      'format' => 'ckeditor5',
      'editor' => 'ckeditor5',
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
    ]);
    $this->drupalLogin($this->account);
  }

  /**
   * Tests if the fragment link to a textarea works with CKEditor 5 enabled.
   */
  public function testFragmentLink() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();
    $ckeditor_class = '.ck-editor';
    $ckeditor_id = '#cke_edit-body-0-value';

    $this->drupalGet('node/add/page');

    $session->getPage();

    // Add a bottom margin to the title field to be sure the body field is not
    // visible.
    $session->executeScript("document.getElementById('edit-title-0-value').style.marginBottom = window.innerHeight*2 +'px';");

    $this->assertSession()->waitForElementVisible('css', $ckeditor_id);
    // Check that the CKEditor5-enabled body field is currently not visible in
    // the viewport.
    $web_assert->assertNotVisibleInViewport('css', $ckeditor_class, 'topLeft', 'CKEditor5-enabled body field is visible.');

    $before_url = $session->getCurrentUrl();

    // Trigger a hash change with as target the hidden textarea.
    $session->executeScript("location.hash = '#edit-body-0-value';");

    // Check that the CKEditor5-enabled body field is visible in the viewport.
    // The hash change adds an ID to the CKEditor 5 instance so check its visibility using the ID now.
    $web_assert->assertVisibleInViewport('css', $ckeditor_id, 'topLeft', 'CKEditor5-enabled body field is not visible.');

    // Use JavaScript to go back in the history instead of
    // \Behat\Mink\Session::back() because that function doesn't work after a
    // hash change.
    $session->executeScript("history.back();");

    $after_url = $session->getCurrentUrl();

    // Check that going back in the history worked.
    self::assertEquals($before_url, $after_url, 'History back works.');
  }

}
