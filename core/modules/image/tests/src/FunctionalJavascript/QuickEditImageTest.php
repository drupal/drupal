<?php

namespace Drupal\Tests\image\FunctionalJavascript;

use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the JavaScript functionality of the "image" in-place editor.
 *
 * @group image
 */
class QuickEditImageTest extends JavascriptTestBase {

  use ImageFieldCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'image', 'field_ui', 'contextual', 'quickedit', 'toolbar'];

  /**
   * A user with permissions to edit Articles and use Quick Edit.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $contentAuthorUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create the Article node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Log in as a content author who can use Quick Edit and edit Articles.
    $this->contentAuthorUser = $this->drupalCreateUser([
      'access contextual links',
      'access toolbar',
      'access in-place editing',
      'access content',
      'create article content',
      'edit any article content',
      'delete any article content',
    ]);
    $this->drupalLogin($this->contentAuthorUser);
  }

  /**
   * Tests if an image can be uploaded inline with Quick Edit.
   */
  public function testUpload() {
    // Create a field with a basic filetype restriction.
    $field_name = strtolower($this->randomMachineName());
    $field_settings = [
      'file_extensions' => 'png',
    ];
    $formatter_settings = [
      'image_style' => 'large',
      'image_link' => '',
    ];
    $this->createImageField($field_name, 'article', [], $field_settings, [], $formatter_settings);

    // Find images that match our field settings.
    $valid_images = [];
    foreach ($this->getTestFiles('image') as $image) {
      // This regex is taken from file_validate_extensions().
      $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote($field_settings['file_extensions'])) . ')$/i';
      if (preg_match($regex, $image->filename)) {
        $valid_images[] = $image;
      }
    }

    // Ensure we have at least two valid images.
    $this->assertGreaterThanOrEqual(2, count($valid_images));

    // Create a File entity for the initial image.
    $file = File::create([
      'uri' => $valid_images[0]->uri,
      'uid' => $this->contentAuthorUser->id(),
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $file->save();

    // Use the first valid image to create a new Node.
    $image_factory = $this->container->get('image.factory');
    $image = $image_factory->get($valid_images[0]->uri);
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => t('Test Node'),
      $field_name => [
        'target_id' => $file->id(),
        'alt' => 'Hello world',
        'title' => '',
        'width' => $image->getWidth(),
        'height' => $image->getHeight(),
      ],
    ]);

    // Visit the new Node.
    $this->drupalGet('node/' . $node->id());

    // Assemble common CSS selectors.
    $entity_selector = '[data-quickedit-entity-id="node/' . $node->id() . '"]';
    $field_selector = '[data-quickedit-field-id="node/' . $node->id() . '/' . $field_name . '/' . $node->language()->getId() . '/full"]';
    $original_image_selector = 'img[src*="' . $valid_images[0]->filename . '"][alt="Hello world"]';
    $new_image_selector = 'img[src*="' . $valid_images[1]->filename . '"][alt="New text"]';

    // Assert that the initial image is present.
    $this->assertSession()->elementExists('css', $entity_selector . ' ' . $field_selector . ' ' . $original_image_selector);

    // Wait until Quick Edit loads.
    $condition = "jQuery('" . $entity_selector . " .quickedit').length > 0";
    $this->assertJsCondition($condition, 10000);

    // Initiate Quick Editing.
    $this->click('.contextual-toolbar-tab button');
    $this->click($entity_selector . ' [data-contextual-id] > button');
    $this->click($entity_selector . ' [data-contextual-id] .quickedit > a');
    $this->click($field_selector);

    // Wait for the field info to load and set new alt text.
    $condition = "jQuery('.quickedit-image-field-info').length > 0";
    $this->assertJsCondition($condition, 10000);
    $input = $this->assertSession()->elementExists('css', '.quickedit-image-field-info input[name="alt"]');
    $input->setValue('New text');

    // Check that our Dropzone element exists.
    $this->assertSession()->elementExists('css', $field_selector . ' .quickedit-image-dropzone');

    // Our headless browser can't drag+drop files, but we can mock the event.
    // Append a hidden upload element to the DOM.
    $script = 'jQuery("<input id=\"quickedit-image-test-input\" type=\"file\" />").appendTo("body")';
    $this->getSession()->executeScript($script);

    // Find the element, and set its value to our new image.
    $input = $this->assertSession()->elementExists('css', '#quickedit-image-test-input');
    $filepath = $this->container->get('file_system')->realpath($valid_images[1]->uri);
    $input->attachFile($filepath);

    // Trigger the upload logic with a mock "drop" event.
    $script = 'var e = jQuery.Event("drop");'
      . 'e.originalEvent = {dataTransfer: {files: jQuery("#quickedit-image-test-input").get(0).files}};'
      . 'e.preventDefault = e.stopPropagation = function () {};'
      . 'jQuery(".quickedit-image-dropzone").trigger(e);';
    $this->getSession()->executeScript($script);

    // Wait for the dropzone element to be removed (i.e. loading is done).
    $condition = "jQuery('" . $field_selector . " .quickedit-image-dropzone').length == 0";
    $this->assertJsCondition($condition, 20000);

    // To prevent 403s on save, we re-set our request (cookie) state.
    $this->prepareRequest();

    // Save the change.
    $this->click('.quickedit-button.action-save');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Re-visit the page to make sure the edit worked.
    $this->drupalGet('node/' . $node->id());

    // Check that the new image appears as expected.
    $this->assertSession()->elementNotExists('css', $entity_selector . ' ' . $field_selector . ' ' . $original_image_selector);
    $this->assertSession()->elementExists('css', $entity_selector . ' ' . $field_selector . ' ' . $new_image_selector);
  }

}
