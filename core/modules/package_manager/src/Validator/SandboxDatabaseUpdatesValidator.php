<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\Event\StatusCheckEvent;
use Drupal\package_manager\PathLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Flags a warning if there are database updates in a staged update.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
class SandboxDatabaseUpdatesValidator implements EventSubscriberInterface {

  use StringTranslationTrait;

  public function __construct(
    private readonly PathLocator $pathLocator,
    private readonly ModuleExtensionList $moduleList,
    private readonly ThemeExtensionList $themeList,
  ) {}

  /**
   * Checks that the staged update does not have changes to its install files.
   *
   * @param \Drupal\package_manager\Event\StatusCheckEvent $event
   *   The event object.
   */
  public function checkForStagedDatabaseUpdates(StatusCheckEvent $event): void {
    if (!$event->sandboxManager->sandboxDirectoryExists()) {
      return;
    }
    $stage_dir = $event->sandboxManager->getSandboxDirectory();
    $extensions_with_updates = $this->getExtensionsWithDatabaseUpdates($stage_dir);
    if ($extensions_with_updates) {
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $extensions_with_updates = array_map($this->t(...), $extensions_with_updates);
      $event->addWarning($extensions_with_updates, $this->t('Database updates have been detected in the following extensions.'));
    }
  }

  /**
   * Determines if a staged extension has changed update functions.
   *
   * @param string $stage_dir
   *   The path of the stage directory.
   * @param \Drupal\Core\Extension\Extension $extension
   *   The extension to check.
   *
   * @return bool
   *   TRUE if the staged copy of the extension has changed update functions
   *   compared to the active copy, FALSE otherwise.
   *
   * @todo In https://drupal.org/i/3253828 use a more sophisticated method to
   *   detect changes in the staged extension. Right now, we just compare hashes
   *   of the .install and .post_update.php files in both copies of the given
   *   extension, but this will cause false positives for changes to comments,
   *   whitespace, or runtime code like requirements checks. It would be
   *   preferable to use a static analyzer to detect new or changed functions
   *   that are actually executed during an update. No matter what, this method
   *   must NEVER cause false negatives, since that could result in code which
   *   is incompatible with the current database schema being copied to the
   *   active directory.
   */
  public function hasStagedUpdates(string $stage_dir, Extension $extension): bool {
    $active_dir = $this->pathLocator->getProjectRoot();

    $web_root = $this->pathLocator->getWebRoot();
    if ($web_root) {
      $active_dir .= DIRECTORY_SEPARATOR . $web_root;
      $stage_dir .= DIRECTORY_SEPARATOR . $web_root;
    }

    $active_functions = $this->getUpdateFunctions($active_dir, $extension);
    $staged_functions = $this->getUpdateFunctions($stage_dir, $extension);

    return (bool) array_diff($staged_functions, $active_functions);
  }

  /**
   * Returns a list of all update functions for a module.
   *
   * This method only exists because the API in core that scans for available
   * updates can only examine the active (running) code base, but we need to be
   * able to scan the staged code base as well to compare it against the active
   * one.
   *
   * @param string $root_dir
   *   The root directory of the Drupal code base.
   * @param \Drupal\Core\Extension\Extension $extension
   *   The module to check.
   *
   * @return string[]
   *   The names of the update functions in the module's .install and
   *   .post_update.php files.
   */
  private function getUpdateFunctions(string $root_dir, Extension $extension): array {
    $name = $extension->getName();

    $path = implode(DIRECTORY_SEPARATOR, [
      $root_dir,
      $extension->getPath(),
      $name,
    ]);
    $function_names = [];

    $patterns = [
      '.install' => '/^' . $name . '_update_[0-9]+$/i',
      '.post_update.php' => '/^' . $name . '_post_update_.+$/i',
    ];
    foreach ($patterns as $suffix => $pattern) {
      $file = $path . $suffix;

      if (!file_exists($file)) {
        continue;
      }
      // Parse the file and scan for named functions which match the pattern.
      $code = file_get_contents($file);
      $tokens = token_get_all($code);

      for ($i = 0; $i < count($tokens); $i++) {
        $chunk = array_slice($tokens, $i, 3);
        if ($this->tokensMatchFunctionNamePattern($chunk, $pattern)) {
          $function_names[] = $chunk[2][1];
        }
      }
    }
    return $function_names;
  }

  /**
   * Determines if a set of tokens contain a function name matching a pattern.
   *
   * @param array[] $tokens
   *   A set of three tokens, part of a stream returned by token_get_all().
   * @param string $pattern
   *   If the tokens declare a named function, a regular expression to test the
   *   function name against.
   *
   * @return bool
   *   TRUE if the given tokens declare a function whose name matches the given
   *   pattern; FALSE otherwise.
   *
   * @see token_get_all()
   */
  private function tokensMatchFunctionNamePattern(array $tokens, string $pattern): bool {
    if (count($tokens) !== 3 || !Inspector::assertAllStrictArrays($tokens)) {
      return FALSE;
    }
    // A named function declaration will always be a T_FUNCTION (the word
    // `function`), followed by T_WHITESPACE (or the code would be syntactically
    // invalid), followed by a T_STRING (the function name). This will ignore
    // anonymous functions, but match class methods (although class methods are
    // highly unlikely to match the naming patterns of update hooks).
    $names = array_map('token_name', array_column($tokens, 0));
    if ($names === ['T_FUNCTION', 'T_WHITESPACE', 'T_STRING']) {
      return (bool) preg_match($pattern, $tokens[2][1]);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      StatusCheckEvent::class => 'checkForStagedDatabaseUpdates',
    ];
  }

  /**
   * Gets extensions that have database updates in the stage directory.
   *
   * @param string $stage_dir
   *   The path of the stage directory.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The names of the extensions that have database updates.
   */
  public function getExtensionsWithDatabaseUpdates(string $stage_dir): array {
    $extensions_with_updates = [];
    // Check all installed extensions for database updates.
    $lists = [$this->moduleList, $this->themeList];
    foreach ($lists as $list) {
      foreach ($list->getAllInstalledInfo() as $name => $info) {
        if ($this->hasStagedUpdates($stage_dir, $list->get($name))) {
          $extensions_with_updates[] = $info['name'];
        }
      }
    }

    return $extensions_with_updates;
  }

}
