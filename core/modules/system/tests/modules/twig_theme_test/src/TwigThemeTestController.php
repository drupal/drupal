<?php

namespace Drupal\twig_theme_test;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Controller routines for Twig theme test routes.
 */
class TwigThemeTestController {

  /**
   * Menu callback for testing PHP variables in a Twig template.
   */
  public function phpVariablesRender() {
    return ['#markup' => \Drupal::theme()->render('twig_theme_test_php_variables', [])];
  }

  /**
   * Menu callback for testing translation blocks in a Twig template.
   */
  public function transBlockRender() {
    return [
      '#theme' => 'twig_theme_test_trans',
    ];
  }

  /**
   * Controller for testing the twig placeholder filter outside of {% trans %}
   */
  public function placeholderOutsideTransRender() {
    return [
      '#theme' => 'twig_theme_test_placeholder_outside_trans',
      '#var' => '<script>alert(123);</script>',
    ];
  }

  /**
   * Renders for testing url_generator functions in a Twig template.
   */
  public function urlGeneratorRender() {
    return [
      '#theme' => 'twig_theme_test_url_generator',
    ];
  }

  /**
   * Renders for testing link_generator functions in a Twig template.
   */
  public function linkGeneratorRender() {
    return [
      '#theme' => 'twig_theme_test_link_generator',
      '#test_url' => new Url('user.register', [], ['absolute' => TRUE]),
      '#test_url_attribute' => new Url('user.register', [], ['attributes' => ['foo' => 'bar']]),
      // Explicitly creating an Attribute object to avoid false positives when
      // testing Attribute object merging with the twig link() function.
      '#attributes' => new Attribute(['class' => ['llama', 'kitten', 'panda']]),
    ];
  }

  /**
   * Renders a URL to a string.
   */
  public function urlToStringRender() {
    return [
      '#theme' => 'twig_theme_test_url_to_string',
      '#test_url' => Url::fromRoute('user.register'),
    ];
  }

  /**
   * Renders for testing file_url functions in a Twig template.
   */
  public function fileUrlRender() {
    return [
      '#theme' => 'twig_theme_test_file_url',
    ];
  }

  /**
   * Renders for testing attach_library functions in a Twig template.
   */
  public function attachLibraryRender() {
    return [
      '#theme' => 'twig_theme_test_attach_library',
    ];
  }

  /**
   * Menu callback for testing the Twig registry loader.
   */
  public function registryLoaderRender() {
    return ['#theme' => 'twig_registry_loader_test'];
  }

  /**
   * Controller for testing a renderable inside a template.
   */
  public function renderable() {
    return [
      '#theme' => 'twig_theme_test_renderable',
      '#renderable' => new ExampleRenderable(),
    ];
  }

  /**
   * Renders for testing the embed tag in a Twig template.
   */
  public function embedTagRender() {
    return ['#theme' => 'twig_theme_test_embed_tag'];
  }

}
