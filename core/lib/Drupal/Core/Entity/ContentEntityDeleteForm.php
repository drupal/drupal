<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityDeleteForm.
 */

namespace Drupal\Core\Entity;

/**
 * Provides a generic base class for a content entity deletion form.
 */
class ContentEntityDeleteForm extends ContentEntityConfirmFormBase {

  use EntityDeleteFormTrait;

}
