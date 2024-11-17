<?php

namespace Drupal\Core\Entity\Entity;

use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityDisplayModeBase;
use Drupal\Core\Entity\EntityViewModeInterface;

/**
 * Defines the entity view mode configuration entity class.
 *
 * View modes let entities be displayed differently depending on the context.
 * For instance, a node can be displayed differently on its own page ('full'
 * mode), on the home page or taxonomy listings ('teaser' mode), or in an RSS
 * feed ('rss' mode). Modules taking part in the display of the entity (notably
 * the Field API) can adjust their behavior depending on the requested view
 * mode. An additional 'default' view mode is available for all entity types.
 * This view mode is not intended for actual entity display, but holds default
 * display settings. For each available view mode, administrators can configure
 * whether it should use its own set of field display settings, or just
 * replicate the settings of the 'default' view mode, thus reducing the amount
 * of display configurations to keep track of.
 *
 * @see \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getAllViewModes()
 * @see \Drupal\Core\Entity\EntityDisplayRepositoryInterface::getViewModes()
 * @see hook_entity_view_mode_info_alter()
 */
#[ConfigEntityType(
  id: 'entity_view_mode',
  label: new TranslatableMarkup('View mode'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'description' => 'description',
  ],
  constraints: [
    'ImmutableProperties' => [
      'id',
      'targetEntityType',
    ],
  ],
  config_export: [
    'id',
    'label',
    'description',
    'targetEntityType',
    'cache',
  ],
  )]
class EntityViewMode extends EntityDisplayModeBase implements EntityViewModeInterface {

}
