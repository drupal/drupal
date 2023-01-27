<?php

namespace Drupal\Core\Template\Loader;

use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * Loads string templates, also known as inline templates.
 *
 * This loader is intended to be used in a Twig loader chain and only loads
 * string templates that begin with the following comment:
 * @code
 * {# inline_template_start #}
 * @endcode
 *
 * This class override ensures that the string loader behaves as expected in
 * the loader chain. If Twig's string loader is used as is, any string (even a
 * reference to a file-based Twig template) is treated as a valid template and
 * is rendered instead of a \Twig\Error\LoaderError exception being thrown.
 *
 * @see \Drupal\Core\Template\TwigEnvironment::renderInline()
 * @see \Drupal\Core\Render\Element\InlineTemplate
 * @see twig_render_template()
 */
class StringLoader implements LoaderInterface {

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    if (str_starts_with($name, '{# inline_template_start #}')) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheKey(string $name): string {
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function isFresh(string $name, int $time): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceContext(string $name): Source {
    $name = (string) $name;
    return new Source($name, $name);
  }

}
