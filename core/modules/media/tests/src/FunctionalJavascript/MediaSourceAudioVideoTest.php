<?php

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;

/**
 * Tests the Audio and Video media sources.
 *
 * @group media
 */
class MediaSourceAudioVideoTest extends MediaSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Check the Audio source functionality.
   */
  public function testAudioTypeCreation() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $source_id = 'audio_file';
    $type_name = 'audio_type';
    $field_name = 'field_media_' . $source_id;
    $this->doTestCreateMediaType($type_name, $source_id);

    // Check that the source field was created with the correct settings.
    $storage = FieldStorageConfig::load("media.$field_name");
    $this->assertInstanceOf(FieldStorageConfig::class, $storage);
    $field = FieldConfig::load("media.$type_name.$field_name");
    $this->assertInstanceOf(FieldConfig::class, $field);
    $this->assertSame('mp3 wav aac', FieldConfig::load("media.$type_name.$field_name")->get('settings')['file_extensions']);

    // Check that the display holds the correct formatter configuration.
    $display = EntityViewDisplay::load("media.$type_name.default");
    $this->assertInstanceOf(EntityViewDisplay::class, $display);
    $formatter = $display->getComponent($field_name)['type'];
    $this->assertSame('file_audio', $formatter);

    // Create a media asset.
    file_put_contents('public://file.mp3', str_repeat('t', 10));
    $file = File::create([
      'uri' => 'public://file.mp3',
      'filename' => 'file.mp3',
    ]);
    $file->save();

    $this->drupalGet("media/add/$type_name");
    $page->fillField('Name', 'Audio media asset');
    $page->attachFileToField("files[{$field_name}_0]", \Drupal::service('file_system')->realpath('public://file.mp3'));
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');

    // Verify that there is a creation message and that it contains a link to
    // the media entity.
    $assert_session->pageTextContains("$type_name Audio media asset has been created.");
    $this->drupalGet($this->assertLinkToCreatedMedia());

    // Verify that the <audio> tag is present on the media entity view.
    $assert_session->elementExists('css', "audio > source[type='audio/mpeg']");
  }

  /**
   * Check the Video source functionality.
   */
  public function testVideoTypeCreation() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $source_id = 'video_file';
    $type_name = 'video_type';
    $field_name = 'field_media_' . $source_id;
    $this->doTestCreateMediaType($type_name, $source_id);

    // Check that the source field was created with the correct settings.
    $storage = FieldStorageConfig::load("media.$field_name");
    $this->assertInstanceOf(FieldStorageConfig::class, $storage);
    $field = FieldConfig::load("media.$type_name.$field_name");
    $this->assertInstanceOf(FieldConfig::class, $field);
    $this->assertSame('mp4', FieldConfig::load("media.$type_name.$field_name")->getSetting('file_extensions'));

    // Check that the display holds the correct formatter configuration.
    $display = EntityViewDisplay::load("media.$type_name.default");
    $this->assertInstanceOf(EntityViewDisplay::class, $display);
    $formatter = $display->getComponent($field_name)['type'];
    $this->assertSame('file_video', $formatter);

    // Create a media asset.
    file_put_contents('public://file.mp4', str_repeat('t', 10));
    $file = File::create([
      'uri' => 'public://file.mp4',
      'filename' => 'file.mp4',
    ]);
    $file->save();

    $this->drupalGet("media/add/$type_name");
    $page->fillField('Name', 'Video media asset');
    $page->attachFileToField("files[{$field_name}_0]", \Drupal::service('file_system')->realpath('public://file.mp4'));
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');

    // Verify that there is a creation message and that it contains a link to
    // the media entity.
    $assert_session->pageTextContains("$type_name Video media asset has been created.");

    $this->drupalGet($this->assertLinkToCreatedMedia());
    // Verify that the <video> tag is present on the media entity view.
    $assert_session->elementExists('css', "video > source[type='video/mp4']");
  }

}
