<?php

namespace Drupal\Tests\media\Functional;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\KernelTests\AssertConfigTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests media config from standard profile.
 *
 * @group media
 */
class ConfigCheckTest extends BrowserTestBase {
  use AssertConfigTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
  ];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function installParameters() {
    $parameters = parent::installParameters();
    $parameters['forms']['install_configure_form']['site_mail'] = 'admin@example.com';
    return $parameters;
  }

  /**
   * Tests the media config on the standard profile.
   */
  public function testMediaConfigFromStandard() {
    $active_config_storage = $this->container->get('config.storage');

    $default_config_storage = new FileStorage(drupal_get_path('profile', 'standard') . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY, InstallStorage::DEFAULT_COLLECTION);
    $this->assertDefaultConfig($default_config_storage, $active_config_storage);
  }

  /**
   * Asserts that the default configuration matches active configuration.
   *
   * @param \Drupal\Core\Config\StorageInterface $default_config_storage
   *   The default configuration storage to check.
   * @param \Drupal\Core\Config\StorageInterface $active_config_storage
   *   The active configuration storage.
   */
  protected function assertDefaultConfig(StorageInterface $default_config_storage, StorageInterface $active_config_storage) {
    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = $this->container->get('config.manager');

    // Only test the config provided by the media module.
    $config_names = [
      'core.entity_form_display.media.audio.default',
      'core.entity_form_display.media.file.default',
      'core.entity_form_display.media.image.default',
      'core.entity_form_display.media.video.default',
      'core.entity_view_display.media.audio.default',
      'core.entity_view_display.media.file.default',
      'core.entity_view_display.media.image.default',
      'core.entity_view_display.media.video.default',
      'field.field.media.audio.field_media_audio_file',
      'field.field.media.file.field_media_file',
      'field.field.media.image.field_media_image',
      'field.field.media.video.field_media_video_file',
      'field.storage.media.field_media_audio_file',
      'field.storage.media.field_media_file',
      'field.storage.media.field_media_image',
      'field.storage.media.field_media_video_file',
      'media.type.audio',
      'media.type.file',
      'media.type.image',
      'media.type.video',
    ];
    foreach ($config_names as $config_name) {
      if ($active_config_storage->exists($config_name)) {
        $result = $config_manager->diff($default_config_storage, $active_config_storage, $config_name);
        $this->assertConfigDiff($result, $config_name, []);
      }
      else {
        $this->fail("$config_name has not been installed");
      }
    }
  }

}
