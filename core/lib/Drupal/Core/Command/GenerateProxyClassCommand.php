<?php

namespace Drupal\Core\Command;

use Drupal\Component\ProxyBuilder\ProxyBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a console command to generate proxy classes.
 */
class GenerateProxyClassCommand extends Command {

  /**
   * The proxy builder.
   *
   * @var \Drupal\Component\ProxyBuilder\ProxyBuilder
   */
  protected $proxyBuilder;

  /**
   * Constructs a new GenerateProxyClassCommand instance.
   *
   * @param \Drupal\Component\ProxyBuilder\ProxyBuilder $proxy_builder
   *   The proxy builder.
   */
  public function __construct(ProxyBuilder $proxy_builder) {
    parent::__construct();

    $this->proxyBuilder = $proxy_builder;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('generate-proxy-class')
      ->setDefinition([
        new InputArgument('class_name', InputArgument::REQUIRED, 'The class to be proxied'),
        new InputArgument('namespace_root_path', InputArgument::REQUIRED, 'The filepath to the root of the namespace.'),
      ])
      ->setDescription('Dumps a generated proxy class into its appropriate namespace.')
      ->addUsage('\'Drupal\Core\Batch\BatchStorage\' "core/lib/Drupal/Core"')
      ->addUsage('\'Drupal\block\BlockRepository\' "core/modules/block/src"')
      ->addUsage('\'Drupal\mymodule\MyClass\' "modules/contrib/mymodule/src"');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $class_name = ltrim($input->getArgument('class_name'), '\\');
    $namespace_root = $input->getArgument('namespace_root_path');

    $match = [];
    preg_match('/([a-zA-Z0-9_]+\\\\[a-zA-Z0-9_]+)\\\\(.+)/', $class_name, $match);

    if ($match) {
      $root_namespace = $match[1];
      $rest_fqcn = $match[2];

      $proxy_filename = $namespace_root . '/ProxyClass/' . str_replace('\\', '/', $rest_fqcn) . '.php';
      $proxy_class_name = $root_namespace . '\\ProxyClass\\' . $rest_fqcn;

      $proxy_class_string = $this->proxyBuilder->build($class_name);

      $file_string = <<<EOF
<?php
// @codingStandardsIgnoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php '$class_name' "$namespace_root".
 */
{{ proxy_class_string }}
EOF;
      $file_string = str_replace(['{{ proxy_class_name }}', '{{ proxy_class_string }}'], [$proxy_class_name, $proxy_class_string], $file_string);

      mkdir(dirname($proxy_filename), 0775, TRUE);
      file_put_contents($proxy_filename, $file_string);

      $output->writeln(sprintf('Proxy of class %s written to %s', $class_name, $proxy_filename));
    }

  }

}
