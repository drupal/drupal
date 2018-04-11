<?php

namespace Drupal\image\Tests;

use Drupal\image\Entity\ImageStyle;

/**
 * Tests creation, deletion, and editing of image styles and effects.
 *
 * @group image
 */
class ImageAdminStylesTest extends ImageFieldTestBase {

  /**
   * Tests editing Ajax-enabled image effect forms.
   */
  public function testAjaxEnabledEffectForm() {
    $admin_path = 'admin/config/media/image-styles';

    // Setup a style to be created and effects to add to it.
    $style_name = strtolower($this->randomMachineName(10));
    $style_label = $this->randomString();
    $style_path = $admin_path . '/manage/' . $style_name;
    $effect_edit = [
      'data[test_parameter]' => 100,
    ];

    // Add style form.
    $edit = [
      'name' => $style_name,
      'label' => $style_label,
    ];
    $this->drupalPostForm($admin_path . '/add', $edit, t('Create new style'));
    $this->assertRaw(t('Style %name was created.', ['%name' => $style_label]));

    // Add two Ajax-enabled test effects.
    $this->drupalPostForm($style_path, ['new' => 'image_module_test_ajax'], t('Add'));
    $this->drupalPostForm(NULL, $effect_edit, t('Add effect'));
    $this->drupalPostForm($style_path, ['new' => 'image_module_test_ajax'], t('Add'));
    $this->drupalPostForm(NULL, $effect_edit, t('Add effect'));

    // Load the saved image style.
    $style = ImageStyle::load($style_name);

    // Edit back the effects.
    foreach ($style->getEffects() as $uuid => $effect) {
      $effect_path = $admin_path . '/manage/' . $style_name . '/effects/' . $uuid;
      $this->drupalGet($effect_path);
      $this->drupalPostAjaxForm(NULL, $effect_edit, ['op' => t('Ajax refresh')]);
      $this->drupalPostForm(NULL, $effect_edit, t('Update effect'));
    }
  }

}
