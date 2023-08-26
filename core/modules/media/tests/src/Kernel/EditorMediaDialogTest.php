<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\editor\EditorInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\Form\EditorMediaDialog;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * @coversDefaultClass \Drupal\media\Form\EditorMediaDialog
 * @group media
 * @group legacy
 */
class EditorMediaDialogTest extends KernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'file',
    'filter',
    'image',
    'media',
    'media_test_source',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('user');
  }

  /**
   * Tests that the form builds successfully.
   *
   * @covers ::buildForm
   */
  public function testBuildForm() {
    $format = FilterFormat::create([
      'filters' => [
        'media_embed' => ['status' => TRUE],
      ],
      'name' => 'Media embed on',
    ]);

    $editor = $this->prophesize(EditorInterface::class);
    $editor->getFilterFormat()->willReturn($format);

    // Create a sample media entity to be embedded.
    $media = Media::create([
      'bundle' => $this->createMediaType('test')->id(),
      'name' => 'Screaming hairy armadillo',
      'field_media_test' => $this->randomString(),
    ]);
    $media->save();

    $form_state = new FormState();
    $form_state->setUserInput([
      'editor_object' => [
        'attributes' => [
          'data-entity-type' => 'media',
          'data-entity-uuid' => $media->uuid(),
          'data-align' => 'center',
        ],
        'hasCaption' => 'false',
        'label' => $media->label(),
        'link' => '',
        'hostEntityLangcode' => $media->language()->getId(),
        'classes' => '',
      ],
    ]);
    $form_state->setRequestMethod('POST');

    $form = EditorMediaDialog::create($this->container)
      ->buildForm([], $form_state, $editor->reveal());
    $this->assertNotNull($form, 'Form should have been built without errors.');
  }

}
