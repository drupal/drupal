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
    $label = 'Type with Default Field';
    $mediaTypeMachineName = str_replace(' ', '_', strtolower($label));

    $this->drupalGet('admin/structure/media/add');
    $page = $this->getSession()->getPage();

    // Fill in a label to the media type.
    $page->fillField('label', $label);
    // Wait for machine name generation. Default: waitUntilVisible(), does not
    // work properly.
    $this->getSession()
      ->wait(5000, "jQuery('.machine-name-value').text() === '{$mediaTypeMachineName}'");

    // Select the media source used by our media type.
    $this->assertSession()->fieldExists('Media source');
    $this->assertSession()->optionExists('Media source', 'test');
    $page->selectFieldOption('Media source', 'test');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->pressButton('Save');

    // Check whether the source field was correctly created.
    $this->drupalGet("admin/structure/media/manage/{$mediaTypeMachineName}/fields");

    // Check 2nd column of first data row, to be machine name for field name.
    $this->assertSession()
      ->elementContains('xpath', '(//table[@id="field-overview"]//tr)[2]//td[2]', 'field_media_test');
    // Check 3rd column of first data row, to be correct field type.
    $this->assertSession()
      ->elementTextContains('xpath', '(//table[@id="field-overview"]//tr)[2]//td[3]', 'Text (plain)');

    // Check that the source field is correctly assigned to media type.
    $this->drupalGet("admin/structure/media/manage/{$mediaTypeMachineName}");

    $this->assertSession()->pageTextContains('Test source field is used to store the essential information about the media item.');
  }

  /**
   * Test creation of media type, reusing an existing source field.
   */
  public function testMediaTypeCreationReuseSourceField() {
    // Create a new media type, which should create a new field we can reuse.
    $this->drupalGet('/admin/structure/media/add');
    $page = $this->getSession()->getPage();
    $page->fillField('label', 'Pastafazoul');
    $this->getSession()
      ->wait(5000, "jQuery('.machine-name-value').text() === 'pastafazoul'");
    $page->selectFieldOption('Media source', 'test');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->pressButton('Save');

    $label = 'Type reusing Default Field';
    $mediaTypeMachineName = str_replace(' ', '_', strtolower($label));

    $this->drupalGet('admin/structure/media/add');
    $page = $this->getSession()->getPage();

    // Fill in a label to the media type.
    $page->fillField('label', $label);

    // Wait for machine name generation. Default: waitUntilVisible(), does not
    // work properly.
    $this->getSession()
      ->wait(5000, "jQuery('.machine-name-value').text() === '{$mediaTypeMachineName}'");

    // Select the media source used by our media type.
    $this->assertSession()->fieldExists('Media source');
    $this->assertSession()->optionExists('Media source', 'test');
    $page->selectFieldOption('Media source', 'test');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Select the existing field for re-use.
    $page->selectFieldOption('source_configuration[source_field]', 'field_media_test');
    $page->pressButton('Save');

    // Check that no new fields were created.
    $this->drupalGet("admin/structure/media/manage/{$mediaTypeMachineName}/fields");
    // The reused field should be present...
    $this->assertSession()->pageTextContains('field_media_test');
    // ...not a new, unique one.
    $this->assertSession()->pageTextNotContains('field_media_test_1');
  }

}
