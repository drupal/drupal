<?php

/**
 * @file
 * Contains \Drupal\user\Controller\UserAutocompleteController.
 */
namespace Drupal\user\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\user\UserAutocomplete;

/**
 * Controller routines for user routes.
 */
class UserAutocompleteController implements ContainerInjectionInterface {

  /**
   * The user autocomplete helper class to find matching user names.
   *
   * @var \Drupal\user\UserAutocomplete
   */
  protected $userAutocomplete;

  /**
   * Constructs an UserAutocompleteController object.
   *
   * @param \Drupal\user\UserAutocomplete $user_autocomplete
   *   The user autocomplete helper class to find matching user names.
   */
  public function __construct(UserAutocomplete $user_autocomplete) {
    $this->userAutocomplete = $user_autocomplete;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.autocomplete')
    );
  }

  /**
   * Returns response for the user autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param bool $include_anonymous
   *   (optional) TRUE if the name used to indicate anonymous users (e.g.
   *   "Anonymous") should be autocompleted. Defaults to FALSE.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for existing users.
   *
   * @see \Drupal\user\UserAutocomplete::getMatches()
   */
  public function autocompleteUser(Request $request, $include_anonymous = FALSE) {
    $matches = $this->userAutocomplete->getMatches($request->query->get('q'), $include_anonymous);

    return new JsonResponse($matches);
  }

  /**
   * Returns response for the user autocompletion with the anonymous user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for existing users.
   *
   * @see \Drupal\user\UserAutocomplete::autocompleteUser()
   */
  public function autocompleteUserAnonymous(Request $request) {
    return $this->autocompleteUser($request, TRUE);
  }

}
