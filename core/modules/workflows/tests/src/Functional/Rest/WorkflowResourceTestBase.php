<?php

declare(strict_types=1);

namespace Drupal\Tests\workflows\Functional\Rest;

use Drupal\Tests\rest\Functional\EntityResource\ConfigEntityResourceTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Resource test base for Workflow entity.
 */
abstract class WorkflowResourceTestBase extends ConfigEntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workflows',
    'workflow_type_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'workflow';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * The Workflow entity.
   *
   * @var \Drupal\workflows\WorkflowInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer workflows']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $workflow = Workflow::create([
      'id' => 'rest_workflow',
      'label' => 'REST Workflow',
      'type' => 'workflow_type_complex_test',
    ]);
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published');
    $configuration = $workflow->getTypePlugin()->getConfiguration();
    $configuration['example_setting'] = 'foo';
    $configuration['states']['draft']['extra'] = 'bar';
    $workflow->getTypePlugin()->setConfiguration($configuration);
    $workflow->save();
    return $workflow;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'dependencies' => [
        'module' => [
          'workflow_type_test',
        ],
      ],
      'id' => 'rest_workflow',
      'label' => 'REST Workflow',
      'langcode' => 'en',
      'status' => TRUE,
      'type' => 'workflow_type_complex_test',
      'type_settings' => [
        'states' => [
          'draft' => [
            'extra' => 'bar',
            'label' => 'Draft',
            'weight' => 0,
          ],
          'published' => [
            'label' => 'Published',
            'weight' => 1,
          ],
        ],
        'transitions' => [],
        'example_setting' => 'foo',
      ],
      'uuid' => $this->entity->uuid(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update in https://www.drupal.org/node/2300677.
    return [];
  }

}
