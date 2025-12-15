<?php

namespace Drupal\maidenov_user_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;

/**
 * Provides a Packing Key Exists Check Resource.
 *
 * @RestResource(
 *   id = "packing_key_exists",
 *   label = @Translation("Packing Key Exists Check"),
 *   uri_paths = {
 *     "canonical" = "/api/user/packing-key/exists"
 *   }
 * )
 */
class PackingKeyExistsResource extends ResourceBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
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
   * Checks if the user has set a packing key.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function get() {
    // Ensure user is authenticated.
    if ($this->currentUser->isAnonymous()) {
      throw new AccessDeniedHttpException('You must be logged in to check packing key status.');
    }

    try {
      // Load the current user.
      $user = User::load($this->currentUser->id());

      if (!$user) {
        throw new AccessDeniedHttpException('User not found.');
      }

      // Get the stored packing key hash.
      $stored_hash = $user->get('field_packing_key')->value;

      // Check if packing key is set (not null and not empty).
      $exists = !empty($stored_hash);

      return new ResourceResponse([
        'exists' => $exists,
        'message' => $exists
          ? 'Packing key has been set.'
          : 'Packing key has not been set.',
      ], 200);

    }
    catch (AccessDeniedHttpException $e) {
      throw $e;
    }
    catch (\Exception $e) {
      $this->logger->error('Packing key exists check failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw new AccessDeniedHttpException('Packing key status check failed. Please try again.');
    }
  }

}
