<?php

namespace Drupal\image\Tests\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests Image update path.
 *
 * @group image
 */
class ImageUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8-rc1.bare.standard.php.gz',
    ];
  }

  /**
   * Tests image_post_update_image_style_dependencies().
   *
   * @see image_post_update_image_style_dependencies()
   */
  public function testPostUpdateImageStylesDependencies() {
    $view = 'core.entity_view_display.node.article.default';
    $form = 'core.entity_form_display.node.article.default';

    // Check that view display 'node.article.default' doesn't depend on image
    // style 'image.style.large'.
    $dependencies = $this->config($view)->get('dependencies.config');
    $this->assertFalse(in_array('image.style.large', $dependencies));
    // Check that form display 'node.article.default' doesn't depend on image
    // style 'image.style.thumbnail'.
    $dependencies = $this->config($form)->get('dependencies.config');
    $this->assertFalse(in_array('image.style.thumbnail', $dependencies));

    // Run updates.
    $this->runUpdates();

    // Check that view display 'node.article.default' depend on image style
    // 'image.style.large'.
    $dependencies = $this->config($view)->get('dependencies.config');
    $this->assertTrue(in_array('image.style.large', $dependencies));
    // Check that form display 'node.article.default' depend on image style
    // 'image.style.thumbnail'.
    $dependencies = $this->config($view)->get('dependencies.config');
    $this->assertTrue(in_array('image.style.large', $dependencies));
  }

}
