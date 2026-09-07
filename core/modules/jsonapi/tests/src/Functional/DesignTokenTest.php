<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Theme\Entity\DesignToken;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiSpec;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * JSON:API integration test for the "DesignToken" config entity type.
 */
#[Group('jsonapi')]
#[RunTestsInSeparateProcesses]
class DesignTokenTest extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'design_token';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'design_token--design_token';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\Core\Theme\Entity\DesignTokenInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method): void {
    $this->grantPermissionsToTestedRole(['administer design tokens']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a design token.
    $entity = DesignToken::create([
      'id' => 'my_token',
      'path' => 'my-token',
      'type' => 'fontWeight',
      'scopes' => [
        ':root' => 500,
      ],
    ]);

    $entity->save();
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument(): array {
    $self_url = Url::fromUri('base:/jsonapi/design_token/design_token/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => JsonApiSpec::SUPPORTED_SPECIFICATION_PERMALINK],
          ],
        ],
        'version' => JsonApiSpec::SUPPORTED_SPECIFICATION_VERSION,
      ],
      'links' => [
        'self' => ['href' => $self_url],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'design_token--design_token',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'dependencies' => [],
          'langcode' => 'en',
          'status' => TRUE,
          'path' => 'my-token',
          'design_token_type' => 'fontWeight',
          'scopes' => [
            ':root' => '500',
          ],
          'drupal_internal__id' => 'my_token',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument(): array {
    // @todo Update in https://www.drupal.org/node/2300677.
    return [];
  }

}
