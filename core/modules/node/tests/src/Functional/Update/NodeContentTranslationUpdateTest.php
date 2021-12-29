<?php

namespace Drupal\Tests\node\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests updating to Drupal 9 with content-translation for author fields.
 *
 * @group node
 * @group legacy
 */
class NodeContentTranslationUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      dirname(__DIR__, 3) . '/fixtures/update/drupal8-9-1-bare.minimal-content-translation.php.gz',
    ];
  }

  /**
   * Tests that content-translation base field overrides are updated.
   *
   * @see node_post_update_modify_base_field_author_override
   */
  public function testContentTranslationDefaultValueBaseFieldOverrideUpdates() {
    $config = \Drupal::config('core.base_field_override.node.article.uid');

    $this->assertEquals('Drupal\node\Entity\Node::getCurrentUserId', $config->get('default_value_callback'));

    $this->runUpdates();

    $entity_field_manager = \Drupal::service('entity_field.manager');
    $fields = $entity_field_manager->getFieldDefinitions('node', 'article');
    $author = $fields['uid'];
    $this->assertEquals('Drupal\node\Entity\Node::getDefaultEntityOwner', $author->getDefaultValueCallback());
  }

}
