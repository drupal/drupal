<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

/**
 * Tests field UI integration for media library widget.
 *
 * @group media_library
 */
class FieldUiIntegrationTest extends MediaLibraryTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field_ui', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');

    // Create a user who can add media fields.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'administer node fields',
      'administer node form display',
    ]);
    $this->drupalLogin($user);
    $this->drupalCreateContentType(['type' => 'article']);
    $this->drupalCreateContentType(['type' => 'page']);
  }

  /**
   * Tests field UI integration for media library widget.
   */
  public function testFieldUiIntegration() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $user = $this->drupalCreateUser([
      'access administration pages',
      'administer node fields',
      'administer node form display',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/structure/types/manage/article/fields/add-field');
    $page->selectFieldOption('new_storage_type', 'field_ui:entity_reference:media');
    $this->assertNotNull($assert_session->waitForField('label'));
    $page->fillField('label', 'Shatner');
    $this->waitForText('field_shatner');
    $page->pressButton('Save and continue');
    $page->pressButton('Save field settings');
    $assert_session->pageTextNotContains('Undefined index: target_bundles');
    $this->waitForFieldExists('Type One')->check();
    $this->assertElementExistsAfterWait('css', '[name="settings[handler_settings][target_bundles][type_one]"][checked="checked"]');
    $page->checkField('settings[handler_settings][target_bundles][type_two]');
    $this->assertElementExistsAfterWait('css', '[name="settings[handler_settings][target_bundles][type_two]"][checked="checked"]');
    $page->checkField('settings[handler_settings][target_bundles][type_three]');
    $this->assertElementExistsAfterWait('css', '[name="settings[handler_settings][target_bundles][type_three]"][checked="checked"]');
    $page->pressButton('Save settings');
    $assert_session->pageTextContains('Saved Shatner configuration.');

    // Create a new instance of an existing field storage and assert that it
    // automatically uses the media library.
    $this->drupalGet('/admin/structure/types/manage/page/fields/reuse');
    $this->assertSession()->elementExists('css', "input[value=Re-use][name=field_shatner]");
    $this->click("input[value=Re-use][name=field_shatner]");
    $this->waitForFieldExists('Type One')->check();
    $this->assertElementExistsAfterWait('css', '[name="settings[handler_settings][target_bundles][type_one]"][checked="checked"]');
    $page->pressButton('Save settings');
    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $assert_session->fieldValueEquals('fields[field_shatner][type]', 'media_library_widget');
  }

}
