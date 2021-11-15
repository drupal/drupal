<?php

namespace Drupal\Tests\quickedit\FunctionalJavascript;

use Drupal\file\Entity\File;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * @coversDefaultClass \Drupal\image\Plugin\InPlaceEditor\Image
 * @group quickedit
 */
class QuickEditImageTest extends QuickEditJavascriptTestBase {

  use ImageFieldCreationTrait;
  use TestFileCreationTrait;
  use QuickEditImageEditorTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'image', 'field_ui', 'hold_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permissions to edit Articles and use Quick Edit.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $contentAuthorUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create the Article node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Tests that quick editor works correctly with images.
   *
   * @covers ::isCompatible
   * @covers ::getAttachments
   *
   * @dataProvider providerTestImageInPlaceEditor
   */
  public function testImageInPlaceEditor($admin_permission = FALSE) {
    // Log in as a content author who can use Quick Edit and edit Articles.
    $permissions = [
      'access contextual links',
      'access toolbar',
      'access in-place editing',
      'access content',
      'create article content',
      'edit any article content',
      'delete any article content',
    ];
    if ($admin_permission) {
      $permissions[] = 'administer nodes';
    }
    $this->contentAuthorUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->contentAuthorUser);
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
    ]);
    $file->setPermanent();
    $file->save();

    // Use the first valid image to create a new Node.
    $image_factory = $this->container->get('image.factory');
    $image = $image_factory->get($valid_images[0]->uri);
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test Node',
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

    // Initial state.
    $this->awaitQuickEditForEntity('node', 1);
    $this->assertEntityInstanceStates([
      'node/1[0]' => 'closed',
    ]);

    $admin_inactive = [];
    $admin_candidate = [];
    if ($admin_permission) {
      $admin_inactive = [
        'node/1/uid/en/full' => 'inactive',
        'node/1/created/en/full' => 'inactive',
      ];
      $admin_candidate = [
        'node/1/uid/en/full' => 'candidate',
        'node/1/created/en/full' => 'candidate',
      ];
    }

    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full'               => 'inactive',
      'node/1/body/en/full'                => 'inactive',
      'node/1/' . $field_name . '/en/full' => 'inactive',
    ] + $admin_inactive);

    // Start in-place editing of the article node.
    $this->startQuickEditViaToolbar('node', 1, 0);
    $this->assertEntityInstanceStates([
      'node/1[0]' => 'opened',
    ]);
    $this->assertQuickEditEntityToolbar((string) $node->label(), NULL);
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full'               => 'candidate',
      'node/1/body/en/full'                => 'candidate',
      'node/1/' . $field_name . '/en/full' => 'candidate',
    ] + $admin_candidate);

    // Click the image field.
    $this->click($field_selector);
    $this->awaitImageEditor();
    $this->assertSession()->elementExists('css', $field_selector . ' .quickedit-image-dropzone');
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full'               => 'candidate',
      'node/1/body/en/full'                => 'candidate',
      'node/1/' . $field_name . '/en/full' => 'active',
    ] + $admin_candidate);

    // Type new 'alt' text.
    $this->typeInImageEditorAltTextInput('New text');
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full'               => 'candidate',
      'node/1/body/en/full'                => 'candidate',
      'node/1/' . $field_name . '/en/full' => 'changed',
    ] + $admin_candidate);

    // Drag and drop an image.
    $this->dropImageOnImageEditor($valid_images[1]->uri);

    // To prevent 403s on save, we re-set our request (cookie) state.
    $this->prepareRequest();

    // Click 'Save'.
    hold_test_response(TRUE);
    $this->saveQuickEdit();
    $this->assertEntityInstanceStates([
      'node/1[0]' => 'committing',
    ]);
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full'               => 'candidate',
      'node/1/body/en/full'                => 'candidate',
      'node/1/' . $field_name . '/en/full' => 'saving',
    ] + $admin_candidate);
    $this->assertEntityInstanceFieldMarkup([
      'node/1/' . $field_name . '/en/full' => '.quickedit-changed',
    ]);
    hold_test_response(FALSE);

    // Wait for the saving of the image field to complete.
    $this->assertJsCondition("Drupal.quickedit.collections.entities.get('node/1[0]').get('state') === 'closed'");
    $this->assertEntityInstanceStates([
      'node/1[0]' => 'closed',
    ]);

    // Re-visit the page to make sure the edit worked.
    $this->drupalGet('node/' . $node->id());

    // Check that the new image appears as expected.
    $this->assertSession()->elementNotExists('css', $entity_selector . ' ' . $field_selector . ' ' . $original_image_selector);
    $this->assertSession()->elementExists('css', $entity_selector . ' ' . $field_selector . ' ' . $new_image_selector);
  }

  /**
   * Data provider for ::testImageInPlaceEditor().
   *
   * @return array
   *   Test cases.
   */
  public function providerTestImageInPlaceEditor(): array {
    return [
      'with permission' => [TRUE],
      'without permission' => [FALSE],
    ];
  }

}
