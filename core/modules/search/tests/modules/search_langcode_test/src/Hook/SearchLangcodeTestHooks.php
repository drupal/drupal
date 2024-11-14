<?php

declare(strict_types=1);

namespace Drupal\search_langcode_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for search_langcode_test.
 */
class SearchLangcodeTestHooks {

  /**
   * Implements hook_search_preprocess().
   */
  #[Hook('search_preprocess')]
  public function searchPreprocess($text, $langcode = NULL) {
    if (isset($langcode) && $langcode == 'en') {
      // Add the alternate verb forms for the word "testing".
      if ($text == 'we are testing') {
        $text .= ' test tested';
      }
      else {
        \Drupal::messenger()->addStatus('Langcode Preprocess Test: ' . $langcode);
        $text .= 'Additional text';
      }
    }
    elseif (isset($langcode)) {
      \Drupal::messenger()->addStatus('Langcode Preprocess Test: ' . $langcode);
      // Preprocessing for the excerpt test.
      if ($langcode == 'ex') {
        $text = str_replace('finding', 'find', $text);
        $text = str_replace('finds', 'find', $text);
        $text = str_replace('dic', ' dependency injection container', $text);
        $text = str_replace('hypertext markup language', 'html', $text);
      }
    }
    return $text;
  }

}
