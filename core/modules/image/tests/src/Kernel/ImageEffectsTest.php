<?php

namespace Drupal\Tests\image\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Form\ImageEffectEditForm;
use Drupal\Tests\Traits\Core\Image\ToolkitTestTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests image effects.
 *
 * @group image
 */
class ImageEffectsTest extends KernelTestBase {

  use ToolkitTestTrait;

  /**
   * The image effect plugin manager service.
   *
   * @var \Drupal\image\ImageEffectManager
   */
  protected $imageEffectPluginManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image',
    'image_module_test',
    'image_test',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->imageEffectPluginManager = $this->container->get('plugin.manager.image.effect');
  }

  /**
   * Tests the 'image_resize' effect.
   */
  public function testResizeEffect() {
    $this->assertImageEffect('image_resize', [
      'width' => 1,
      'height' => 2,
    ]);
    $this->assertToolkitOperationsCalled(['resize']);

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    // Width was passed correctly.
    $this->assertEquals(1, $calls['resize'][0][0]);
    // Height was passed correctly.
    $this->assertEquals(2, $calls['resize'][0][1]);
  }

  /**
   * Tests the 'image_scale' effect.
   */
  public function testScaleEffect() {
    // @todo Test also image upscaling in #3040887.
    // @see https://www.drupal.org/project/drupal/issues/3040887
    $this->assertImageEffect('image_scale', [
      'width' => 10,
      'height' => 10,
    ]);
    $this->assertToolkitOperationsCalled(['scale']);

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    // Width was passed correctly.
    $this->assertEquals(10, $calls['scale'][0][0]);
    // Height was based off aspect ratio and passed correctly.
    $this->assertEquals(10, $calls['scale'][0][1]);
  }

  /**
   * Tests the 'image_crop' effect.
   */
  public function testCropEffect() {
    // @todo Test also keyword offsets in #3040887.
    // @see https://www.drupal.org/project/drupal/issues/3040887
    $this->assertImageEffect('image_crop', [
      'anchor' => 'top-1',
      'width' => 3,
      'height' => 4,
    ]);
    $this->assertToolkitOperationsCalled(['crop']);

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    // X was passed correctly.
    $this->assertEquals(0, $calls['crop'][0][0]);
    // Y was passed correctly.
    $this->assertEquals(1, $calls['crop'][0][1]);
    // Width was passed correctly.
    $this->assertEquals(3, $calls['crop'][0][2]);
    // Height was passed correctly.
    $this->assertEquals(4, $calls['crop'][0][3]);
  }

  /**
   * Tests the 'image_convert' effect.
   */
  public function testConvertEffect() {
    // Test jpeg.
    $this->assertImageEffect('image_convert', [
      'extension' => 'jpeg',
    ]);
    $this->assertToolkitOperationsCalled(['convert']);

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    // Extension was passed correctly.
    $this->assertEquals('jpeg', $calls['convert'][0][0]);
  }

  /**
   * Tests the 'image_scale_and_crop' effect.
   */
  public function testScaleAndCropEffect() {
    $this->assertImageEffect('image_scale_and_crop', [
      'width' => 5,
      'height' => 10,
    ]);
    $this->assertToolkitOperationsCalled(['scale_and_crop']);

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    // X was computed and passed correctly.
    $this->assertEquals(7.5, $calls['scale_and_crop'][0][0]);
    // Y was computed and passed correctly.
    $this->assertEquals(0, $calls['scale_and_crop'][0][1]);
    // Width was computed and passed correctly.
    $this->assertEquals(5, $calls['scale_and_crop'][0][2]);
    // Height was computed and passed correctly.
    $this->assertEquals(10, $calls['scale_and_crop'][0][3]);
  }

  /**
   * Tests the 'image_scale_and_crop' effect with an anchor.
   */
  public function testScaleAndCropEffectWithAnchor() {
    $this->assertImageEffect('image_scale_and_crop', [
      'anchor' => 'top-1',
      'width' => 5,
      'height' => 10,
    ]);
    $this->assertToolkitOperationsCalled(['scale_and_crop']);

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    // X was computed and passed correctly.
    $this->assertEquals(0, $calls['scale_and_crop'][0][0]);
    // Y was computed and passed correctly.
    $this->assertEquals(1, $calls['scale_and_crop'][0][1]);
    // Width was computed and passed correctly.
    $this->assertEquals(5, $calls['scale_and_crop'][0][2]);
    // Height was computed and passed correctly.
    $this->assertEquals(10, $calls['scale_and_crop'][0][3]);
  }

  /**
   * Tests the 'image_desaturate' effect.
   */
  public function testDesaturateEffect() {
    $this->assertImageEffect('image_desaturate', []);
    $this->assertToolkitOperationsCalled(['desaturate']);

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    // No parameters were passed.
    $this->assertEmpty($calls['desaturate'][0]);
  }

  /**
   * Tests the image_rotate_effect() function.
   */
  public function testRotateEffect() {
    // @todo Test also with 'random' === TRUE in #3040887.
    // @see https://www.drupal.org/project/drupal/issues/3040887
    $this->assertImageEffect('image_rotate', [
      'degrees' => 90,
      'bgcolor' => '#fff',
    ]);
    $this->assertToolkitOperationsCalled(['rotate']);

    // Check the parameters.
    $calls = $this->imageTestGetAllCalls();
    // Degrees were passed correctly.
    $this->assertEquals(90, $calls['rotate'][0][0]);
    // Background color was passed correctly.
    $this->assertEquals('#fff', $calls['rotate'][0][1]);
  }

  /**
   * Tests image effect caching.
   */
  public function testImageEffectsCaching() {
    $state = $this->container->get('state');

    // The 'image_module_test.counter' state variable value is incremented in
    // image_module_test_image_effect_info_alter() every time the image effect
    // plugin definitions are recomputed.
    // @see image_module_test_image_effect_info_alter()
    $state->set('image_module_test.counter', 0);

    // First call should grab a fresh copy of the data.
    $effects = $this->imageEffectPluginManager->getDefinitions();
    $this->assertEquals(1, $state->get('image_module_test.counter'));

    // Second call should come from cache.
    $state->set('image_module_test.counter', 0);
    $cached_effects = $this->imageEffectPluginManager->getDefinitions();
    $this->assertEquals(0, $state->get('image_module_test.counter'));

    // Check that cached effects are the same as the processed.
    $this->assertSame($effects, $cached_effects);
  }

  /**
   * Tests that validation errors are passed from the plugin to the parent form.
   */
  public function testEffectFormValidationErrors() {
    $form_builder = $this->container->get('form_builder');

    /** @var \Drupal\image\ImageStyleInterface $image_style */
    $image_style = ImageStyle::create(['name' => 'foo']);
    $effect_id = $image_style->addImageEffect(['id' => 'image_scale']);
    $image_style->save();

    $form = new ImageEffectEditForm();
    $form_state = (new FormState())->setValues([
      'data' => ['width' => '', 'height' => ''],
    ]);
    $form_builder->submitForm($form, $form_state, $image_style, $effect_id);

    $errors = $form_state->getErrors();
    $this->assertCount(1, $errors);
    $error = reset($errors);
    $this->assertEquals('Width and height can not both be blank.', $error);
  }

  /**
   * Asserts the effect processing of an image effect plugin.
   *
   * @param string $effect_name
   *   The name of the image effect to test.
   * @param array $data
   *   The data to be passed to the image effect.
   */
  protected function assertImageEffect($effect_name, array $data) {
    $effect = $this->imageEffectPluginManager->createInstance($effect_name, ['data' => $data]);
    $image = $this->getImage();
    $this->imageTestReset();
    $this->assertTrue($effect->applyEffect($image));
  }

}
