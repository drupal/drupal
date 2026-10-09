<?php

declare(strict_types=1);

namespace Drupal\Core\Extension\Command;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Command\OutputFormat;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Theme\ExtensionType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List extensions.
 *
 * @internal
 */
#[AsCommand(
  name: 'ex:list',
  description: 'List extensions.',
  aliases: [
    'pm:list',
    'pml',
  ],
  usages: [
    'ex:list --type=module --format=json',
    'ex:list --type=theme --format=yaml',
  ]
)]
class ExtensionListCommand {

  public function __construct(
    protected ModuleExtensionList $extensionListModule,
    protected ThemeExtensionList $extensionListTheme,
  ) {}

  /**
   * Invoke the command.
   */
  public function __invoke(
    InputInterface $input,
    OutputInterface $output,
    #[Option('Show extensions that belong to Drupal core.')]
    ?bool $core = NULL,
    #[Option('Filter extensions to modules or themes.')]
    ?ExtensionType $type = NULL,
    #[Option('A comma delimited list of packages to filter by.')]
    ?string $package = NULL,
    #[Option('Filter extensions by install status.')]
    ?bool $installed = NULL,
    #[Option('An alternative output format such as json or yaml. Defaults to table.')]
    ?OutputFormat $format = NULL,
  ): int {
    $rows = [];

    $modules = $this->extensionListModule->getList();
    $themes = $this->extensionListTheme->getList();
    $both = array_merge($modules, $themes);

    foreach ($both as $key => $extension) {
      // Populate any missing keys.
      $extension->info += ['package' => '', 'version' => ''];

      // Filter out test extensions.
      if (str_contains($extension->getPath(), 'tests')) {
        continue;
      }

      if ($type && $extension->getType() !== $type->value) {
        continue;
      }

      // Filter out uninstalled if --installed is specified.
      if ($installed === TRUE && $extension->status !== 1) {
        continue;
      }

      // Filter out installed if --no-installed is specified.
      if ($installed === FALSE && $extension->status == 1) {
        continue;
      }

      // Filter out core if --no-core specified.
      // @todo Add origin property to themes.
      // @see https://www.drupal.org/project/drupal/issues/3605703
      if ($core === FALSE && @$extension->origin == 'core') {
        continue;
      }

      // Filter out non-core if --core specified.
      if ($core === TRUE && $extension->origin !== 'core') {
        continue;
      }

      // Filter by package.
      if ($package && !in_array(strtolower($extension->info['package']), array_map('strtolower', explode(',', $package)))) {
        continue;
      }

      $row = [
        'package' => $extension->info['package'],
        'display_name' => $extension->info['name'] . ' (' . $extension->getName() . ')',
        'status' => ucfirst($extension->status ? 'installed' : 'uninstalled'),
        'version' => $extension->info['version'],
      ];
      $rows[$key] = $row;
    }

    $result = match ($format) {
      OutputFormat::Json => json_encode($rows, JSON_PRETTY_PRINT),
      OutputFormat::Yaml => Yaml::encode($rows),
      default => $this->renderTable($rows),
    };

    $output->writeln($result);

    return Command::SUCCESS;
  }

  /**
   * Render the table.
   */
  protected function renderTable(array $rows): string {
    $buffer = new BufferedOutput();
    $table = new Table($buffer);
    $table
      ->setHeaders(['Package', 'Name', 'Status', 'Version'])
      ->setRows($rows)
      ->render();
    return $buffer->fetch();
  }

}
