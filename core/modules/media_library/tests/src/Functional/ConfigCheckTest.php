<?php

namespace Drupal\Tests\media_library\Functional;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\KernelTests\AssertConfigTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests installing media configuration from the Standard profile.
 *
 * @group media
 */
class ConfigCheckTest extends BrowserTestBase {

  use AssertConfigTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media_library'];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * Tests that the Standard profile installs all of its media configuration.
   */
  public function testMediaConfigFromStandard() {
    $active_storage = $this->container->get('config.storage');

    $profile_path = $this->container->get('extension.list.profile')
      ->getPath($this->profile);
    $default_storage = new FileStorage($profile_path . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY);

    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = $this->container->get('config.manager');

    // Test that all the media configuration is installed and looks correct.
    $config_names = [
      'core.entity_form_display.media.audio.default',
      'core.entity_form_display.media.audio.media_library',
      'core.entity_form_display.media.document.default',
      'core.entity_form_display.media.document.media_library',
      'core.entity_form_display.media.image.default',
      'core.entity_form_display.media.image.media_library',
      'core.entity_form_display.media.remote_video.default',
      'core.entity_form_display.media.remote_video.media_library',
      'core.entity_form_display.media.video.default',
      'core.entity_form_display.media.video.media_library',
      'core.entity_view_display.media.audio.default',
      'core.entity_view_display.media.audio.media_library',
      'core.entity_view_display.media.document.default',
      'core.entity_view_display.media.document.media_library',
      'core.entity_view_display.media.image.default',
      'core.entity_view_display.media.image.media_library',
      'core.entity_view_display.media.remote_video.default',
      'core.entity_view_display.media.remote_video.media_library',
      'core.entity_view_display.media.video.default',
      'core.entity_view_display.media.video.media_library',
      'field.field.media.audio.field_media_audio_file',
      'field.field.media.file.field_media_document',
      'field.field.media.image.field_media_image',
      'field.field.media.remote_video.field_media_oembed_video',
      'field.field.media.video.field_media_video_file',
      'field.storage.media.field_media_audio_file',
      'field.storage.media.field_media_document',
      'field.storage.media.field_media_image',
      'field.storage.media.field_media_oembed_video',
      'field.storage.media.field_media_video_file',
      'media.type.audio',
      'media.type.document',
      'media.type.image',
      'media.type.remote_video',
      'media.type.video',
    ];
    foreach ($config_names as $config_name) {
      if ($active_storage->exists($config_name)) {
        $result = $config_manager->diff($default_storage, $active_storage, $config_name);
        $this->assertConfigDiff($result, $config_name, []);
      }
      else {
        $this->fail("$config_name has not been installed");
      }
    }
  }

}
