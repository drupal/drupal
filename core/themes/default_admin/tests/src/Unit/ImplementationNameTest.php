<?php

declare(strict_types=1);

namespace Drupal\Tests\default_admin\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests that inherited implementation names have an explicit rationale.
 */
#[Group('default_admin')]
class ImplementationNameTest extends TestCase {

  /**
   * Tests that reviewed Default Admin prefixes are not retained.
   */
  public function testReviewedDefaultAdminPrefixes(): void {
    $theme_path = dirname(__DIR__, 3);
    $superseded_names = [
      'data-default-admin-accent',
      'data-default-admin-focus',
      'data-default-admin-layout-density',
      'data-default-admin-move-focus-to-end-of-form',
      'data-default-admin-move-focus-to-sticky-bar',
      'data-default-admin-sticky-form-selector',
      'data-default-admin-toolbar-escape-admin',
      'default-admin--classic-toolbar',
      'default-admin--core-navigation',
      'default-admin--dark-mode',
      'default-admin--edit-form',
      'default-admin--has-sticky-form-actions',
      'default-admin--high-contrast-mode',
      'default-admin--horizontal-toolbar',
      'default-admin--layout-form',
      'default-admin--navigation',
      'default-admin--sticky-bulk-select',
      'default-admin--sticky-table-header',
      'default-admin--toolbar',
      'default-admin--vertical-toolbar',
      'default-admin-autocomplete',
      'default-admin-back-to-admin',
      'default-admin-beta-flag',
      'default-admin-breadcrumb',
      'default-admin-confirm-form',
      'default-admin-custom-focus',
      'default-admin-details',
      'default-admin-experimental-flag',
      'default-admin-horizontal-scroll-shadow',
      'default-admin-layer-wrapper',
      'default-admin-layout-container',
      'default-admin-messages-dismiss',
      'default-admin-more-actions',
      'default-admin-new-flag',
      'default-admin-node-edit-form',
      'default-admin-pseudo-throbber',
      'default-admin-region-breadcrumb',
      'default-admin-secondary-toolbar',
      'default-admin-sidebar',
      'default-admin-sidebar-draggable',
      'default-admin-status',
      'default-admin-sticky-edit-',
      'default-admin-sticky-${',
      "default-admin-sticky-' .",
      'default-admin-sticky-form-actions',
      'default-admin-sticky-header',
      'default-admin-table-scroll-wrapper',
      'default-admin-throbber',
      'default-admin-toolbar',
      'defaultAdminAccent',
      'defaultAdminAutoCompete',
      'defaultAdminAutoComplete',
      'defaultAdminCoreNavigation',
      'defaultAdminDetails',
      'defaultAdminDropbutton',
      'defaultAdminEditForm',
      'defaultAdminEscapeAdmin',
      'defaultAdminFormActions',
      'defaultAdminMediaLibrary',
      'defaultAdminMessages',
      'defaultAdminMoreActionsToggle',
      'defaultAdminMoveFocusToStickyBar',
      'defaultAdminSettings',
      'defaultAdminSidebar',
      'defaultAdminSticky',
      'defaultAdminSyncActionButtons',
      'defaultAdminTableDrag',
      'defaultAdminTableHeader',
      'defaultAdminTableSelect',
      'defaultAdminTabledrag',
      'defaultAdminToolbarClickHandler',
      'defaultAdminToolbarKeyboardShortcut',
      'dropbutton--default-admin',
      'media-library-selection-info-default-admin-event',
    ];
    $extensions = ['css', 'js', 'php', 'twig'];
    $directory = new \RecursiveDirectoryIterator(
      $theme_path,
      \FilesystemIterator::SKIP_DOTS,
    );
    $files = new \RecursiveIteratorIterator($directory);
    $unexpected = [];

    foreach ($files as $file) {
      if (!$file->isFile() || !in_array($file->getExtension(), $extensions, TRUE)) {
        continue;
      }
      $relative_path = substr($file->getPathname(), strlen($theme_path) + 1);
      if ($relative_path === 'tests/src/Unit/ImplementationNameTest.php') {
        continue;
      }
      foreach (file($file->getPathname()) as $line_number => $line) {
        foreach ($superseded_names as $name) {
          if (str_contains($line, $name)) {
            $unexpected[] = sprintf(
              '%s:%d: %s',
              $relative_path,
              $line_number + 1,
              trim($line),
            );
            break;
          }
        }
      }
    }

    self::assertSame([], $unexpected, implode("\n", $unexpected));
  }

  /**
   * Tests source contents and filenames against the reviewed allowlist.
   */
  public function testImplementationNames(): void {
    $theme_path = dirname(__DIR__, 3);
    $allowlist_path = $theme_path
      . '/tests/fixtures/implementation-name-allowlist.json';
    $allowlist = json_decode(
      file_get_contents($allowlist_path),
      TRUE,
      flags: JSON_THROW_ON_ERROR,
    );
    $matched_allowlist_entries = [];
    $unexpected = [];
    $name_pattern = '/(?:\b(?:gin|Gin|claro|Claro)\b'
      . '|(?<![A-Za-z])(?:gin|claro)(?=[._-]|[A-Z])'
      . '|(?<![A-Za-z])(?:Gin|Claro)(?=[A-Z]))/';
    $extensions = ['css', 'js', 'json', 'md', 'php', 'svg', 'twig', 'yml'];
    $directory = new \RecursiveDirectoryIterator(
      $theme_path,
      \FilesystemIterator::SKIP_DOTS,
    );
    $files = new \RecursiveIteratorIterator($directory);
    $excluded = [
      'tests/fixtures/implementation-name-allowlist.json',
    ];

    foreach ($files as $file) {
      if (!$file->isFile()) {
        continue;
      }
      $relative_path = substr($file->getPathname(), strlen($theme_path) + 1);
      if (in_array($relative_path, $excluded, TRUE)) {
        continue;
      }
      if (preg_match('/(?:gin|claro)/i', $file->getFilename())) {
        $unexpected[] = $relative_path . ': inherited name in filename';
      }
      if (!in_array($file->getExtension(), $extensions, TRUE)) {
        continue;
      }
      foreach (file($file->getPathname()) as $line_number => $line) {
        if (!preg_match($name_pattern, $line)) {
          continue;
        }
        $allowed = FALSE;
        foreach ($allowlist as $index => $entry) {
          if ($entry['path'] === $relative_path
            && str_contains($line, $entry['pattern'])) {
            $matched_allowlist_entries[$index] = TRUE;
            $allowed = TRUE;
            break;
          }
        }
        if (!$allowed) {
          $unexpected[] = sprintf(
            '%s:%d: %s',
            $relative_path,
            $line_number + 1,
            trim($line),
          );
        }
      }
    }

    foreach ($allowlist as $index => $entry) {
      if (empty($matched_allowlist_entries[$index])) {
        $unexpected[] = sprintf(
          'Unused allowlist entry: %s: %s (%s)',
          $entry['path'],
          $entry['pattern'],
          $entry['reason'],
        );
      }
    }

    self::assertSame([], $unexpected, implode("\n", $unexpected));
  }

}
