<?php

namespace Drupal\Tests\image\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests adding an 'anchor' setting to existing scale and crop image effects.
 *
 * @see image_post_update_scale_and_crop_effect_add_anchor()
 *
 * @group Update
 * @group legacy
 */
class ScaleAndCropAddAnchorUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/test_scale_and_crop_add_anchor.php',
    ];
  }

  /**
   * Tests that 'anchor' setting is properly added.
   */
  public function testImagePostUpdateScaleAndCropEffectAddAnchor() {
    // Test that the first effect does not have an 'anchor' setting.
    $effect_data = $this->config('image.style.test_scale_and_crop_add_anchor')->get('effects.8c7170c9-5bcc-40f9-8698-f88a8be6d434.data');
    $this->assertFalse(array_key_exists('anchor', $effect_data));

    // Test that the second effect has an 'anchor' setting.
    $effect_data = $this->config('image.style.test_scale_and_crop_add_anchor')->get('effects.a8d83b12-abc6-40c8-9c2f-78a4e421cf97.data');
    $this->assertTrue(array_key_exists('anchor', $effect_data));

    // Test that the third effect does not have an 'anchor' setting.
    $effect_data = $this->config('image.style.test_scale_and_crop_add_anchor')->get('effects.1bffd475-19d0-439a-b6a1-7e5850ce40f9.data');
    $this->assertFalse(array_key_exists('anchor', $effect_data));

    $this->runUpdates();

    // Test that the first effect now has an 'anchor' setting.
    $effect_data = $this->config('image.style.test_scale_and_crop_add_anchor')->get('effects.8c7170c9-5bcc-40f9-8698-f88a8be6d434.data');
    $this->assertTrue(array_key_exists('anchor', $effect_data));
    $this->assertEquals('center-center', $effect_data['anchor']);

    // Test that the second effect's 'anchor' setting is unchanged.
    $effect_data = $this->config('image.style.test_scale_and_crop_add_anchor')->get('effects.a8d83b12-abc6-40c8-9c2f-78a4e421cf97.data');
    $this->assertTrue(array_key_exists('anchor', $effect_data));
    $this->assertEquals('left-top', $effect_data['anchor']);

    // Test that the third effect still does not have an 'anchor' setting.
    $effect_data = $this->config('image.style.test_scale_and_crop_add_anchor')->get('effects.1bffd475-19d0-439a-b6a1-7e5850ce40f9.data');
    $this->assertFalse(array_key_exists('anchor', $effect_data));
  }

}
