<?php

namespace Drupal\TestSite\Commands;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Command to generate a login link for the test site.
 *
 * @internal
 */
class TestSiteUserLoginCommand extends Command {

  /**
   * The class loader to use for installation and initialization of setup.
   *
   * @var \Symfony\Component\Classloader\Classloader
   */
  protected $classLoader;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('user-login')
      ->setDescription('Generate a one time login link for an user.')
      ->addArgument('uid', InputArgument::REQUIRED, 'The ID of the user for whom the link will be generated')
      ->addOption('site-path', NULL, InputOption::VALUE_REQUIRED, 'The path for the test site.');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $root = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
    chdir($root);

    $this->classLoader = require 'autoload.php';
    $kernel = new DrupalKernel('prod', $this->classLoader, FALSE);
    $kernel::bootEnvironment();
    $kernel->setSitePath($input->getOption('site-path'));
    Settings::initialize($kernel->getAppRoot(), $kernel->getSitePath(), $this->classLoader);

    $request = Request::createFromGlobals();
    $kernel->prepareLegacyRequest($request);

    $kernel->boot();

    $container = $kernel->getContainer();
    $uid = $input->getArgument('uid');
    if (!is_numeric($uid)) {
      throw new InvalidArgumentException(sprintf('The "uid" argument needs to be an integer, but it is "%s".', $uid));
    }
    $userEntity = $container->get('entity_type.manager')
      ->getStorage('user')
      ->load($uid);
    $url = user_pass_reset_url($userEntity) . '/login';
    $output->writeln($url);
  }

}
