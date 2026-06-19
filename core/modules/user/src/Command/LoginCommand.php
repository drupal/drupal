<?php

declare(strict_types=1);

namespace Drupal\user\Command;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\OneTimeAuthentication;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate a one-time login link.
 *
 * @internal
 */
#[AsCommand(
  name: 'user:login',
  description: 'Generate a one-time login link.',
  aliases: [
    'uli',
  ],
)]
class LoginCommand {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly OneTimeAuthentication $oneTimeAuthentication,
  ) {}

  /**
   * Generate a one-time login link.
   */
  public function __invoke(
    OutputInterface $output,
    #[Argument('Optional path to redirect to after logging in.')]
    ?string $path,
    #[Option('A user name to log in as.')]
    ?string $name = NULL,
    #[Option('A user ID to log in as.')]
    ?string $uid = NULL,
    #[Option('A user email to log in as.')]
    ?string $mail = NULL,
  ): int {
    $account = NULL;
    $storage = $this->entityTypeManager->getStorage('user');
    $candidates = [
      'name' => $name,
      'uid' => $uid,
      'mail' => $mail,
    ];
    foreach ($candidates as $property => $value) {
      if ($value) {
        if (!$account = $storage->loadByProperties([$property => $value])) {
          throw new \InvalidArgumentException(sprintf('Unable to load user by %s: %s', $property, $value));
        }
        $account = reset($account);
        break;
      }
    }

    if (empty($account)) {
      $account = $storage->load(1);
    }

    if ($account->isBlocked()) {
      throw new \InvalidArgumentException(sprintf('Account %s is blocked and thus cannot login.', $account->getAccountName()));
    }

    $options = ['query' => $path ? ['destination' => $path] : []];
    $url = $this->oneTimeAuthentication
      ->generateOneTimeLoginUrl($account, $options, immediate: TRUE)
      ->mergeOptions($options);
    $output->writeln($url->toString());
    return Command::SUCCESS;
  }

}
