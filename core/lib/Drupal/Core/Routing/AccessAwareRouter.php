<?php

namespace Drupal\Core\Routing;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Cmf\Component\Routing\ChainRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\RequestContext as SymfonyRequestContext;

/**
 * A router class for Drupal with access check and upcasting.
 */
class AccessAwareRouter implements AccessAwareRouterInterface {

  /**
   * The chain router doing the actual routing.
   *
   * @var \Symfony\Cmf\Component\Routing\ChainRouter
   */
  protected $chainRouter;

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The account to use in access checks.
   *
   * @var \Drupal\Core\Session\AccountInterface;
   */
  protected $account;

  /**
   * Constructs a router for Drupal with access check and upcasting.
   *
   * @param \Symfony\Cmf\Component\Routing\ChainRouter $chain_router
   *   The chain router doing the actual routing.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to use in access checks.
   */
  public function __construct(ChainRouter $chain_router, AccessManagerInterface $access_manager, AccountInterface $account) {
    $this->chainRouter = $chain_router;
    $this->accessManager = $access_manager;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function __call($name, $arguments) {
    // Ensure to call every other function to the chained router.
    // @todo Sadly does the ChainRouter not implement an interface in CMF.
    return call_user_func_array([$this->chainRouter, $name], $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function setContext(SymfonyRequestContext $context) {
    $this->chainRouter->setContext($context);
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->chainRouter->getContext();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when access checking failed.
   */
  public function matchRequest(Request $request) {
    $parameters = $this->chainRouter->matchRequest($request);
    $request->attributes->add($parameters);
    $this->checkAccess($request);
    // We can not return $parameters because the access check can change the
    // request attributes.
    return $request->attributes->all();
  }

  /**
   * Apply access check service to the route and parameters in the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to access check.
   */
  protected function checkAccess(Request $request) {
    // The cacheability (if any) of this request's access check result must be
    // applied to the response.
    $access_result = $this->accessManager->checkRequest($request, $this->account, TRUE);
    // Allow a master request to set the access result for a subrequest: if an
    // access result attribute is already set, don't overwrite it.
    if (!$request->attributes->has(AccessAwareRouterInterface::ACCESS_RESULT)) {
      $request->attributes->set(AccessAwareRouterInterface::ACCESS_RESULT, $access_result);
    }
    if (!$access_result->isAllowed()) {
      throw new AccessDeniedHttpException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteCollection() {
    return $this->chainRouter->getRouteCollection();
  }

  /**
   * {@inheritdoc}
   */
  public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH) {
    return $this->chainRouter->generate($name, $parameters, $referenceType);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when access checking failed.
   */
  public function match($pathinfo) {
    return $this->matchRequest(Request::create($pathinfo));
  }

}

