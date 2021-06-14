<?php

namespace Drupal\Tests\views_ui\Functional;

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
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testUI() {
    // Set up a block and an entity_test entity.
    $block = Block::create(['id' => 'test_id', 'plugin' => 'system_main_block']);
    $block->save();

    $entity_test = EntityTest::create(['bundle' => 'entity_test']);
    $entity_test->save();

    $default = $this->randomView([]);
    $id = $default['id'];
    $view = View::load($id);

    $this->drupalGet($view->toUrl('edit-form'));

    // Add a global NULL argument to the view for testing argument placeholders.
    $this->drupalGet("admin/structure/views/nojs/add-handler/{$id}/page_1/argument");
    $this->submitForm(['name[views.null]' => TRUE], 'Add and configure contextual filters');
    $this->submitForm([], 'Apply');

    // Configure both the entity_test area header and the block header to
    // reference the given entities.
    $this->drupalGet("admin/structure/views/nojs/add-handler/{$id}/page_1/header");
    $this->submitForm(['name[views.entity_block]' => TRUE], 'Add and configure header');
    $this->submitForm(['options[target]' => $block->id()], 'Apply');

    $this->drupalGet("admin/structure/views/nojs/add-handler/{$id}/page_1/header");
    $this->submitForm(['name[views.entity_entity_test]' => TRUE], 'Add and configure header');
    $this->submitForm(['options[target]' => $entity_test->id()], 'Apply');

    $this->submitForm([], 'Save');

    // Confirm the correct target identifiers were saved for both entities.
    $view = View::load($id);
    $header = $view->getDisplay('default')['display_options']['header'];
    $this->assertEquals(['entity_block', 'entity_entity_test'], array_keys($header));

    $this->assertEquals($block->id(), $header['entity_block']['target']);
    $this->assertEquals($entity_test->uuid(), $header['entity_entity_test']['target']);

    // Confirm that the correct serial ID (for the entity_test) and config ID
    // (for the block) are displayed in the form.
    $this->drupalGet("admin/structure/views/nojs/handler/$id/page_1/header/entity_block");
    $this->assertSession()->fieldValueEquals('options[target]', $block->id());

    $this->drupalGet("admin/structure/views/nojs/handler/$id/page_1/header/entity_entity_test");
    $this->assertSession()->fieldValueEquals('options[target]', $entity_test->id());

    // Replace the header target entities with argument placeholders.
    $this->drupalGet("admin/structure/views/nojs/handler/{$id}/page_1/header/entity_block");
    $this->submitForm(['options[target]' => '{{ raw_arguments.null }}'], 'Apply');
    $this->drupalGet("admin/structure/views/nojs/handler/{$id}/page_1/header/entity_entity_test");
    $this->submitForm(['options[target]' => '{{ raw_arguments.null }}'], 'Apply');
    $this->submitForm([], 'Save');

    // Confirm that the argument placeholders are saved.
    $view = View::load($id);
    $header = $view->getDisplay('default')['display_options']['header'];
    $this->assertEquals(['entity_block', 'entity_entity_test'], array_keys($header));

    $this->assertEquals('{{ raw_arguments.null }}', $header['entity_block']['target']);
    $this->assertEquals('{{ raw_arguments.null }}', $header['entity_entity_test']['target']);

    // Confirm that the argument placeholders are still displayed in the form.
    $this->drupalGet("admin/structure/views/nojs/handler/$id/page_1/header/entity_block");
    $this->assertSession()->fieldValueEquals('options[target]', '{{ raw_arguments.null }}');

    $this->drupalGet("admin/structure/views/nojs/handler/$id/page_1/header/entity_entity_test");
    $this->assertSession()->fieldValueEquals('options[target]', '{{ raw_arguments.null }}');

    // Change the targets for both headers back to the entities.
    $this->drupalGet("admin/structure/views/nojs/handler/{$id}/page_1/header/entity_block");
    $this->submitForm(['options[target]' => $block->id()], 'Apply');
    $this->drupalGet("admin/structure/views/nojs/handler/{$id}/page_1/header/entity_entity_test");
    $this->submitForm(['options[target]' => $entity_test->id()], 'Apply');
    $this->submitForm([], 'Save');

    // Confirm the targets were again saved correctly and not skipped based on
    // the previous form value.
    $view = View::load($id);
    $header = $view->getDisplay('default')['display_options']['header'];
    $this->assertEquals(['entity_block', 'entity_entity_test'], array_keys($header));

    $this->assertEquals($block->id(), $header['entity_block']['target']);
    $this->assertEquals($entity_test->uuid(), $header['entity_entity_test']['target']);
  }

}
