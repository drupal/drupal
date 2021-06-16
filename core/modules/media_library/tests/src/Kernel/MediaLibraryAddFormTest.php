<?php

namespace Drupal\Tests\media_library\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media_library\Form\FileUploadForm;
use Drupal\media_library\Form\OEmbedForm;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_library_form_overwrite_test\Form\TestAddForm;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the media library add form.
 *
 * @group media_library
 */
class MediaLibraryAddFormTest extends KernelTestBase {

  use MediaTypeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
    'media_library',
    'file',
    'field',
    'image',
    'system',
    'views',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('media');
    $this->installConfig([
      'field',
      'system',
      'file',
      'image',
      'media',
      'media_library',
    ]);

    // Create an account with special UID 1.
    $this->createUser([]);

    $this->createMediaType('image', ['id' => 'image']);
    $this->createMediaType('oembed:video', ['id' => 'remote_video']);
  }

  /**
   * Tests the media library add form.
   */
  public function testMediaTypeAddForm() {
    $entity_type_manager = \Drupal::entityTypeManager();
    $image = $entity_type_manager->getStorage('media_type')->load('image');
    $remote_video = $entity_type_manager->getStorage('media_type')->load('remote_video');
    $image_source_definition = $image->getSource()->getPluginDefinition();
    $remote_video_source_definition = $remote_video->getSource()->getPluginDefinition();

    // Assert the form class is added to the media source.
    $this->assertSame(FileUploadForm::class, $image_source_definition['forms']['media_library_add']);
    $this->assertSame(OEmbedForm::class, $remote_video_source_definition['forms']['media_library_add']);

    // Assert the media library UI does not contains the add form when the user
    // does not have access.
    $this->assertEmpty($this->buildLibraryUi('image')['content']['form']);
    $this->assertEmpty($this->buildLibraryUi('remote_video')['content']['form']);

    // Create a user that has access to create the image media type but not the
    // remote video media type.
    $this->setCurrentUser($this->createUser([
      'create image media',
    ]));
    // Assert the media library UI only contains the add form for the image
    // media type.
    $this->assertSame('managed_file', $this->buildLibraryUi('image')['content']['form']['container']['upload']['#type']);
    $this->assertEmpty($this->buildLibraryUi('remote_video')['content']['form']);

    // Create a user that has access to create both media types.
    $this->setCurrentUser($this->createUser([
      'create image media',
      'create remote_video media',
    ]));
    // Assert the media library UI only contains the add form for both media
    // types.
    $this->assertSame('managed_file', $this->buildLibraryUi('image')['content']['form']['container']['upload']['#type']);
    $this->assertSame('url', $this->buildLibraryUi('remote_video')['content']['form']['container']['url']['#type']);
  }

  /**
   * Build the media library UI for a selected type.
   *
   * @param string $selected_type_id
   *   The selected media type ID.
   *
   * @return array
   *   The render array for the media library.
   */
  protected function buildLibraryUi($selected_type_id) {
    $state = MediaLibraryState::create('test', ['image', 'remote_video'], $selected_type_id, -1);
    return \Drupal::service('media_library.ui_builder')->buildUi($state);
  }

  /**
   * Tests the validation of the library state in the media library add form.
   */
  public function testFormStateValidation() {
    $form_state = new FormState();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The media library state is not present in the form state.');
    \Drupal::formBuilder()->buildForm(FileUploadForm::class, $form_state);
  }

  /**
   * Tests the validation of the selected type in the media library add form.
   */
  public function testSelectedTypeValidation() {
    $state = MediaLibraryState::create('test', ['image', 'remote_video', 'header_image'], 'header_image', -1);
    $form_state = new FormState();
    $form_state->set('media_library_state', $state);
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The 'header_image' media type does not exist.");
    \Drupal::formBuilder()->buildForm(FileUploadForm::class, $form_state);
  }

  /**
   * Tests overwriting of the add form.
   */
  public function testDifferentAddForm() {
    $this->enableModules(['media_library_form_overwrite_test']);

    $entity_type_manager = \Drupal::entityTypeManager();
    $image = $entity_type_manager->getStorage('media_type')->load('image');

    $image_source_definition = $image->getSource()->getPluginDefinition();

    // Assert the overwritten form class is set to the media source.
    $this->assertSame(TestAddForm::class, $image_source_definition['forms']['media_library_add']);
  }

}
