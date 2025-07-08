<?php

declare(strict_types=1);

namespace Drupal\path_test_misc\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;

/**
 * Hook implementations for path_test_misc.
 */
class PathTestMiscHooks {

  /**
   * Implements hook_ENTITY_TYPE_presave() for node entities.
   *
   * This is invoked from testAliasDuplicationPrevention.
   */
  #[Hook('node_presave')]
  public function nodePresave(NodeInterface $node): void {
    if ($node->getTitle() !== 'path duplication test') {
      return;
    }

    // Update the title to be able to check that this code ran.
    $node->setTitle('path duplication test ran');

    // Create a path alias that has the same values as the one in
    // PathItem::postSave.
    $path = \Drupal::entityTypeManager()->getStorage('path_alias')
      ->create([
        'path' => '/node/1',
        'alias' => '/my-alias',
        'langcode' => 'en',
      ]);
    $path->save();
  }

}
