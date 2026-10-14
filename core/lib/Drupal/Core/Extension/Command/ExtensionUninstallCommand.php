<?php

declare(strict_types=1);

namespace Drupal\Core\Extension\Command;

use Drupal\Core\Command\Exception\UserAbortException;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Uninstalls modules or themes, resolving and confirming their dependencies.
 *
 * @internal
 */
#[AsCommand(
  name: 'ex:uninstall',
  description: 'Uninstalls one or more modules or themes.',
  aliases: ['exun', 'pm:uninstall', 'pmu'],
  usages: ['views,views_ui', 'claro']
)]
class ExtensionUninstallCommand {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ModuleInstallerInterface $moduleInstaller,
    protected readonly ThemeInstallerInterface $themeInstaller,
    protected readonly ModuleExtensionList $moduleExtensionList,
    protected readonly ThemeExtensionList $themeExtensionList,
    protected readonly MessengerInterface $messenger,
  ) {}

  /**
   * Uninstalls one or more modules or themes.
   */
  public function __invoke(
    InputInterface $input,
    OutputInterface $output,
    #[Argument(description: 'A comma-separated list of module or theme machine names to uninstall.')]
    string $extensions,
    #[Option(description: 'Proceed without prompting for confirmation when additional dependencies will be uninstalled.', shortcut: 'y')]
    bool $yes = FALSE,
    #[Option(description: 'Show the operations that would be performed without uninstalling anything.')]
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
        continue;
      }
      if (isset($theme_data[$name])) {
        $themes[] = $name;
        continue;
      }
      $unknown[] = $name;
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

    // Keep the uninstall to a single extension type because it would require
    // combining two different dependency-resolution and uninstall processes
    // e.g. a theme may depend on a module being uninstalled first.
    if (!empty($modules) && !empty($themes)) {
      $io->error((string) $this->t('Cannot uninstall modules (@modules) and themes (@themes) in the same command. Run "ex:uninstall" separately for each.', [
        '@modules' => implode(', ', $modules),
        '@themes' => implode(', ', $themes),
      ]));
      return Command::FAILURE;
    }

    $is_theme = !empty($themes);
    $type = $is_theme ? 'theme' : 'module';
    $names = $is_theme ? $themes : $modules;
    $data = $is_theme ? $theme_data : $module_data;

    // Evaluate the full set of extensions that will be uninstalled, including
    // dependencies that are pulled in automatically. This validates the
    // request and throws on missing/incompatible/obsolete extensions.
    try {
      $resolved = $is_theme
        ? $this->themeInstaller->getThemesToUninstall($names)
        : $this->moduleInstaller->getModulesToUninstall($names);
    }
    catch (UnknownExtensionException | \InvalidArgumentException $e) {
      $io->error($e->getMessage());
      return Command::FAILURE;
    }

    if (empty($resolved)) {
      $io->success((string) $this->formatPlural(
        count($names),
        'Nothing to do: the requested @type is already uninstalled.',
        'Nothing to do: the requested @types are already uninstalled.',
        ['@type' => $type],
      ));
      return Command::SUCCESS;
    }

    // Split the resolved set into the extensions that were explicitly
    // requested and the additional dependencies that will be uninstalled too.
    $additional = array_diff($resolved, $names);
    $verb = $dryRun ? $this->t('would be uninstalled') : $this->t('will be uninstalled');

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

    // Perform the same checks as the modules uninstall form, so a module that
    // an uninstall validator blocks is reported before anything happens.
    if (!$is_theme && $reasons = $this->moduleInstaller->validateUninstall($resolved)) {
      foreach ($reasons as $machine_name => $module_reasons) {
        $io->error((string) $this->t('@module cannot be uninstalled: @reasons', [
          '@module' => sprintf('%s (%s)', $data[$machine_name]->info['name'], $machine_name),
          '@reasons' => implode('; ', $module_reasons),
        ]));
      }
      return Command::FAILURE;
    }

    if ($dryRun) {
      $io->note((string) $this->t('Dry run: no changes were made.'));
      return Command::SUCCESS;
    }

    // Prompt before pulling in dependencies the user did not request. In
    // non-interactive mode there is nobody to answer the prompt, so require
    // --yes to be explicit rather than silently uninstalling the dependencies.
    if (!empty($additional) && !$yes) {
      if (!$input->isInteractive()) {
        throw new UserAbortException(sprintf('Uninstalling the requested %s(s) requires additional dependencies. Re-run with --yes to uninstall them.', $type));
      }
      $question = (string) $this->formatPlural(
        count($additional),
        'Uninstall @count additional @type as a dependency?',
        'Uninstall @count additional @types as dependencies?',
        ['@type' => $type],
      );
      if (!$io->confirm($question, TRUE)) {
        throw new UserAbortException();
      }
    }

    try {
      if ($is_theme) {
        $this->themeInstaller->uninstall($resolved);
      }
      else {
        $this->moduleInstaller->uninstall($resolved);
      }
    }
    catch (\Exception $e) {
      $io->error($e->getMessage());
      return Command::FAILURE;
    }

    $io->success((string) $this->formatPlural(
      count($resolved),
      'Successfully uninstalled @count @type.',
      'Successfully uninstalled @count @types.',
      ['@type' => $type],
    ));
    return Command::SUCCESS;
  }

}
