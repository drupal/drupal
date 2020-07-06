<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Url;

/**
 * Test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_external",
 *   label = @Translation("Entity test external"),
 *   base_table = "entity_test_external",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *   },
 *   links = {
 *     "canonical" = "/entity_test_external/{entity_test_external}"
 *   },
 * )
 */
class EntityTestExternal extends EntityTest {

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    if ($rel === 'canonical') {
      return Url::fromUri('http://example.com', $options);
    }
    return parent::toUrl($rel, $options);
  }

}
