<?php

namespace Drupal\comment;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\DefaultCancellationHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an entity handler for comments to react to user account cancellation.
 */
class CancellationHandler extends DefaultCancellationHandler {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * CancellationHandler constructor.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\ContentEntityStorageInterface $storage
   *   The entity storage handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ContentEntityTypeInterface $entity_type, ContentEntityStorageInterface $storage, ConfigFactoryInterface $config_factory) {
    parent::__construct($entity_type, $storage);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function anonymize(ContentEntityInterface $entity): void {
    /** @var \Drupal\comment\CommentInterface $entity */
    parent::anonymize($entity);
    $entity->setAuthorName($this->configFactory->get('user.settings')->get('anonymous'));
  }

}
