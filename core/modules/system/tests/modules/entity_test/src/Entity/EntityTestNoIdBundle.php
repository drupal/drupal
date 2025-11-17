<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the entity_test_no_id bundle configuration entity.
 */
#[ConfigEntityType(
  id: 'entity_test_no_id_bundle',
  label: new TranslatableMarkup('Entity Test without id bundle'),
  config_prefix: 'entity_test_no_id_bundle',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  handlers: [
    'access' => EntityAccessControlHandler::class,
    'form' => [
      'default' => BundleEntityFormBase::class,
      'add' => BundleEntityFormBase::class,
      'edit' => BundleEntityFormBase::class,
    ],
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
    ],
  ],
  links: [
    'add-form' => '/admin/structure/entity_test_no_id_bundle/add',
    'edit-form' => '/admin/structure/entity_test_no_id_bundle/manage/{entity_test_no_id_bundle}',
    'collection' => '/admin/structure/entity_test_no_id_bundle',
  ],
  admin_permission: 'administer entity_test content',
  bundle_of: 'entity_test_no_id',
  config_export: [
    'id',
    'label',
  ],
)]
class EntityTestNoIdBundle extends ConfigEntityBundleBase {

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

}
