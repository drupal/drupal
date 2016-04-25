<?php

namespace Drupal\help\Plugin\HelpSection;

use Drupal\Core\Cache\UnchangingCacheableDependencyTrait;
use Drupal\Core\Plugin\PluginBase;
use Drupal\help\HelpSectionPluginInterface;

/**
 * Provides a base class for help section plugins.
 *
 * @see \Drupal\help\HelpSectionPluginInterface
 * @see \Drupal\help\Annotation\HelpSection
 * @see \Drupal\help\HelpSectionManager
 */
abstract class HelpSectionPluginBase extends PluginBase implements HelpSectionPluginInterface {

  use UnchangingCacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->getPluginDefinition()['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->getPluginDefinition()['description'];
  }

}
