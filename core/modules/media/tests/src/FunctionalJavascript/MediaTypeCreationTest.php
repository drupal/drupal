<?php

namespace Drupal\Tests\media\FunctionalJavascript;

/**
 * Tests the media type creation.
 *
 * @group media
 */
class MediaTypeCreationTest extends MediaJavascriptTestBase {

  /**
   * Tests the media type creation form.
   */
  public function testMediaTypeCreationFormWithDefaultField() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $label = 'Type with Default Field';
    $mediaTypeMachineName = str_replace(' ', '_', strtolower($label));

    $this->drupalGet('admin/structure/media/add');

    // Fill in a label to the media type.
    $page->fillField('label', $label);
    // Wait for machine name generation. Default: waitUntilVisible(), does not
    // work properly.
    $session->wait(5000, "jQuery('.machine-name-value').text() === '{$mediaTypeMachineName}'");

    // Select the media source used by our media type.
    $assert_session->fieldExists('Media source');
    $assert_session->optionExists('Media source', 'test');
    $page->selectFieldOption('Media source', 'test');
    $result = $assert_session->waitForElementVisible('css', 'fieldset[data-drupal-selector="edit-source-configuration"]');
    $this->assertNotEmpty($result);

    $page->pressButton('Save');

    // Check whether the source field was correctly created.
    $this->drupalGet("admin/structure/media/manage/{$mediaTypeMachineName}/fields");

    // Check 2nd column of first data row, to be machine name for field name.
    $assert_session->elementContains('xpath', '(//table[@id="field-overview"]//tr)[2]//td[2]', 'field_media_test');
    // Check 3rd column of first data row, to be correct field type.
    $assert_session->elementTextContains('xpath', '(//table[@id="field-overview"]//tr)[2]//td[3]', 'Text (plain)');

    // Check that the source field is correctly assigned to media type.
    $this->drupalGet("admin/structure/media/manage/{$mediaTypeMachineName}");

    $assert_session->pageTextContains('Test source field is used to store the essential information about the media item.');
  }

  /**
   * Test creation of media type, reusing an existing source field.
   */
  public function testMediaTypeCreationReuseSourceField() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Create a new media type, which should create a new field we can reuse.
    $this->drupalGet('/admin/structure/media/add');
    $page->fillField('label', 'Pastafazoul');
    $session->wait(5000, "jQuery('.machine-name-value').text() === 'pastafazoul'");
    $page->selectFieldOption('Media source', 'test');
    $result = $assert_session->waitForElementVisible('css', 'fieldset[data-drupal-selector="edit-source-configuration"]');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');

    $label = 'Type reusing Default Field';
    $mediaTypeMachineName = str_replace(' ', '_', strtolower($label));

    $this->drupalGet('admin/structure/media/add');

    // Fill in a label to the media type.
    $page->fillField('label', $label);

    // Wait for machine name generation. Default: waitUntilVisible(), does not
    // work properly.
    $session->wait(5000, "jQuery('.machine-name-value').text() === '{$mediaTypeMachineName}'");

    // Select the media source used by our media type.
    $assert_session->fieldExists('Media source');
    $assert_session->optionExists('Media source', 'test');
    $page->selectFieldOption('Media source', 'test');
    $result = $assert_session->waitForElementVisible('css', 'fieldset[data-drupal-selector="edit-source-configuration"]');
    $this->assertNotEmpty($result);
    // Select the existing field for re-use.
    $page->selectFieldOption('source_configuration[source_field]', 'field_media_test');
    $page->pressButton('Save');

    // Check that no new fields were created.
    $this->drupalGet("admin/structure/media/manage/{$mediaTypeMachineName}/fields");
    // The reused field should be present...
    $assert_session->pageTextContains('field_media_test');
    // ...not a new, unique one.
    $assert_session->pageTextNotContains('field_media_test_1');
  }

}
