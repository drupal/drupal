<?php

namespace Drupal\Tests\media\Functional\FieldWidget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\media\Functional\MediaFunctionalTestBase;

/**
 * @covers \Drupal\media\Plugin\Field\FieldWidget\OEmbedWidget
 *
 * @group media
 */
class OEmbedFieldWidgetTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'link',
    'media_test_oembed',
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

    \Drupal::configFactory()
      ->getEditable('media.settings')
      ->set('standalone_url', TRUE)
      ->save(TRUE);

    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Test to ensure that help text exists when it is set on field configuration.
   */
  public function testFieldWidgetHelpText() {
    $this->drupalLogin($this->rootUser);

    $media_type = $this->createMediaType('oembed:video');

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media_oembed_video_test',
      'entity_type' => 'media',
      'type' => 'string',
      'cardinality' => 1,
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $media_type->id(),
      'description' => 'This is help text for oEmbed field.',
    ])->save();

    $entity_form_display = EntityFormDisplay::load("media.{$media_type->id()}.default");
    $entity_form_display->setComponent('field_media_oembed_video_test', [
      'type' => 'oembed_textfield',
      'settings' => [
        'size' => 60,
        'region' => 'content',
      ],
    ]);
    $entity_form_display->save();

    $this->drupalGet('media/add/' . $media_type->id());
    $this->assertSession()->pageTextContains('This is help text for oEmbed field.');
  }

}
