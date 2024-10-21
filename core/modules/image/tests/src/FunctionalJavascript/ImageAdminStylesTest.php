<?php

declare(strict_types=1);

namespace Drupal\Tests\image\FunctionalJavascript;

use Drupal\image\Entity\ImageStyle;

/**
 * Tests creation, deletion, and editing of image styles and effects.
 *
 * @group image
 */
class ImageAdminStylesTest extends ImageFieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests editing Ajax-enabled image effect forms.
   */
  public function testAjaxEnabledEffectForm(): void {
    $admin_path = 'admin/config/media/image-styles';

    // Setup a style to be created and effects to add to it.
    $style_name = $this->randomMachineName(10);
    $style_label = $this->randomString();
    $style_path = $admin_path . '/manage/' . $style_name;
    $effect_edit = [
      'data[test_parameter]' => 100,
    ];

    // Add style form.
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();
    $this->drupalGet($admin_path . '/add');
    $page->findField('label')->setValue($style_label);
    $assert->waitForElementVisible('named', ['button', 'Edit'])->press();
    $assert->waitForElementVisible('named', ['id_or_name', 'name'])->setValue($style_name);
    $page->pressButton('Create new style');
    $assert->statusMessageContains("Style $style_label was created.", 'status');

    // Add two Ajax-enabled test effects.
    $this->drupalGet($style_path);
    $this->submitForm(['new' => 'image_module_test_ajax'], 'Add');
    $this->submitForm($effect_edit, 'Add effect');
    $this->drupalGet($style_path);
    $this->submitForm(['new' => 'image_module_test_ajax'], 'Add');
    $this->submitForm($effect_edit, 'Add effect');

    // Load the saved image style.
    $style = ImageStyle::load($style_name);

    // Edit back the effects.
    foreach ($style->getEffects() as $uuid => $effect) {
      $effect_path = $admin_path . '/manage/' . $style_name . '/effects/' . $uuid;
      $this->drupalGet($effect_path);
      $this->assertSession()->fieldValueEquals('data[test_parameter]', '100');
      $page->findField('data[test_parameter]')->setValue(111);
      $ajax_value = $page->find('css', '#ajax-value')->getText();
      $this->assertSame('Ajax value bar', $ajax_value);
      $this->getSession()->getPage()->pressButton('Ajax refresh');
      $this->assertTrue($page->waitFor(10, function ($page) {
        $ajax_value = $page->find('css', '#ajax-value')->getText();
        return (bool) preg_match('/^Ajax value [0-9.]+ [0-9.]+$/', $ajax_value);
      }));
      $page->pressButton('Update effect');
      $assert->statusMessageContains('The image effect was successfully applied.', 'status');
      $this->drupalGet($effect_path);
      $this->assertSession()->fieldValueEquals('data[test_parameter]', '111');
    }

    // Edit the 1st effect, multiple AJAX calls before updating.
    $style = ImageStyle::load($style_name);
    $uuid = array_values($style->getEffects()->getInstanceIds())[0];
    $this->drupalGet($admin_path . '/manage/' . $style_name . '/effects/' . $uuid);
    $this->assertSession()->fieldValueEquals('data[test_parameter]', '111');
    $field = $page->findField('data[test_parameter]');
    $field->setValue(200);
    $page->pressButton('Ajax refresh');
    $this->assertSession()->assertExpectedAjaxRequest(1);
    $field->setValue(300);
    $page->pressButton('Ajax refresh');
    $this->assertSession()->assertExpectedAjaxRequest(2);
    $field->setValue(400);
    $page->pressButton('Ajax refresh');
    $this->assertSession()->assertExpectedAjaxRequest(3);
    $page->pressButton('Update effect');
    $this->assertSession()->statusMessageContains('The image effect was successfully applied.', 'status');
    $style = ImageStyle::load($style_name);
    $effectConfiguration = $style->getEffect($uuid)->getConfiguration();
    $this->assertSame(400, $effectConfiguration['data']['test_parameter']);
    $this->drupalGet($admin_path . '/manage/' . $style_name . '/effects/' . $uuid);
    $this->assertSession()->fieldValueEquals('data[test_parameter]', '400');
  }

}
