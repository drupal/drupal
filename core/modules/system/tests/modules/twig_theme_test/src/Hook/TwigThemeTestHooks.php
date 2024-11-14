<?php

declare(strict_types=1);

namespace Drupal\twig_theme_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for twig_theme_test.
 */
class TwigThemeTestHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) : array {
    $items['twig_theme_test_filter'] = [
      'variables' => [
        'quote' => [],
        'attributes' => [],
      ],
      'template' => 'twig_theme_test.filter',
    ];
    $items['twig_theme_test_php_variables'] = ['template' => 'twig_theme_test.php_variables'];
    $items['twig_theme_test_trans'] = ['variables' => [], 'template' => 'twig_theme_test.trans'];
    $items['twig_theme_test_placeholder_outside_trans'] = [
      'variables' => [
        'var' => '',
      ],
      'template' => 'twig_theme_test.placeholder_outside_trans',
    ];
    $items['twig_namespace_test'] = ['variables' => [], 'template' => 'twig_namespace_test'];
    $items['twig_registry_loader_test'] = ['variables' => []];
    $items['twig_registry_loader_test_include'] = ['variables' => []];
    $items['twig_registry_loader_test_extend'] = ['variables' => []];
    $items['twig_raw_test'] = ['variables' => ['script' => '']];
    $items['twig_autoescape_test'] = ['variables' => ['script' => '']];
    $items['twig_theme_test_url_generator'] = ['variables' => [], 'template' => 'twig_theme_test.url_generator'];
    $items['twig_theme_test_link_generator'] = [
      'variables' => [
        'test_url' => NULL,
        'test_url_attribute' => NULL,
        'attributes' => [],
      ],
      'template' => 'twig_theme_test.link_generator',
    ];
    $items['twig_theme_test_url_to_string'] = [
      'variables' => [
        'test_url' => NULL,
      ],
      'template' => 'twig_theme_test.url_to_string',
    ];
    $items['twig_theme_test_file_url'] = ['variables' => [], 'template' => 'twig_theme_test.file_url'];
    $items['twig_theme_test_attach_library'] = ['variables' => [], 'template' => 'twig_theme_test.attach_library'];
    $items['twig_theme_test_renderable'] = [
      'variables' => [
        'renderable' => NULL,
      ],
      'template' => 'twig_theme_test.renderable',
    ];
    $items['twig_theme_test_embed_tag'] = ['variables' => [], 'template' => 'twig_theme_test.embed_tag'];
    $items['twig_theme_test_dump'] = ['variables' => [], 'template' => 'twig_theme_test.dump'];
    return $items;
  }

}
