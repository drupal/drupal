<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\TopBarItem;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\navigation\Attribute\TopBarItem;
use Drupal\navigation\EntityRouteHelper;
use Drupal\navigation\TopBarItemBase;
use Drupal\navigation\TopBarRegion;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Page Context top bar item.
 */
#[TopBarItem(
  id: 'page_context',
  region: TopBarRegion::Context,
  label: new TranslatableMarkup('Page Context'),
)]
class PageContext extends TopBarItemBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new PageContext instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\navigation\EntityRouteHelper $entityRouteHelper
   *   The entity route helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private EntityTypeManagerInterface $entityTypeManager,
    private EntityRouteHelper $entityRouteHelper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
    $configuration,
    $plugin_id,
    $plugin_definition,
    $container->get(EntityTypeManagerInterface::class),
    $container->get(EntityRouteHelper::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [
      '#cache' => [
        'contexts' => ['route'],
      ],
    ];

    if (!$entity = $this->entityRouteHelper->getContentEntityFromRoute()) {
      return $build;
    }

    $build += [
      [
        '#type' => 'component',
        '#component' => 'navigation:title',
        '#props' => [
          'icon' => 'database',
          'html_tag' => 'span',
          'modifiers' => ['ellipsis', 'xs'],
          'extra_classes' => ['top-bar__title'],
        ],
        '#slots' => [
          'content' => $entity->label(),
        ],
      ],
    ];

    if ($label = $this->getBadgeLabel($entity)) {
      $build += [
        '#type' => 'component',
        '#component' => 'navigation:badge',
        '#props' => [
          'status' => $this->getBadgeStatus($entity) ?? 'info',
        ],
        '#slots' => [
          'label' => $label,
        ],
      ];
    }

    return $build;
  }

  /**
   * Retrieves the badge label for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which the label is being retrieved.
   *
   * @return string|null
   *   The translated status if available. NULL otherwise.
   *   The status if available. NULL otherwise.
   */
  protected function getBadgeLabel(EntityInterface $entity): ?string {
    if (!$entity instanceof EntityPublishedInterface) {
      return NULL;
    }
    return (string) ($entity->isPublished() ? $this->t('Published') : $this->t('Unpublished'));
  }

  /**
   * Retrieves the badge status for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which the status is being retrieved.
   *
   * @return string|null
   *   The badge status if available. NULL otherwise.
   */
  protected function getBadgeStatus(EntityInterface $entity): ?string {
    if (!$entity instanceof EntityPublishedInterface) {
      return NULL;
    }
    return $entity->isPublished() ? 'success' : 'info';
  }

}
