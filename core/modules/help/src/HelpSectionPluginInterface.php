<?php

namespace Drupal\help;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Provides an interface for a plugin for a section of the /admin/help page.
 *
 * Plugins of this type need to be annotated with
 * \Drupal\help\Annotation\HelpSection annotation, and placed in the
 * Plugin\HelpSection namespace directory. They are managed by the
 * \Drupal\help\HelpSectionManager plugin manager class. There is a base
 * class that may be helpful:
 * \Drupal\help\Plugin\HelpSection\HelpSectionPluginBase.
 */
interface HelpSectionPluginInterface extends PluginInspectionInterface, CacheableDependencyInterface {


  /**
   * Returns the title of the help section.
   *
   * @return string
   *   The title text, which could be a plain string or an object that can be
   *   cast to a string.
   */
  public function getTitle();

  /**
   * Returns the description text for the help section.
   *
   * @return string
   *   The description text, which could be a plain string or an object that
   *   can be cast to a string.
   */
  public function getDescription();

  /**
   * Returns a list of topics to show in the help section.
   *
   * @return array
   *   A sorted list of topic links or render arrays for topic links. The links
   *   will be shown in the help section; if the returned array of links is
   *   empty, the section will be shown with some generic empty text.
   */
  public function listTopics();

}
