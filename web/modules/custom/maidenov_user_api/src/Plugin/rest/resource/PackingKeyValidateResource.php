<?php

namespace Drupal\maidenov_user_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\Core\Password\PasswordInterface;

/**
 * Provides a Packing Key Validation Resource.
 *
 * @RestResource(
 *   id = "packing_key_validate",
 *   label = @Translation("Packing Key Validation"),
 *   uri_paths = {
 *     "create" = "/api/user/validate-packing-key"
 *   }
 * )
 */
class PackingKeyValidateResource extends ResourceBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The password hasher service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $passwordHasher;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    PasswordInterface $password_hasher
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->passwordHasher = $password_hasher;
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
      $container->get('current_user'),
      $container->get('password')
    );
  }

  /**
   * Responds to POST requests.
   *
   * Validates the user's packing key.
   *
   * @param array $data
   *   The request data containing:
   *   - packing_key: The packing key to validate
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
   */
  public function post(array $data) {
    // Ensure user is authenticated.
    if ($this->currentUser->isAnonymous()) {
      throw new AccessDeniedHttpException('You must be logged in to validate your packing key.');
    }

    // Validate required field.
    if (!isset($data['packing_key']) || $data['packing_key'] === '') {
      throw new BadRequestHttpException('Packing key is required.');
    }

    try {
      // Load the current user.
      $user = User::load($this->currentUser->id());

      if (!$user) {
        throw new AccessDeniedHttpException('User not found.');
      }

      // Get the stored packing key hash.
      $stored_hash = $user->get('field_packing_key')->value;

      // Check if packing key is set.
      if (empty($stored_hash)) {
        throw new UnprocessableEntityHttpException('Packing key has not been set. Please set your packing key first.');
      }

      // Verify the packing key.
      $is_valid = $this->passwordHasher->check($data['packing_key'], $stored_hash);

      if ($is_valid) {
        $this->logger->info('User @name successfully validated their packing key.', [
          '@name' => $user->getAccountName(),
        ]);

        return new ModifiedResourceResponse([
          'valid' => TRUE,
          'message' => 'Packing key is correct.',
        ], 200);
      }
      else {
        // Log failed validation attempt for security monitoring.
        $this->logger->warning('User @name failed to validate their packing key.', [
          '@name' => $user->getAccountName(),
        ]);

        return new ModifiedResourceResponse([
          'valid' => FALSE,
          'message' => 'Packing key is incorrect.',
        ], 200);
      }

    }
    catch (UnprocessableEntityHttpException $e) {
      throw $e;
    }
    catch (AccessDeniedHttpException $e) {
      throw $e;
    }
    catch (BadRequestHttpException $e) {
      throw $e;
    }
    catch (\Exception $e) {
      $this->logger->error('Packing key validation failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw new BadRequestHttpException('Packing key validation failed. Please try again.');
    }
  }

}
