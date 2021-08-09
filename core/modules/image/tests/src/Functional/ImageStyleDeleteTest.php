<?php

namespace Drupal\Tests\image\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Tests image style deletion using the UI.
 *
 * @group image
 */
class ImageStyleDeleteTest extends ImageFieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create an image field 'foo' having the image style 'medium' as widget
    // preview and as formatter.
    $this->createImageField('foo', 'page', [], [], ['preview_image_style' => 'medium'], ['image_style' => 'medium']);
  }

  /**
   * Tests image style deletion.
   */
  public function testDelete() {
    $this->drupalGet('admin/config/media/image-styles/manage/medium/delete');
    // Checks that the 'replacement' select element is displayed.
    $this->assertSession()->fieldExists('replacement');
    // Checks that UI messages are correct.
    $this->assertSession()->pageTextContains("If this style is in use on the site, you may select another style to replace it. All images that have been generated for this style will be permanently deleted. If no replacement style is selected, the dependent configurations might need manual reconfiguration.");
    $this->assertSession()->pageTextNotContains("All images that have been generated for this style will be permanently deleted. The dependent configurations might need manual reconfiguration.");

    // Delete 'medium' image style but replace it with 'thumbnail'. This style
    // is involved in 'node.page.default' display view and form.
    $this->submitForm(['replacement' => 'thumbnail'], 'Delete');

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
    $view_display = EntityViewDisplay::load('node.page.default');
    // Checks that the formatter setting is replaced.
    $this->assertNotNull($component = $view_display->getComponent('foo'));
    $this->assertSame('thumbnail', $component['settings']['image_style']);

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = EntityFormDisplay::load('node.page.default');
    // Check that the widget setting is replaced.
    $this->assertNotNull($component = $form_display->getComponent('foo'));
    $this->assertSame('thumbnail', $component['settings']['preview_image_style']);

    $this->drupalGet('admin/config/media/image-styles/manage/thumbnail/delete');
    // Checks that the 'replacement' select element is displayed.
    $this->assertSession()->fieldExists('replacement');
    // Checks that UI messages are correct.
    $this->assertSession()->pageTextContains("If this style is in use on the site, you may select another style to replace it. All images that have been generated for this style will be permanently deleted. If no replacement style is selected, the dependent configurations might need manual reconfiguration.");
    $this->assertSession()->pageTextNotContains("All images that have been generated for this style will be permanently deleted. The dependent configurations might need manual reconfiguration.");

    // Delete 'thumbnail' image style. Provide no replacement.
    $this->submitForm([], 'Delete');

    $view_display = EntityViewDisplay::load('node.page.default');
    // Checks that the formatter setting is disabled.
    $this->assertNull($view_display->getComponent('foo'));
    $this->assertNotNull($view_display->get('hidden')['foo']);
    // Checks that widget setting is preserved with the image preview disabled.
    $form_display = EntityFormDisplay::load('node.page.default');
    $this->assertNotNull($widget = $form_display->getComponent('foo'));
    $this->assertSame('', $widget['settings']['preview_image_style']);

    $this->drupalGet('admin/config/media/image-styles/manage/wide/delete');
    // Checks that the 'replacement' select element is displayed.
    $this->assertSession()->fieldExists('replacement');
    // Checks that UI messages are correct.
    $this->assertSession()->pageTextContains("If this style is in use on the site, you may select another style to replace it. All images that have been generated for this style will be permanently deleted. If no replacement style is selected, the dependent configurations might need manual reconfiguration.");
    $this->assertSession()->pageTextNotContains("All images that have been generated for this style will be permanently deleted. The dependent configurations might need manual reconfiguration.");
    // Delete 'wide' image style. Provide no replacement.
    $this->submitForm([], 'Delete');

    // Now, there's only one image style configured on the system: 'large'.
    $this->drupalGet('admin/config/media/image-styles/manage/large/delete');
    // Checks that the 'replacement' select element is not displayed.
    $this->assertSession()->fieldNotExists('replacement');
    // Checks that UI messages are correct.
    $this->assertSession()->pageTextNotContains("If this style is in use on the site, you may select another style to replace it. All images that have been generated for this style will be permanently deleted. If no replacement style is selected, the dependent configurations might need manual reconfiguration.");
    $this->assertSession()->pageTextContains("All images that have been generated for this style will be permanently deleted. The dependent configurations might need manual reconfiguration.");
  }

}
