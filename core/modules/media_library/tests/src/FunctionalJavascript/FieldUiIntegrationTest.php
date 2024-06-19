<?php

declare(strict_types=1);

namespace Drupal\Tests\media_library\FunctionalJavascript;

// cspell:ignore shatner

/**
 * Tests field UI integration for media library widget.
 *
 * @group media_library
 */
class FieldUiIntegrationTest extends MediaLibraryTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

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
    $this->createMediaItems([
      'type_one' => [
        'Horse',
        'Bear',
        'Cat',
        'Dog',
      ],
    ]);
  }

  /**
   * Tests field UI integration for media library widget.
   */
  public function testFieldUiIntegration(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $user = $this->drupalCreateUser([
      'access administration pages',
      'administer node fields',
      'administer node form display',
      'view media',
      'bypass node access',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('/admin/structure/types/manage/article/fields/add-field');
    $page->find('css', "[name='new_storage_type'][value='field_ui:entity_reference:media']")->getParent()->click();
    $page->findButton('Continue')->click();
    $this->assertNotNull($assert_session->waitForField('label'));
    $page->fillField('label', 'Shatner');
    $this->waitForText('field_shatner');
    $page->pressButton('Continue');
    $this->assertMatchesRegularExpression('/.*article\/add-field\/node\/field_shatner.*/', $this->getUrl());
    $assert_session->pageTextNotContains('Undefined index: target_bundles');
    $this->waitForFieldExists('Type One')->check();
    $this->assertElementExistsAfterWait('css', '[name="settings[handler_settings][target_bundles][type_one]"][checked="checked"]');
    $page->checkField('settings[handler_settings][target_bundles][type_two]');
    $this->assertElementExistsAfterWait('css', '[name="settings[handler_settings][target_bundles][type_two]"][checked="checked"]');
    $page->checkField('settings[handler_settings][target_bundles][type_three]');
    $this->assertElementExistsAfterWait('css', '[name="settings[handler_settings][target_bundles][type_three]"][checked="checked"]');
    $page->pressButton('Save settings');
    $assert_session->pageTextContains('Saved Shatner configuration.');

    $this->drupalGet('/admin/structure/types/manage/article/fields/node.article.field_shatner');
    $assert_session->checkboxNotChecked('set_default_value');
    $page->checkField('set_default_value');
    $this->assertElementExistsAfterWait('css', "#field_shatner-media-library-wrapper-default_value_input")
      ->pressButton('Add media');
    $this->waitForText('Add or select media');
    $this->selectMediaItem(0);
    $this->pressInsertSelected('Added one media item.');

    $page->pressButton('Save settings');
    $assert_session->pageTextContains('Saved Shatner configuration.');

    $this->drupalGet('/admin/structure/types/manage/article/fields/node.article.field_shatner');
    $assert_session->checkboxChecked('set_default_value');

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
