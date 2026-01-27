<?php

declare(strict_types=1);

namespace Drupal\image_test\Plugin\ImageToolkit\Operation\test;

use Drupal\Core\ImageToolkit\Attribute\ImageToolkitOperation;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Builds an image toolkit operation.
 */
#[ImageToolkitOperation(
  id: "test_autowiring",
  toolkit: "test",
  operation: "autowiring",
  label: new TranslatableMarkup("Autowiring operation"),
  description: new TranslatableMarkup("Autowiring operation."),
)]
class Autowiring extends OperationBase {

  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    #[Autowire(service: 'logger.channel.image')]
    LoggerInterface $logger,
    #[Autowire(service: 'messenger')]
    protected $messenger,
    protected StateInterface $state,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array $arguments) {
    $this->state->set('image_test.autowiring_operation', 'foo');
    return TRUE;
  }

}
