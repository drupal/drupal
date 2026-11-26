<?php

declare(strict_types=1);

namespace Drupal\image_module_test\Hook;

use Drupal\image\ImageStyleInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for image_module_test.
 */
class ImageModuleTestHooks {

  /**
   * Implements hook_image_effect_info_alter().
   */
  #[Hook('image_effect_info_alter')]
  public function imageEffectInfoAlter(&$effects): void {
    $key_value = \Drupal::keyValue('image');
    // The 'image_module_test.counter' key/value entry is set and accessed
    // from the ImageEffectsTest::testImageEffectsCaching() test and used to
    // signal if the image effect plugin definitions were computed or were
    // retrieved from the cache.
    // @see \Drupal\Tests\image\Kernel\ImageEffectsTest::testImageEffectsCaching()
    $counter = $key_value->get('image_module_test.counter');
    // Increase the test counter, signaling that image effects were processed,
    // rather than being served from the cache.
    $key_value->set('image_module_test.counter', ++$counter);
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   *
   * Used to save test third party settings in the image style entity.
   */
  #[Hook('image_style_presave')]
  public function imageStylePresave(ImageStyleInterface $style): void {
    $style->setThirdPartySetting('image_module_test', 'foo', 'bar');
  }

  /**
   * Implements hook_image_style_flush().
   */
  #[Hook('image_style_flush')]
  public function imageStyleFlush($style, $path = NULL): void {
    \Drupal::keyValue('image')->set('image_module_test_image_style_flush.called', $path);
  }

  /**
   * Implements hook_file_download().
   */
  #[Hook('file_download')]
  public function fileDownload($uri): array|int {
    $default_uri = \Drupal::keyValue('image')->get('test_file_download', FALSE);
    if ($default_uri == $uri) {
      return ['X-Image-Owned-By' => 'image_module_test'];
    }
    // When we have custom stream wrapper is enabled, do the validation based
    // on a key/value entry to allow it.
    if (str_starts_with($uri, 'image-test-dummy://')) {
      if (\Drupal::keyValue('image')->get('image_module_test.allow_image_test_dummy', FALSE)) {
        return ['X-Allowed-By' => 'image_module_test'];
      }
      return -1;
    }
    return [];
  }

}
