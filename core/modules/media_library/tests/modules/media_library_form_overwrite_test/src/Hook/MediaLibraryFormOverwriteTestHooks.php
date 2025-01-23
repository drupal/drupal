<?php

declare(strict_types=1);

namespace Drupal\media_library_form_overwrite_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\media_library_form_overwrite_test\Form\TestAddForm;

/**
 * Hook implementations for media_library_form_overwrite_test.
 */
class MediaLibraryFormOverwriteTestHooks {

  /**
   * Implements hook_media_source_info_alter().
   */
  #[Hook('media_source_info_alter')]
  public function mediaSourceInfoAlter(array &$sources): void {
    $sources['image']['forms']['media_library_add'] = TestAddForm::class;
  }

}
