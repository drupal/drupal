<?php

/**
 * @file
 * Contains SchemaController.
 */

namespace Drupal\rdf\SiteSchema;

use Drupal\rdf\SiteSchema\SiteSchema;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resource controller for displaying entity schemas.
 */
class SchemaController implements ContainerAwareInterface {

  /**
   * The injection container for this object.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Injects the service container used by this object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   */
  public function setContainer(ContainerInterface $container = NULL) {
    $this->container = $container;
  }

  /**
   * Responds to a schema request for a bundle of a given entity type.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param string $schema_path
   *   The relative base path for the schema.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function bundle($entity_type, $bundle, $schema_path) {
    if (!$entity_info = entity_get_info($entity_type)) {
      throw new NotFoundHttpException(t('Entity type @entity_type not found', array('@entity_type' => $entity_type)));
    }
    if (!array_key_exists($bundle, entity_get_bundles($entity_type))) {
      throw new NotFoundHttpException(t('Bundle @bundle not found', array('@bundle' => $bundle)));
    }

    $serializer = $this->container->get('serializer');
    $site_schema_manager = $this->container->get('rdf.site_schema_manager');
    $schema = $site_schema_manager->getSchema($schema_path);
    // @todo Remove hard-coded mimetype once we have proper conneg.
    $content = $serializer->serialize($schema->bundle($entity_type, $bundle), 'jsonld');
    return new Response($content, 200, array('Content-type' => 'application/json'));
  }

}
