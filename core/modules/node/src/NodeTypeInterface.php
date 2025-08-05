<?php

namespace Drupal\node;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface defining a node type entity.
 */
interface NodeTypeInterface extends ConfigEntityInterface, RevisionableEntityBundleInterface {

  /**
   * Determines whether the node type is locked.
   *
   * @return string|false
   *   The module name that locks the type or FALSE.
   */
  public function isLocked();

  /**
   * Sets whether a new revision should be created by default.
   *
   * @param bool $new_revision
   *   TRUE if a new revision should be created by default.
   */
  public function setNewRevision($new_revision);

  /**
   * Gets whether 'Submitted by' information should be shown.
   *
   * @return bool
   *   TRUE if the submitted by information should be shown.
   */
  public function displaySubmitted();

  /**
   * Sets whether 'Submitted by' information should be shown.
   *
   * @param bool $display_submitted
   *   TRUE if the submitted by information should be shown.
   */
  public function setDisplaySubmitted($display_submitted);

  /**
   * Gets the preview mode.
   *
   * phpcs:disable Drupal.Commenting
   * @todo Uncomment new method parameters before drupal:12.0.0.
   * @see https://www.drupal.org/project/drupal/issues/3539662
   *
   * @param bool $returnAsInt
   *   (deprecated) Whether to return an integer or enum value. The $returnAsInt
   *   parameter is deprecated in drupal:11.3.0 and is removed from
   *   drupal:13.0.0.
   * phpcs:enable
   *
   * @return \Drupal\node\NodePreviewMode|int
   *   Returns the enum case if $returnAsInt is FALSE, otherwise returns the
   *   integer equivalent.
   */
  public function getPreviewMode(/* bool $returnAsInt = TRUE  */);

  /**
   * Sets the preview mode.
   *
   * @param \Drupal\node\NodePreviewMode|int $preview_mode
   *   A NodePreviewMode case, or the integer equivalent. Passing an integer
   *   is deprecated.
   *
   * @todo Uncomment parameters type declarations before drupal:12.0.0.
   * @see https://www.drupal.org/project/drupal/issues/3539662
   */
  public function setPreviewMode(/* \Drupal\node\NodePreviewMode|int */ $preview_mode);

  /**
   * Gets the help information.
   *
   * @return string
   *   The help information of this node type.
   */
  public function getHelp();

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this node type.
   */
  public function getDescription();

}
