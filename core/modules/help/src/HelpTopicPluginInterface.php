<?php

namespace Drupal\help;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Defines an interface for help topic plugin classes.
 *
 * @see \Drupal\help\HelpTopicPluginManager
 */
interface HelpTopicPluginInterface extends PluginInspectionInterface, DerivativeInspectionInterface, CacheableDependencyInterface {

  /**
   * Returns the label of the topic.
   *
   * @return string
   *   The label of the topic.
   */
  public function getLabel();

  /**
   * Returns the body of the topic.
   *
   * @return array
   *   A render array representing the body.
   */
  public function getBody();

  /**
   * Returns whether this is a top-level topic or not.
   *
   * @return bool
   *   TRUE if this is a topic that should be displayed on the Help topics
   *   list; FALSE if not.
   */
  public function isTopLevel();

  /**
   * Returns the IDs of related topics.
   *
   * @return string[]
   *   Array of the IDs of related topics.
   */
  public function getRelated();

  /**
   * Returns the URL for viewing the help topic.
   *
   * @param array $options
   *   (optional) See
   *   \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute() for the
   *    available options.
   *
   * @return \Drupal\Core\Url
   *   A URL object containing the URL for viewing the help topic.
   */
  public function toUrl(array $options = []);

  /**
   * Returns a link for viewing the help topic.
   *
   * @param string|null $text
   *   (optional) Link text to use for the link. If NULL, defaults to the
   *   topic title.
   * @param array $options
   *   (optional) See
   *   \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute() for the
   *    available options.
   *
   * @return \Drupal\Core\Link
   *   A link object for viewing the topic.
   */
  public function toLink($text = NULL, array $options = []);

}
