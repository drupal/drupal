<?php

namespace Drupal\content_moderation\ParamConverter;

use Drupal\Core\ParamConverter\EntityConverter;

/**
 * Defines a class for making sure the edit-route loads the current draft.
 *
 * @internal
 *   This class only exists to provide backwards compatibility with the
 *   load_pending_revision flag, the predecessor to load_latest_revision. The
 *   core entity converter now natively loads the latest revision of an entity
 *   when the load_latest_revision flag is present. This flag is also added
 *   automatically to all entity forms.
 */
class EntityRevisionConverter extends EntityConverter {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if (!empty($definition['load_pending_revision'])) {
      @trigger_error('The load_pending_revision flag has been deprecated. You should use load_latest_revision instead.', E_USER_DEPRECATED);
      $definition['load_latest_revision'] = TRUE;
    }
    return parent::convert($value, $definition, $name, $defaults);
  }

}
