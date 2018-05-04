<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a base RepositoryHandler plugin implementation.
 *
 * @see \Drupal\workspace\RepositoryHandlerInterface
 * @see \Drupal\workspace\RepositoryHandlerManager
 * @see \Drupal\workspace\Annotation\RepositoryHandler
 * @see plugin_api
 */
abstract class RepositoryHandlerBase extends PluginBase implements RepositoryHandlerInterface {

  use DependencyTrait;

  /**
   * The source repository identifier.
   *
   * @var string
   */
  protected $source;

  /**
   * The target repository identifier.
   *
   * @var string
   */
  protected $target;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!isset($configuration['source'])) {
      throw new \InvalidArgumentException('Missing repository handler source configuration');
    }
    if (!isset($configuration['target'])) {
      throw new \InvalidArgumentException('Missing repository handler target configuration');
    }

    $this->source = $configuration['source'];
    $this->target = $configuration['target'];
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

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
