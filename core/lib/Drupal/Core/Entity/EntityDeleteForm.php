<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityConfirmFormBase.
 */

namespace Drupal\Core\Entity;

/**
 * Provides a generic base class for an entity deletion form.
 *
 * @ingroup entity_api
 */
class EntityDeleteForm extends EntityConfirmFormBase {

  use EntityDeleteFormTrait;

}
