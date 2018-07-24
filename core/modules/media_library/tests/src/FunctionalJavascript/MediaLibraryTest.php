<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Contains Media library integration tests.
 *
 * @group media_library
 */
class MediaLibraryTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'media_library_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a few example media items for use in selection.
    $media = [
      'type_one' => [
        'Horse',
        'Bear',
        'Cat',
        'Dog',
      ],
      'type_two' => [
        'Crocodile',
        'Lizard',
        'Snake',
        'Turtle',
      ],
    ];

    $time = time();
    foreach ($media as $type => $names) {
      foreach ($names as $name) {
        $entity = Media::create(['name' => $name, 'bundle' => $type]);
        $source_field = $type === 'type_one' ? 'field_media_test' : 'field_media_test_1';
        $entity->setCreatedTime(++$time);
        $entity->set($source_field, $name);
        $entity->save();
      }
    }

    // Create a user who can use the Media library.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'access media overview',
      'edit own basic_page content',
      'create basic_page content',
      'create media',
      'delete any media',
      'view media',
    ]);
    $this->drupalLogin($user);
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests that the Media library's administration page works as expected.
   */
  public function testAdministrationPage() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Visit the administration page.
    $this->drupalGet('admin/content/media');

    // Verify that the "Add media" link is present.
    $assert_session->linkExists('Add media');

    // Verify that media from two separate types is present.
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Turtle');

    // Test that users can filter by type.
    $page->selectFieldOption('Media type', 'Type One');
    $page->pressButton('Apply Filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Turtle');
    $page->selectFieldOption('Media type', 'Type Two');
    $page->pressButton('Apply Filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Dog');
    $assert_session->pageTextContains('Turtle');

    // Test that selecting elements as a part of bulk operations works.
    $page->selectFieldOption('Media type', '- Any -');
    $page->pressButton('Apply Filters');
    $assert_session->assertWaitOnAjaxRequest();
    // This tests that anchor tags clicked inside the preview are suppressed.
    $this->getSession()->executeScript('jQuery(".js-click-to-select-trigger a")[0].click()');
    $this->submitForm([], 'Apply to selected items');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Cat');
    $this->submitForm([], 'Delete');
    $assert_session->pageTextNotContains('Dog');
    $assert_session->pageTextContains('Cat');

    // Test 'Select all media'.
    $this->getSession()->getPage()->checkField('Select all media');
    $this->getSession()->getPage()->selectFieldOption('Action', 'media_delete_action');
    $this->submitForm([], 'Apply to selected items');
    $this->getSession()->getPage()->pressButton('Delete');

    $assert_session->pageTextNotContains('Cat');
    $assert_session->pageTextNotContains('Turtle');
    $assert_session->pageTextNotContains('Snake');

    // Test empty text.
    $assert_session->pageTextContains('No media available.');

    // Verify that the "Table" link is present, click it and check address.
    $assert_session->linkExists('Table');
    $page->clickLink('Table');
    $assert_session->addressEquals('admin/content/media-table');
    // Verify that the "Add media" link is present.
    $assert_session->linkExists('Add media');
  }

  /**
   * Tests that the Media library's widget works as expected.
   */
  public function testWidget() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    // Verify that both media widget instances are present.
    $assert_session->pageTextContains('Unlimited media');
    $assert_session->pageTextContains('Twin media');

    // Add to the unlimited cardinality field.
    $unlimited_button = $assert_session->elementExists('css', '.media-library-open-button[href*="field_unlimited_media"]');
    $unlimited_button->click();
    $assert_session->assertWaitOnAjaxRequest();
    // Assert that only type_one media items exist, since this field only
    // accepts items of that type.
    $assert_session->pageTextContains('Media library');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    // Ensure that the "Select all" checkbox is not visible.
    $this->assertFalse($assert_session->elementExists('css', '.media-library-select-all')->isVisible());
    // Use an exposed filter.
    $session = $this->getSession();
    $session->getPage()->fillField('Name', 'Dog');
    $session->getPage()->pressButton('Apply Filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    // Clear the exposed filter.
    $session->getPage()->fillField('Name', '');
    $session->getPage()->pressButton('Apply Filters');
    $assert_session->assertWaitOnAjaxRequest();
    // Select the first three media items (should be Dog/Cat/Bear).
    $checkbox_selector = '.media-library-view .js-click-to-select-checkbox input';
    $checkboxes = $page->findAll('css', $checkbox_selector);
    $checkboxes[0]->click();
    $checkboxes[1]->click();
    $checkboxes[2]->click();
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Select media');
    $assert_session->assertWaitOnAjaxRequest();
    // Ensure that the selection completed successfully.
    $assert_session->pageTextNotContains('Media library');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Cat');
    $assert_session->pageTextContains('Bear');
    // Remove "Dog" (happens to be the first remove button on the page).
    $assert_session->elementExists('css', '.media-library-item__remove')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('Dog');
    $assert_session->pageTextContains('Cat');
    $assert_session->pageTextContains('Bear');

    // Open another Media library on the same page.
    $twin_button = $assert_session->elementExists('css', '.media-library-open-button[href*="field_twin_media"]');
    $twin_button->click();
    $assert_session->assertWaitOnAjaxRequest();
    // This field allows both media types.
    $assert_session->pageTextContains('Media library');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Turtle');
    // Attempt to select three items - the cardinality of this field is two so
    // the third selection should be disabled.
    $checkbox_selector = '.media-library-view .js-click-to-select-checkbox input';
    $checkboxes = $page->findAll('css', $checkbox_selector);
    $this->assertFalse($checkboxes[5]->hasAttribute('disabled'));
    $checkboxes[0]->click();
    $checkboxes[7]->click();
    $this->assertTrue($checkboxes[5]->hasAttribute('disabled'));
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Select media');
    $assert_session->assertWaitOnAjaxRequest();
    // Ensure that the selection completed successfully, and we have only two
    // media items of two different types.
    $assert_session->pageTextNotContains('Media library');
    $assert_session->pageTextContains('Horse');
    $assert_session->pageTextContains('Turtle');
    $assert_session->pageTextNotContains('Snake');

    // Finally, save the form.
    $assert_session->elementExists('css', '.js-media-library-widget-toggle-weight')->click();
    $this->submitForm([
      'title[0][value]' => 'My page',
      'field_unlimited_media[selection][0][weight]' => '2',
    ], 'Save');
    $assert_session->pageTextContains('Basic Page My page has been created');
    // We removed this item earlier.
    $assert_session->pageTextNotContains('Dog');
    // This item should not have been selected due to cardinality constraints.
    $assert_session->pageTextNotContains('Snake');
    // "Cat" should come after "Bear", since we changed the weight.
    $assert_session->elementExists('css', '.field--name-field-unlimited-media > .field__items > .field__item:last-child:contains("Cat")');
    // Make sure everything that was selected shows up.
    $assert_session->pageTextContains('Cat');
    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextContains('Horse');
    $assert_session->pageTextContains('Turtle');
  }

  /**
   * Tests that the widget works as expected for anonymous users.
   */
  public function testWidgetAnonymous() {
    $assert_session = $this->assertSession();

    $this->drupalLogout();

    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $role->revokePermission('view media');
    $role->save();

    // Verify that unprivileged users can't access the widget view.
    $this->drupalGet('admin/content/media-widget');
    $assert_session->responseContains('Access denied');

    // Allow the anonymous user to create pages and view media.
    $this->grantPermissions($role, [
      'access content',
      'create basic_page content',
      'view media',
    ]);

    // Ensure the widget works as an anonymous user.
    $this->drupalGet('node/add/basic_page');

    // Add to the unlimited cardinality field.
    $unlimited_button = $assert_session->elementExists('css', '.media-library-open-button[href*="field_unlimited_media"]');
    $unlimited_button->click();
    $assert_session->assertWaitOnAjaxRequest();

    // Select the first media item (should be Dog).
    $checkbox_selector = '.media-library-view .js-click-to-select-checkbox input';
    $checkboxes = $this->getSession()->getPage()->findAll('css', $checkbox_selector);
    $checkboxes[0]->click();
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Select media');
    $assert_session->assertWaitOnAjaxRequest();

    // Ensure that the selection completed successfully.
    $assert_session->pageTextNotContains('Media library');
    $assert_session->pageTextContains('Dog');

    // Save the form.
    $assert_session->elementExists('css', '.js-media-library-widget-toggle-weight')->click();
    $this->submitForm([
      'title[0][value]' => 'My page',
      'field_unlimited_media[selection][0][weight]' => '0',
    ], 'Save');
    $assert_session->pageTextContains('Basic Page My page has been created');
    $assert_session->pageTextContains('Dog');
  }

}
