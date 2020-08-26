<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\jsonapi\ResourceType\ResourceType;

/**
 * Converts the Drupal config entity object to a JSON:API array structure.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
final class ConfigEntityDenormalizer extends EntityDenormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = ConfigEntityInterface::class;

  /**
   * {@inheritdoc}
   */
  protected function prepareInput(array $data, ResourceType $resource_type, $format, array $context) {
    $prepared = [];
    foreach ($data as $key => $value) {
      $prepared[$resource_type->getInternalName($key)] = $value;
    }
    return $prepared;
  }

}
