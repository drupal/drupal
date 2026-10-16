<?php

declare(strict_types=1);

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Form\BundleEntityDeleteForm;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Defines the Test entity bundle configuration entity.
 */
#[ConfigEntityType(
  id: 'entity_test_bundle',
  label: new TranslatableMarkup('Test entity bundle'),
  label_collection: new TranslatableMarkup('Test entity bundles'),
  label_singular: new TranslatableMarkup('test entity bundle'),
  label_plural: new TranslatableMarkup('test entity bundles'),
  config_prefix: 'entity_test_bundle',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  handlers: [
    'access' => EntityAccessControlHandler::class,
    'form' => [
      'default' => BundleEntityFormBase::class,
      'delete' => BundleEntityDeleteForm::class,
    ],
    'route_provider' => [
      'html' => DefaultHtmlRouteProvider::class,
    ],
  ],
  links: [
    'edit-form' => '/entity_test_bundle/{entity_test_bundle}',
    'add-form' => '/entity_test_bundle/add',
    'delete-form' => '/entity_test_bundle/delete/{entity_test_bundle}',
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
