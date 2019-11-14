<?php

namespace Drupal\content_moderation\Plugin\Action;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Alternate action plugin that can opt-out of modifying moderated entities.
 *
 * @deprecated in drupal:8.5.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\content_moderation\Plugin\Action\ModerationOptOutUnpublish
 *   instead.
 *
 * @see \Drupal\content_moderation\Plugin\Action\ModerationOptOutPublish
 * @see https://www.drupal.org/node/2919303
 */
class ModerationOptOutUnpublishNode extends ModerationOptOutUnpublish {

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModerationInformationInterface $moderation_info, EntityTypeBundleInfoInterface $bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $moderation_info, $bundle_info);
    @trigger_error(__NAMESPACE__ . '\ModerationOptOutUnpublishNode is deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.0. Use \Drupal\content_moderation\Plugin\Action\ModerationOptOutUnpublish instead. See https://www.drupal.org/node/2919303.', E_USER_DEPRECATED);
  }

}
