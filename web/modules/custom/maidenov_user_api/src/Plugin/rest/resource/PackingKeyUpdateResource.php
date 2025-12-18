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
 * Provides a Packing Key Update Resource.
 *
 * @RestResource(
 *   id = "packing_key_update",
 *   label = @Translation("Packing Key Update"),
 *   uri_paths = {
 *     "create" = "/api/user/packing-key"
 *   }
 * )
 */
class PackingKeyUpdateResource extends ResourceBase {

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
   * Updates the user's packing key and encryption salt.
   *
   * @param array $data
   *   The request data containing:
   *   - packing_key: The new packing key to hash and store
   *   - packing_key_confirm: Confirmation of the packing key
   *   - salt: Base64 encoded salt for PBKDF2 key derivation
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
      throw new AccessDeniedHttpException('You must be logged in to update your packing key.');
    }

    // Validate required fields.
    if (empty($data['packing_key'])) {
      throw new BadRequestHttpException('Packing key is required.');
    }

    if (empty($data['packing_key_confirm'])) {
      throw new BadRequestHttpException('Packing key confirmation is required.');
    }

    if (empty($data['salt'])) {
      throw new BadRequestHttpException('Encryption salt is required.');
    }

    // Verify packing keys match.
    if ($data['packing_key'] !== $data['packing_key_confirm']) {
      throw new UnprocessableEntityHttpException('Packing keys do not match.');
    }

    try {
      // Load the current user.
      $user = User::load($this->currentUser->id());

      if (!$user) {
        throw new AccessDeniedHttpException('User not found.');
      }

      // Hash the packing key (using Drupal's password hasher for consistency).
      $hashed_packing_key = $this->passwordHasher->hash($data['packing_key']);

      // Update the packing key field and salt.
      $user->set('field_packing_key', $hashed_packing_key);
      $user->set('field_encryption_salt', $data['salt']);

      // Validate the user entity.
      $violations = $user->validate();
      if ($violations->count() > 0) {
        $messages = [];
        foreach ($violations as $violation) {
          $messages[] = $violation->getMessage();
        }
        throw new UnprocessableEntityHttpException(implode(' ', $messages));
      }

      // Save the user.
      $user->save();

      $this->logger->notice('User @name updated their packing key.', [
        '@name' => $user->getAccountName(),
      ]);

      return new ModifiedResourceResponse([
        'message' => 'Packing key updated successfully.',
      ], 200);

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
      $this->logger->error('Packing key update failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw new BadRequestHttpException('Packing key update failed. Please try again.');
    }
  }

}
