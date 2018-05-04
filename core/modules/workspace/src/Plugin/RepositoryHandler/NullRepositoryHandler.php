<?php

namespace Drupal\workspace\Plugin\RepositoryHandler;

use Drupal\Core\Plugin\PluginBase;
use Drupal\workspace\RepositoryHandlerInterface;

/**
 * Defines a fallback repository handler plugin.
 *
 * @RepositoryHandler(
 *   id = "null",
 *   label = @Translation("Missing repository handler"),
 *   description = @Translation("Provides a fallback for missing repository handlers. Do not use."),
 * )
 */
class NullRepositoryHandler extends PluginBase implements RepositoryHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function push() {
    // Nothing to do here.
  }

  /**
   * {@inheritdoc}
   */
  public function pull() {
    // Nothing to do here.
  }

  /**
   * {@inheritdoc}
   */
  public function checkConflictsOnTarget() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDifferringRevisionIdsOnTarget() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDifferringRevisionIdsOnSource() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberOfChangesOnTarget() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberOfChangesOnSource() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->getPluginDefinition()['description'];
  }

}
