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
  protected static $modules = ['field_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a user who can add media fields.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'administer node fields',
      'administer node form display',
    ]);
    $this->drupalLogin($user);
    $this->drupalCreateContentType(['type' => 'article']);
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
  }

}
