<?php

declare(strict_types=1);

namespace Drupal\nyan_cat;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Theme\ThemeEngineInterface;

// cspell:ignore nyan nyancat

/**
 * NYAN NYAN NYAN!
 *
 * If you don't understand yet what nyan cat is, look at
 * https://www.youtube.com/watch?v=IAISUDbjXj0
 */
class NyanCatThemeEngine implements ThemeEngineInterface {

  /**
   * {@inheritdoc}
   */
  public function theme(array $existing, string $type, string $theme, string $path): ?array {
    return drupal_find_theme_templates($existing, '.nyan-cat.html', $path);
  }

  /**
   * {@inheritdoc}
   */
  public function renderTemplate(string $template_file, array $variables): string|MarkupInterface {
    $output = str_replace('div', 'nyancat', file_get_contents(\Drupal::root() . '/' . $template_file . '.nyan-cat.html'));
    foreach ($variables as $key => $variable) {
      if (str_contains($output, '9' . $key)) {
        $output = str_replace('9' . $key, Html::escape($variable), $output);
      }
    }
    return $output;
  }

}
