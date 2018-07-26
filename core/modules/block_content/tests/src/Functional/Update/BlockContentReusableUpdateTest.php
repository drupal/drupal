<?php

namespace Drupal\Tests\block_content\Functional\Update;

use Drupal\block_content\Entity\BlockContent;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests 'reusable' field related update functions for the Block Content module.
 *
 * @group Update
 * @group block_content
 * @group legacy
 */
class BlockContentReusableUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      // Override the 'block_content' view with an extra display with overridden
      // filters. This extra display should also have a filter added for
      // 'reusable' field so that it does not expose non-reusable fields. This
      // display also has a filter that only shows blocks that contain 'block2'
      // in the 'info' field.
      __DIR__ . '/../../../fixtures/update/drupal-8.views_block_content-2976334.php',
    ];
  }

  /**
   * Tests adding 'reusable' entity base field to the block content entity type.
   *
   * @see block_content_update_8600()
   * @see block_content_post_update_add_views_reusable_filter()
   */
  public function testReusableFieldAddition() {
    $assert_session = $this->assertSession();
    $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();

    // Ensure that 'reusable' field is not present before updates.
    $this->assertEmpty($entity_definition_update_manager->getFieldStorageDefinition('reusable', 'block_content'));

    // Ensure that 'reusable' filter is not present before updates.
    $view_config = \Drupal::configFactory()->get('views.view.block_content');
    $this->assertFalse($view_config->isNew());
    $this->assertEmpty($view_config->get('display.default.display_options.filters.reusable'));
    $this->assertEmpty($view_config->get('display.page_2.display_options.filters.reusable'));
    // Run updates.
    $this->runUpdates();

    // Ensure that 'reusable' filter is present after updates.
    \Drupal::configFactory()->clearStaticCache();
    $view_config = \Drupal::configFactory()->get('views.view.block_content');
    $this->assertNotEmpty($view_config->get('display.default.display_options.filters.reusable'));
    $this->assertNotEmpty($view_config->get('display.page_2.display_options.filters.reusable'));

    // Check that the field exists and is configured correctly.
    $reusable_field = $entity_definition_update_manager->getFieldStorageDefinition('reusable', 'block_content');
    $this->assertEquals('Reusable', $reusable_field->getLabel());
    $this->assertEquals('A boolean indicating whether this block is reusable.', $reusable_field->getDescription());
    $this->assertEquals(FALSE, $reusable_field->isRevisionable());
    $this->assertEquals(FALSE, $reusable_field->isTranslatable());

    $after_block1 = BlockContent::create([
      'info' => 'After update block1',
      'type' => 'basic_block',
    ]);
    $after_block1->save();
    // Add second block that will be shown with the 'info' filter on the
    // additional view display.
    $after_block2 = BlockContent::create([
      'info' => 'After update block2',
      'type' => 'basic_block',
    ]);
    $after_block2->save();

    $this->assertTrue($after_block1->isReusable());
    $this->assertTrue($after_block2->isReusable());

    $admin_user = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($admin_user);

    $block_non_reusable = BlockContent::create([
      'info' => 'block1 non reusable',
      'type' => 'basic_block',
      'reusable' => FALSE,
    ]);
    $block_non_reusable->save();
    // Add second block that would be shown with the 'info' filter on the
    // additional view display if the 'reusable' filter was not added.
    $block2_non_reusable = BlockContent::create([
      'info' => 'block2 non reusable',
      'type' => 'basic_block',
      'reusable' => FALSE,
    ]);
    $block2_non_reusable->save();
    $this->assertFalse($block_non_reusable->isReusable());
    $this->assertFalse($block2_non_reusable->isReusable());

    // Ensure the Custom Block view shows the reusable blocks only.
    $this->drupalGet('admin/structure/block/block-content');
    $assert_session->statusCodeEquals('200');
    $assert_session->responseContains('view-id-block_content');
    $assert_session->pageTextContains($after_block1->label());
    $assert_session->pageTextContains($after_block2->label());
    $assert_session->pageTextNotContains($block_non_reusable->label());
    $assert_session->pageTextNotContains($block2_non_reusable->label());

    // Ensure the view's other display also only shows reusable blocks and still
    // filters on the 'info' field.
    $this->drupalGet('extra-view-display');
    $assert_session->statusCodeEquals('200');
    $assert_session->responseContains('view-id-block_content');
    $assert_session->pageTextNotContains($after_block1->label());
    $assert_session->pageTextContains($after_block2->label());
    $assert_session->pageTextNotContains($block_non_reusable->label());
    $assert_session->pageTextNotContains($block2_non_reusable->label());

    // Ensure the Custom Block listing without Views installed shows the only
    // reusable blocks.
    $this->drupalGet('admin/structure/block/block-content');
    $this->container->get('module_installer')->uninstall(['views_ui', 'views']);
    $this->drupalGet('admin/structure/block/block-content');
    $assert_session->statusCodeEquals('200');
    $assert_session->responseNotContains('view-id-block_content');
    $assert_session->pageTextContains($after_block1->label());
    $assert_session->pageTextContains($after_block2->label());
    $assert_session->pageTextNotContains($block_non_reusable->label());
    $assert_session->pageTextNotContains($block2_non_reusable->label());

    $this->drupalGet('block/' . $after_block1->id());
    $assert_session->statusCodeEquals('200');

    // Ensure the non-reusable block is not accessible in the form.
    $this->drupalGet('block/' . $block_non_reusable->id());
    $assert_session->statusCodeEquals('403');

    $this->drupalLogout();

    $this->drupalLogin($this->createUser([
      'access user profiles',
      'administer blocks',
    ]));
    $this->drupalGet('block/' . $after_block1->id());
    $assert_session->statusCodeEquals('200');

    $this->drupalGet('block/' . $block_non_reusable->id());
    $assert_session->statusCodeEquals('403');
  }

}
