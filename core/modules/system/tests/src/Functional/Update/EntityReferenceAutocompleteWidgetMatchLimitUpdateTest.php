<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that the match_limit setting is added to entity_reference_autocomplete.
 *
 * @see system_post_update_entity_reference_autocomplete_match_limit()
 *
 * @group legacy
 */
class EntityReferenceAutocompleteWidgetMatchLimitUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that the match_limit setting is added to the config.
   *
   * @expectedDeprecation Any entity_reference_autocomplete component of an entity_form_display must have a match_limit setting. The field_tags field on the node.article.default form display is missing it. This BC layer will be removed before 9.0.0. See https://www.drupal.org/node/2863188
   * @expectedDeprecation Any entity_reference_autocomplete component of an entity_form_display must have a match_limit setting. The uid field on the node.article.default form display is missing it. This BC layer will be removed before 9.0.0. See https://www.drupal.org/node/2863188
   */
  public function testViewsPostUpdateEntityLinkUrl() {
    $display = EntityFormDisplay::load('node.article.default');
    $this->assertArrayNotHasKey('match_limit', $display->getComponent('field_tags')['settings']);
    $this->assertArrayNotHasKey('match_limit', $display->getComponent('uid')['settings']);

    $this->runUpdates();

    $display = EntityFormDisplay::load('node.article.default');
    $this->assertEquals(10, $display->getComponent('field_tags')['settings']['match_limit']);
    $this->assertEquals(10, $display->getComponent('uid')['settings']['match_limit']);
  }

}
