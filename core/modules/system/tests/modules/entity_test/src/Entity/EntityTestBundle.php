<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Defines the Test entity bundle configuration entity.
 */
#[ConfigEntityType(
  id: 'entity_test_bundle',
  label: new TranslatableMarkup('Test entity bundle'),
  config_prefix: 'entity_test_bundle',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  handlers: [
    'access' => EntityAccessControlHandler::class,
    'form' => [
      'default' => BundleEntityFormBase::class,
    ],
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
    ],
  ],
  links: [
    'add-form' => '/entity_test_bundle/add',
  ],
  admin_permission: 'administer entity_test_bundle content',
  bundle_of: 'entity_test_with_bundle',
  config_export: [
    'id',
    'label',
    'description',
  ],
)]
class EntityTestBundle extends ConfigEntityBundleBase implements EntityDescriptionInterface {

  /**
   * The machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name.
   *
   * @var string
   */
  protected $label;

  /**
   * The description.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

}
