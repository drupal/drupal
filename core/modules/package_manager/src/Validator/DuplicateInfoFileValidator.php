<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\PathLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Validates the stage does not have duplicate info.yml not present in active.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class DuplicateInfoFileValidator implements EventSubscriberInterface {

  use StringTranslationTrait;

  public function __construct(private readonly PathLocator $pathLocator) {
  }

  /**
   * Validates the stage does not have duplicate info.yml not present in active.
   */
  public function validate(PreApplyEvent $event): void {
    $active_dir = $this->pathLocator->getProjectRoot();
    $stage_dir = $event->sandboxManager->getSandboxDirectory();
    $active_info_files = $this->findInfoFiles($active_dir);
    $stage_info_files = $this->findInfoFiles($stage_dir);

    foreach ($stage_info_files as $stage_info_file => $stage_info_count) {
      if (isset($active_info_files[$stage_info_file])) {
        // Check if stage directory has more info.yml files matching
        // $stage_info_file than in the active directory.
        if ($stage_info_count > $active_info_files[$stage_info_file]) {
          $event->addError([
            $this->t('The stage directory has @stage_count instances of @stage_info_file as compared to @active_count in the active directory. This likely indicates that a duplicate extension was installed.', [
              '@stage_info_file' => $stage_info_file,
              '@stage_count' => $stage_info_count,
              '@active_count' => $active_info_files[$stage_info_file],
            ]),
          ]);
        }
      }
      // Check if stage directory has two or more info.yml files matching
      // $stage_info_file which are not in active directory.
      elseif ($stage_info_count > 1) {
        $event->addError([
          $this->t('The stage directory has @stage_count instances of @stage_info_file. This likely indicates that a duplicate extension was installed.', [
            '@stage_info_file' => $stage_info_file,
            '@stage_count' => $stage_info_count,
          ]),
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreApplyEvent::class => 'validate',
    ];
  }

  /**
   * Recursively finds info.yml files in a directory.
   *
   * @param string $dir
   *   The path of the directory to check.
   *
   * @return int[]
   *   Array of count of info.yml files in the directory keyed by file name.
   */
  private function findInfoFiles(string $dir): array {
    // Use the official extension discovery mechanism, but tweak it, because by
    // default it resolves duplicates.
    // @see \Drupal\Core\Extension\ExtensionDiscovery::process()
    $duplicate_aware_extension_discovery = new class($dir, FALSE, []) extends ExtensionDiscovery {

      /**
       * {@inheritdoc}
       */
      protected function process(array $all_files) {
        // Unlike parent implementation: no processing, to retain duplicates.
        return $all_files;
      }

    };

    // Scan all 4 extension types, explicitly ignoring tests.
    $extension_info_files = array_merge(
      array_keys($duplicate_aware_extension_discovery->scan('module', FALSE)),
      array_keys($duplicate_aware_extension_discovery->scan('theme', FALSE)),
      array_keys($duplicate_aware_extension_discovery->scan('profile', FALSE)),
      array_keys($duplicate_aware_extension_discovery->scan('theme_engine', FALSE)),
    );

    $info_files = [];
    foreach ($extension_info_files as $info_file) {
      $file_name = basename($info_file);
      $info_files[$file_name] = ($info_files[$file_name] ?? 0) + 1;
    }
    return $info_files;
  }

}
