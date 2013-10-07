<?php

/**
 * @file
 * Contains \Drupal\Core\Breadcrumb\BreadcrumbBuilderBase.
 */

namespace Drupal\Core\Breadcrumb;

/**
 * Defines a common base class for breadcrumb builders adding a link generator.
 *
 * @todo Use traits once we have a PHP 5.4 requirement.
 */
abstract class BreadcrumbBuilderBase implements BreadcrumbBuilderInterface {

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

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

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translationManager()->translate($string, $args, $options);
  }

  /**
   * Returns the translation manager.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   *   The translation manager.
   */
  protected function translationManager() {
    if (!$this->translationManager) {
      $this->translationManager = $this->container()->get('string_translation');
    }
    return $this->translationManager;
  }

}
