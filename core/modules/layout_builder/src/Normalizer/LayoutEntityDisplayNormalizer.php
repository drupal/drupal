<?php

namespace Drupal\layout_builder\Normalizer;

use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\serialization\Normalizer\ConfigEntityNormalizer;

/**
 * Normalizes/denormalizes LayoutEntityDisplay objects into an array structure.
 *
 * @internal
 *   Tagged services are internal.
 */
class LayoutEntityDisplayNormalizer extends ConfigEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected static function getDataWithoutInternals(array $data) {
    $data = parent::getDataWithoutInternals($data);
    // Do not expose the actual layout sections in normalization.
    // @todo Determine what to expose here in
    //   https://www.drupal.org/node/2942975.
    unset($data['third_party_settings']['layout_builder']['sections']);
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      LayoutEntityDisplayInterface::class => TRUE,
    ];
  }

}
