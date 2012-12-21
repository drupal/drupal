<?php

/**
 * @file
 * Definition of Drupal\edit_test\Plugin\edit\processed_text_editor\TestProcessedEditor.
 */

namespace Drupal\edit_test\Plugin\edit\processed_text_editor;

use Drupal\edit\Plugin\ProcessedTextEditorBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a test processed text editor plugin.
 *
 * @Plugin(
 *   id = "test_processed_editor",
 *   title = @Translation("Test Processed Editor")
 * )
 */
class TestProcessedEditor extends ProcessedTextEditorBase {

  /**
   * Implements Drupal\edit\Plugin\ProcessedTextEditorInterface::checkFormatCompatibility().
   */
  function checkFormatCompatibility($format_id) {
    return state()->get('edit_test.compatible_format') == $format_id;
  }

}
