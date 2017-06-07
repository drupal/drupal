<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\views\Entity\View;

/**
 * Tests the token display for the TokenizeAreaPluginBase UI.
 *
 * @see \Drupal\views\Plugin\views\area\Entity
 * @group views_ui
 */
class TokenizeAreaUITest extends UITestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test'];

  /**
   * Test that the right tokens are shown as available for replacement.
   */
  public function testTokenUI() {
    $entity_test = EntityTest::create(['bundle' => 'entity_test']);
    $entity_test->save();

    $default = $this->randomView([]);
    $id = $default['id'];
    $view = View::load($id);

    $this->drupalGet($view->toUrl('edit-form'));

    // Add a global NULL argument to the view for testing argument tokens.
    $this->drupalPostForm("admin/structure/views/nojs/add-handler/$id/page_1/argument", ['name[views.null]' => 1], 'Add and configure contextual filters');
    $this->drupalPostForm(NULL, [], 'Apply');

    $this->drupalPostForm("admin/structure/views/nojs/add-handler/$id/page_1/header", ['name[views.area]' => 'views.area'], 'Add and configure header');
    // Test that field tokens are shown.
    $this->assertText('{{ title }} == Content: Title');
    // Test that argument tokens are shown.
    $this->assertText('{{ arguments.null }} == Global: Null title');
  }

}
