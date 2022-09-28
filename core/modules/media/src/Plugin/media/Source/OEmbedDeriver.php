<?php

namespace Drupal\media\Plugin\media\Source;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Derives media source plugin definitions for supported oEmbed providers.
 *
 * @internal
 *   This is an internal part of the oEmbed system and should only be used by
 *   oEmbed-related code in Drupal core.
 */
class OEmbedDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [
      'video' => [
        'id' => 'video',
        'label' => $this->t('Remote video'),
        'description' => $this->t('Use remote video URL for reusable media.'),
        'providers' => ['YouTube', 'Vimeo'],
        'default_thumbnail_filename' => 'video.png',
      ] + $base_plugin_definition,
    ];
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
