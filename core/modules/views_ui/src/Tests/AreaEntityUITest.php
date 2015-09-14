<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\AreaEntityUITest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\block\Entity\Block;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\views\Entity\View;

/**
 * Tests the entity area UI test.
 *
 * @see \Drupal\views\Plugin\views\area\Entity
 * @group views_ui
 */
class AreaEntityUITest extends UITestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test'];

  public function testUI() {
    // Set up a block and a entity_test entity.
    $block = Block::create(['id' => 'test_id', 'plugin' => 'system_main_block']);
    $block->save();

    $entity_test = EntityTest::create(['bundle' => 'entity_test']);
    $entity_test->save();

    $default = $this->randomView([]);
    $id = $default['id'];
    $view = View::load($id);

    $this->drupalGet($view->urlInfo('edit-form'));

    // Add a global NULL argument to the view for testing argument placeholders.
    $this->drupalPostForm("admin/structure/views/nojs/add-handler/$id/page_1/argument", ['name[views.null]' => 1], 'Add and configure contextual filters');
    $this->drupalPostForm(NULL, [], 'Apply');

    // Configure both the entity_test area header and the block header to
    // reference the given entities.
    $this->drupalPostForm("admin/structure/views/nojs/add-handler/$id/page_1/header", ['name[views.entity_block]' => 1], 'Add and configure header');
    $this->drupalPostForm(NULL, ['options[target]' => $block->id()], 'Apply');

    $this->drupalPostForm("admin/structure/views/nojs/add-handler/$id/page_1/header", ['name[views.entity_entity_test]' => 1], 'Add and configure header');
    $this->drupalPostForm(NULL, ['options[target]' => $entity_test->id()], 'Apply');

    $this->drupalPostForm(NULL, [], 'Save');

    // Confirm the correct target identifiers were saved for both entities.
    $view = View::load($id);
    $header = $view->getDisplay('default')['display_options']['header'];
    $this->assertEqual(['entity_block', 'entity_entity_test'], array_keys($header));

    $this->assertEqual($block->id(), $header['entity_block']['target']);
    $this->assertEqual($entity_test->uuid(), $header['entity_entity_test']['target']);

    // Confirm that the correct serial ID (for the entity_test) and config ID
    // (for the block) are displayed in the form.
    $this->drupalGet("admin/structure/views/nojs/handler/$id/page_1/header/entity_block");
    $this->assertFieldByName('options[target]', $block->id());

    $this->drupalGet("admin/structure/views/nojs/handler/$id/page_1/header/entity_entity_test");
    $this->assertFieldByName('options[target]', $entity_test->id());

    // Replace the header target entities with argument placeholders.
    $this->drupalPostForm("admin/structure/views/nojs/handler/$id/page_1/header/entity_block", ['options[target]' => '{{ raw_arguments.null }}'], 'Apply');
    $this->drupalPostForm("admin/structure/views/nojs/handler/$id/page_1/header/entity_entity_test", ['options[target]' => '{{ raw_arguments.null }}'], 'Apply');
    $this->drupalPostForm(NULL, [], 'Save');

    // Confirm that the argument placeholders are saved.
    $view = View::load($id);
    $header = $view->getDisplay('default')['display_options']['header'];
    $this->assertEqual(['entity_block', 'entity_entity_test'], array_keys($header));

    $this->assertEqual('{{ raw_arguments.null }}', $header['entity_block']['target']);
    $this->assertEqual('{{ raw_arguments.null }}', $header['entity_entity_test']['target']);

    // Confirm that the argument placeholders are still displayed in the form.
    $this->drupalGet("admin/structure/views/nojs/handler/$id/page_1/header/entity_block");
    $this->assertFieldByName('options[target]', '{{ raw_arguments.null }}');

    $this->drupalGet("admin/structure/views/nojs/handler/$id/page_1/header/entity_entity_test");
    $this->assertFieldByName('options[target]', '{{ raw_arguments.null }}');

    // Change the targets for both headers back to the entities.
    $this->drupalPostForm("admin/structure/views/nojs/handler/$id/page_1/header/entity_block", ['options[target]' => $block->id()], 'Apply');
    $this->drupalPostForm("admin/structure/views/nojs/handler/$id/page_1/header/entity_entity_test", ['options[target]' => $entity_test->id()], 'Apply');
    $this->drupalPostForm(NULL, [], 'Save');

    // Confirm the targets were again saved correctly and not skipped based on
    // the previous form value.
    $view = View::load($id);
    $header = $view->getDisplay('default')['display_options']['header'];
    $this->assertEqual(['entity_block', 'entity_entity_test'], array_keys($header));

    $this->assertEqual($block->id(), $header['entity_block']['target']);
    $this->assertEqual($entity_test->uuid(), $header['entity_entity_test']['target']);
  }

}
