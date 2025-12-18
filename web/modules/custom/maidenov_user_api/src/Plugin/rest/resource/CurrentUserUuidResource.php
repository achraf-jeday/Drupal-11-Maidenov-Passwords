<?php

namespace Drupal\maidenov_user_api\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get the current user's UUID.
 *
 * @RestResource(
 *   id = "current_user_uuid",
 *   label = @Translation("Current User UUID"),
 *   uri_paths = {
 *     "canonical" = "/api/user/current-uuid"
 *   }
 * )
 */
class CurrentUserUuidResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new CurrentUserUuidResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('maidenov_user_api'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws exception when user is not authenticated.
   */
  public function get() {
    // Check if user is authenticated
    if ($this->currentUser->isAnonymous()) {
      throw new AccessDeniedHttpException('You must be authenticated to access this resource.');
    }

    // Load the full user entity to get the UUID
    $user = User::load($this->currentUser->id());

    if (!$user) {
      throw new AccessDeniedHttpException('User not found.');
    }

    $response_data = [
      'uid' => (int) $user->id(),
      'uuid' => $user->uuid(),
      'name' => $user->getAccountName(),
      'email' => $user->getEmail(),
    ];

    return new ResourceResponse($response_data, 200);
  }

}
