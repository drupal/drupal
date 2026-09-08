<?php

declare(strict_types=1);

namespace Drupal\system\Command;

use Drupal\Core\Command\Exception\UserAbortException;
use Drupal\Core\Extension\Exception\ObsoleteExtensionException;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\PermissionHandlerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Installs modules or themes, resolving and confirming their dependencies.
 *
 * @internal
 */
#[AsCommand(
  name: 'ex:install',
  description: 'Installs one or more modules or themes.',
  aliases: ['exin', 'pm:install', 'pm:enable', 'enable', 'en'],
  usages: ['views,views_ui', 'claro']
)]
class ExtensionInstallCommand {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ModuleInstallerInterface $moduleInstaller,
    protected readonly ThemeInstallerInterface $themeInstaller,
    protected readonly ModuleExtensionList $moduleExtensionList,
    protected readonly ThemeExtensionList $themeExtensionList,
    protected readonly MessengerInterface $messenger,
  ) {}

  /**
   * Installs one or more modules or themes.
   */
  public function __invoke(
    InputInterface $input,
    OutputInterface $output,
    #[Argument(description: 'A comma-separated list of module or theme machine names to install.')]
    string $extensions,
    #[Option(description: 'Proceed without prompting for confirmation when additional dependencies will be installed.', shortcut: 'y')]
    bool $yes = FALSE,
    #[Option(description: 'Show the operations that would be performed without installing anything.')]
    bool $dryRun = FALSE,
  ): int {
    $io = new SymfonyStyle($input, $output);

    // Parse the comma-delimited list of extension machine names, dropping any
    // empty entries left by stray or trailing commas.
    $requested = array_values(array_filter(explode(',', $extensions)));
    if (empty($requested)) {
      $io->error((string) $this->t('No modules or themes were specified.'));
      return Command::INVALID;
    }

    // Classify each requested extension as a module or a theme. The extension
    // lists are reset so that any extensions added directly to the filesystem
    // are picked up.
    $module_data = $this->moduleExtensionList->reset()->getList();
    $theme_data = $this->themeExtensionList->reset()->getList();

    $modules = [];
    $themes = [];
    $unknown = [];
    foreach ($requested as $name) {
      if (isset($module_data[$name])) {
        $modules[] = $name;
      }
      elseif (isset($theme_data[$name])) {
        $themes[] = $name;
      }
      else {
        $unknown[] = $name;
      }
    }

    if (!empty($unknown)) {
      $io->error((string) $this->formatPlural(
        count($unknown),
        'The following could not be found as a module or theme: @names.',
        'The following could not be found as modules or themes: @names.',
        ['@names' => implode(', ', $unknown)],
      ));
      return Command::FAILURE;
    }

    // Installing modules and themes together would require interleaving two
    // different dependency-resolution and install processes (a theme may even
    // depend on a module being installed first). Keep each invocation to a
    // single extension type rather than guessing at the correct ordering.
    if (!empty($modules) && !empty($themes)) {
      $io->error((string) $this->t('Cannot install modules (@modules) and themes (@themes) in the same command. Run "ex:install" separately for each.', [
        '@modules' => implode(', ', $modules),
        '@themes' => implode(', ', $themes),
      ]));
      return Command::FAILURE;
    }

    $is_theme = !empty($themes);
    $type = $is_theme ? 'theme' : 'module';
    $names = $is_theme ? $themes : $modules;
    $data = $is_theme ? $theme_data : $module_data;

    // Evaluate the full set of extensions that will be installed, including
    // dependencies that are pulled in automatically. This validates the
    // request and throws on missing/incompatible/obsolete extensions.
    try {
      $resolved = $is_theme
        ? $this->themeInstaller->getThemesToInstall($names)
        : $this->moduleInstaller->getModulesToInstall($names);
    }
    catch (MissingDependencyException | ObsoleteExtensionException | UnknownExtensionException $e) {
      $io->error($e->getMessage());
      return Command::FAILURE;
    }

    if (empty($resolved)) {
      $io->success((string) $this->formatPlural(
        count($names),
        'Nothing to do: the requested @type is already installed.',
        'Nothing to do: the requested @types are already installed.',
        ['@type' => $type],
      ));
      return Command::SUCCESS;
    }

    // Split the resolved set into the extensions that were explicitly
    // requested and the additional dependencies that will be installed too.
    $additional = array_diff($resolved, $names);
    $verb = $dryRun ? $this->t('would be installed') : $this->t('will be installed');

    $requested_labels = [];
    $additional_labels = [];
    foreach ($resolved as $machine_name) {
      $label = sprintf('%s (%s)', $data[$machine_name]->info['name'], $machine_name);
      if (in_array($machine_name, $names, TRUE)) {
        $requested_labels[] = $label;
      }
      else {
        $additional_labels[] = $label;
      }
    }

    $io->writeln((string) $this->formatPlural(
      count($requested_labels),
      'The following @type @verb:',
      'The following @types @verb:',
      ['@type' => $type, '@verb' => $verb],
    ));
    $io->listing($requested_labels);

    if ($additional_labels) {
      $io->writeln((string) $this->formatPlural(
        count($additional_labels),
        'The following additional @type @verb as a dependency:',
        'The following additional @types @verb as dependencies:',
        ['@type' => $type, '@verb' => $verb],
      ));
      $io->listing($additional_labels);
    }

    // Perform the same requirements checks as the modules administration form.
    // Themes mirror the current behavior and rely on the dependency checks
    // already performed by the theme installer.
    if (!$is_theme && !$this->checkRequirements($resolved, $io)) {
      return Command::FAILURE;
    }

    if ($dryRun) {
      $io->note((string) $this->t('Dry run: no changes were made.'));
      return Command::SUCCESS;
    }

    // Prompt before pulling in dependencies the user did not request. In
    // non-interactive mode there is nobody to answer the prompt, so require
    // --yes to be explicit rather than silently installing the dependencies.
    if (!empty($additional) && !$yes) {
      if (!$input->isInteractive()) {
        throw new UserAbortException(sprintf('Installing the requested %s(s) requires additional dependencies. Re-run with --yes to install them.', $type));
      }
      $question = (string) $this->formatPlural(
        count($additional),
        'Install @count additional @type as a dependency?',
        'Install @count additional @types as dependencies?',
        ['@type' => $type],
      );
      if (!$io->confirm($question, TRUE)) {
        throw new UserAbortException();
      }
    }

    try {
      if ($is_theme) {
        $this->themeInstaller->install($names);
      }
      else {
        $this->moduleInstaller->install($names);
      }
    }
    catch (\Exception $e) {
      $io->error($e->getMessage());
      return Command::FAILURE;
    }

    $items = [];
    foreach ($resolved as $machine_name) {
      $label = sprintf('%s (%s)', $data[$machine_name]->info['name'], $machine_name);
      $links = $this->getExtensionLinks($machine_name, $data[$machine_name]);
      if ($links) {
        $label .= ' — ' . implode(', ', $links);
      }
      $items[] = $label;
    }

    $io->success((string) $this->formatPlural(
      count($resolved),
      'Successfully installed @count @type.',
      'Successfully installed @count @types.',
      ['@type' => $type],
    ));
    $io->listing($items);
    return Command::SUCCESS;
  }

  /**
   * Returns console-formatted hyperlinks for an extension's admin pages.
   *
   * @return string[]
   *   Zero or more strings in the Symfony Console hyperlink format
   *   (<href=URL>label</>).
   */
  private function getExtensionLinks(string $machine_name, Extension $extension): array {
    $links = [];
    // Fetch lazily: ModuleInstaller::install() rebuilds the container, and
    // resetImplementations() does not clear hookLists, so the injected handler
    // retains a stale hook list that misses newly installed modules.
    $module_handler = \Drupal::moduleHandler();
    if ($module_handler->moduleExists('help') && $module_handler->hasImplementations('help', $machine_name)) {
      $url = Url::fromRoute('help.page', ['name' => $machine_name])->setAbsolute()->toString();
      $links[] = sprintf('<href=%s>%s</>', $url, $this->t('Help'));
    }

    // Fetched lazily rather than via constructor injection because
    // ModuleInstaller::install() triggers a container rebuild, which would
    // leave an injected instance stale.
    if (\Drupal::hasService(PermissionHandlerInterface::class)
        && \Drupal::service(PermissionHandlerInterface::class)->moduleProvidesPermissions($machine_name)) {
      $url = Url::fromRoute('user.admin_permissions.module', ['modules' => $machine_name])->setAbsolute()->toString();
      $links[] = sprintf('<href=%s>%s</>', $url, $this->t('Permissions'));
    }

    if (isset($extension->info['configure'])) {
      $params = $extension->info['configure_parameters'] ?? [];
      $url = Url::fromRoute($extension->info['configure'], $params)->setAbsolute()->toString();
      $links[] = sprintf('<href=%s>%s</>', $url, $this->t('Configure'));
    }

    return $links;
  }

  /**
   * Runs hook_requirements('install') for the given modules.
   *
   * @param string[] $modules
   *   The module machine names to check.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   The output style used to report any failures.
   *
   * @return bool
   *   TRUE if all modules meet their installation requirements.
   */
  protected function checkRequirements(array $modules, SymfonyStyle $io): bool {
    // Make sure the install API is available.
    include_once DRUPAL_ROOT . '/core/includes/install.inc';

    $failed = [];
    foreach ($modules as $module) {
      if (!drupal_check_module($module)) {
        $failed[] = $module;
      }
    }

    // drupal_check_module() reports the specific failures via the messenger;
    // surface them on the console and clear them so they are not rendered
    // again on a subsequent request.
    foreach ($this->messenger->messagesByType(MessengerInterface::TYPE_ERROR) as $message) {
      $io->error((string) $message);
    }
    $this->messenger->deleteByType(MessengerInterface::TYPE_ERROR);

    if (!empty($failed)) {
      $io->error((string) $this->t('The following modules did not meet their installation requirements: @modules.', ['@modules' => implode(', ', $failed)]));
      return FALSE;
    }
    return TRUE;
  }

}
