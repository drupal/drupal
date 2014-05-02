<?php

/**
 * @file
 * Contains \Drupal\Core\Breadcrumb\BreadcrumbBuilderBase.
 */

namespace Drupal\Core\Breadcrumb;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a common base class for breadcrumb builders adding a link generator.
 *
 * @todo Use traits once we have a PHP 5.4 requirement.
 */
abstract class BreadcrumbBuilderBase implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * Returns the service container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   */
  protected function container() {
    return \Drupal::getContainer();
  }

  /**
   * Renders a link to a route given a route name and its parameters.
   *
   * @see \Drupal\Core\Utility\LinkGeneratorInterface::generate() for details
   *   on the arguments, usage, and possible exceptions.
   *
   * @return string
   *   An HTML string containing a link to the given route and parameters.
   */
  protected function l($text, $route_name, array $parameters = array(), array $options = array()) {
    return $this->linkGenerator()->generate($text, $route_name, $parameters, $options);
  }

  /**
   * Returns the link generator.
   *
   * @return \Drupal\Core\Utility\LinkGeneratorInterface
   *   The link generator
   */
  protected function linkGenerator() {
    if (!isset($this->linkGenerator)) {
      $this->linkGenerator = $this->container()->get('link_generator');
    }
    return $this->linkGenerator;
  }

}
