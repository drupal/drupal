<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\media\Entity\Media;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * For testing the drupalEntityLinkSuggestions plugin.
 */
#[Group('ckeditor5')]
#[RunTestsInSeparateProcesses]
class EntityLinkSuggestionsTest extends CKEditor5TestBase {

  use CKEditor5TestTrait;
  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'media',
    'ckeditor5',
    'ckeditor5_test',
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

    // Create text format, associate CKEditor 5, validate.
    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p> <br> <a href data-entity-type data-entity-uuid data-entity-metadata>',
          ],
        ],
        'entity_links' => [
          'status' => TRUE,
        ],
      ],
    ])->save();
    Editor::create([
      'format' => 'test_format',
      'editor' => 'ckeditor5',
      'image_upload' => [
        'status' => FALSE,
      ],
      'settings' => [
        'toolbar' => [
          'items' => [
            'link',
          ],
        ],
      ],
    ])->save();
    $this->assertExpectedCkeditor5Violations();

    // Create an account with "f" in the username.
    $account = $this->drupalCreateUser([
      'create page content',
      'edit any page content',
      'use text format test_format',
    ]);

    // Create a document media item with "f" in the name.
    $this->createMediaType('file', ['id' => 'document', 'label' => 'Document']);
    File::create([
      'uri' => $this->getTestFiles('text')[0]->uri,
    ])->save();
    Media::create([
      'bundle' => 'document',
      'name' => 'Information about screaming hairy armadillo',
      'field_media_file' => [
        [
          'target_id' => 1,
        ],
      ],
    ])->save();

    $this->drupalLogin($account);
  }

  /**
   * Test the entity link suggestions.
   */
  public function testStandardLink(): void {
    $session = $this->getSession();
    $assert_session = $this->assertSession();
    $page = $session->getPage();

    // Create a test entity.
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->drupalCreateNode(['type' => 'page', 'title' => 'Foo']);

    $this->drupalGet('node/add/page');
    $this->waitForEditor();
    $this->pressEditorButton('Link');

    // Find the href field.
    $balloon = $this->assertVisibleBalloon('.ck-link-form');
    $this->assertNotNull($autocomplete_field = $balloon->find('css', '.ck-input-text[inputmode=url]'));

    // Make sure all fields are empty.
    $this->assertEmpty($autocomplete_field->getValue(), 'Autocomplete field is empty.');

    // Make sure the autocomplete result container is hidden.
    $autocomplete_container = $assert_session->elementExists('css', '.ck-link-form .entity-link-suggestions-ui-autocomplete');
    $this->assertFalse($autocomplete_container->isVisible());

    // Trigger a keydown event to activate an autocomplete search.
    $autocomplete_field->setValue('f');
    $this->assertTrue($this->getSession()->wait(5000, "document.querySelectorAll('.entity-link-suggestions-result-line.ui-menu-item').length > 0"));

    // Make sure the autocomplete result container is visible.
    $this->assertTrue($autocomplete_container->isVisible());

    // Find all the autocomplete results.
    $results = $page->findAll('css', '.entity-link-suggestions-result-line.ui-menu-item');
    $this->assertCount(2, $results);
    $this->assertSame('Foo', $results[0]->find('css', '.entity-link-suggestions-result-line--title')->getText());
    $this->assertSame('Information about screaming hairy armadillo', $results[1]->find('css', '.entity-link-suggestions-result-line--title')->getText());

    // Make the search term longer to narrow down the results.
    $autocomplete_field->setValue('fo');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->waitForElementRemoved('xpath', '//span[@class="entity-link-suggestions-result-line--title" and text()="Foo"]');

    // Find all the autocomplete results.
    $results = $page->findAll('css', '.entity-link-suggestions-result-line.ui-menu-item');
    $this->assertCount(2, $results);
    $this->assertSame('Foo', $results[0]->find('css', '.entity-link-suggestions-result-line--title')->getText());
    $this->assertSame('Information about screaming hairy armadillo', $results[1]->find('css', '.entity-link-suggestions-result-line--title')->getText());

    // Find the first result and click it.
    $results[0]->click();

    // Make sure the link field is populated with the test entity's URL.
    $expected_url = base_path() . 'node/1';
    $this->assertSame($expected_url, $autocomplete_field->getValue());
    $balloon->pressButton('Insert');
    $this->assertBalloonClosed();

    // Make sure all attributes are populated.
    $entity_link_suggestions_link = $assert_session->waitForElementVisible('css', '.ck-content a');
    $this->assertNotNull($entity_link_suggestions_link);
    $this->assertSame($expected_url, $entity_link_suggestions_link->getAttribute('href'));
    $this->assertSame('node', $entity_link_suggestions_link->getAttribute('data-entity-type'));
    $this->assertSame($entity->uuid(), $entity_link_suggestions_link->getAttribute('data-entity-uuid'));

    // Let's change our mind: we want to use the second result instead.
    $this->selectTextInsideElement('a');
    $this->pressEditorButton('Link');
    $balloon = $this->assertVisibleBalloon('.ck-link-form');
    $autocomplete_field = $balloon->find('css', '.ck-input-text[inputmode=url]');
    $autocomplete_field->setValue('fo');
    $assert_session->waitForElementVisible('css', '.ck-link-form .entity-link-suggestions-ui-autocomplete');
    $results = $page->findAll('css', '.entity-link-suggestions-result-line.ui-menu-item');
    $results[1]->click();
    $expected_url = base_path() . 'media/1/edit';
    $this->assertSame($expected_url, $autocomplete_field->getValue());
    $balloon->pressButton('Update');
    $this->assertBalloonClosed();

    // Again make sure all attributes are populated.
    $entity_link_suggestions_link = $assert_session->waitForElementVisible('css', '.ck-content a');
    $this->assertNotNull($entity_link_suggestions_link);
    $this->assertSame($expected_url, $entity_link_suggestions_link->getAttribute('href'));
    $this->assertSame('media', $entity_link_suggestions_link->getAttribute('data-entity-type'));
    $this->assertSame(Media::load(1)->uuid(), $entity_link_suggestions_link->getAttribute('data-entity-uuid'));

    // Open the edit link dialog by moving selection to the link, verifying the
    // "Link" button is off before and on after, and then pressing that button.
    $this->selectTextInsideElement('a');
    $this->assertTrue($this->getEditorButton('Link')->hasClass('ck-on'));
    $this->pressEditorButton('Link');
    $link_edit_balloon = $this->assertVisibleBalloon('.ck-link-form');
    $autocomplete_field = $link_edit_balloon->find('css', '.ck-input-text[inputmode=url]');
    $this->assertSame($expected_url, $autocomplete_field->getValue());
    // Click to trigger the reset of the the autocomplete status.
    $autocomplete_field->click();
    // Enter a URL and verify that no link suggestions are found.
    $autocomplete_field->setValue('http://example.com');
    $autocomplete_field->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->waitForElementVisible('css', '.entity-link-suggestions-result-line.ui-menu-item');
    $results = $page->findAll('css', '.entity-link-suggestions-result-line.ui-menu-item');
    $this->assertCount(1, $results);
    $this->assertSame('http://example.com', $results[0]->find('css', '.entity-link-suggestions-result-line--title')->getText());
    $this->assertSame('No content suggestions found. This URL will be used as is.', $results[0]->find('css', '.entity-link-suggestions-result-line--description')->getText());
    // Accept the first autocomplete suggestion.
    $results[0]->click();
    $assert_session->waitForElementRemoved('css', '.entity-link-suggestions-result-line--title');
    $assert_session->waitForElementVisible('css', '.ck-link-form .ck-button-save');
    $link_edit_balloon->pressButton('Update');
    $this->getSession()->wait(5000, '!document.querySelector(".ck .ui-autocomplete") || document.querySelector(".ck .ui-autocomplete").style.display === "none"');
    $autocomplete_still_present = $this->getSession()->evaluateScript('!!document.querySelector(".ck .ui-autocomplete")');
    if ($autocomplete_still_present) {
      $link_edit_balloon->pressButton('Update');
    }
    $this->assertTrue($assert_session->waitForElementRemoved('css', '.ck-button-save'));
    // Assert balloon is still visible, but now it's again the link actions one.
    $this->assertVisibleBalloon('.ck-link-toolbar');
    // Assert balloon can be closed by clicking elsewhere in the editor.
    $page->find('css', '.ck-editor__editable')->click();
    $this->assertBalloonClosed();

    $changed_link = $assert_session->waitForElementVisible('css', '.ck-content [href="http://example.com"]');
    $this->assertNotNull($changed_link);
    foreach (['data-entity-type', 'data-entity-uuid'] as $attribute_name) {
      $this->assertFalse($changed_link->hasAttribute($attribute_name), "Link should no longer have $attribute_name");
    }
  }

}
