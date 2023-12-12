<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Theme;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\media_library\FunctionalJavascript\MediaLibraryTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests that buttons in modals are not in their button pane.
 *
 * @group claro
 */
class ClaroModalDisplayTest extends MediaLibraryTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * Tests the position f "add another" button in dialogs.
   */
  public function testModalAddAnother() {

    // Add unlimited field to the media type four.
    $unlimited_field_storage = FieldStorageConfig::create([
      'entity_type' => 'media',
      'field_name' => 'unlimited',
      'type' => 'string',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $unlimited_field_storage->save();
    $unlimited_field = FieldConfig::create([
      'field_storage' => $unlimited_field_storage,
      'bundle' => 'type_four',
      'label' => 'Unlimited',
    ]);
    $unlimited_field->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('media', 'type_four', 'media_library')
      ->setComponent('unlimited', [
        'type' => 'string_textfield',
      ])
      ->save();

    $assert_session = $this->assertSession();

    foreach ($this->getTestFiles('image') as $image) {
      $extension = pathinfo($image->filename, PATHINFO_EXTENSION);
      if ($extension === 'jpg') {
        $jpg_image = $image;
      }
    }
    if (!isset($jpg_image)) {
      $this->fail('Expected test files not present.');
    }

    // Create a user that can create media for all media types.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'create basic_page content',
      'create media',
      'view media',
    ]);
    $this->drupalLogin($user);

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    // Add to the twin media field.
    $this->openMediaLibraryForField('field_twin_media');
    $this->switchToMediaType('Four');

    // A file needs to be added for the unlimited field to appear in the form.
    $this->addMediaFileToField('Add files', $this->container->get('file_system')->realpath($jpg_image->uri));

    // Wait for the file upload to be completed.
    // Copied from \Drupal\Tests\media_library\FunctionalJavascript\MediaLibraryTestBase::assertMediaAdded.
    $selector = '.js-media-library-add-form-added-media';
    $this->assertJsCondition('jQuery("' . $selector . '").is(":focus")');

    // Assert that the 'add another item' button is not in the dialog footer.
    $assert_session->elementNotExists('css', '.ui-dialog-buttonset .field-add-more-submit');
    $assert_session->elementExists('css', '.ui-dialog-content .field-add-more-submit');
  }

}
