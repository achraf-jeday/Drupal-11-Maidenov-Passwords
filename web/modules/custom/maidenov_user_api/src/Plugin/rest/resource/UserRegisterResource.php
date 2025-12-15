<?php

namespace Drupal\maidenov_user_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Psr\Log\LoggerInterface;

/**
 * Provides a User Registration Resource.
 *
 * @RestResource(
 *   id = "user_register",
 *   label = @Translation("User Registration"),
 *   uri_paths = {
 *     "create" = "/api/register"
 *   }
 * )
 */
class UserRegisterResource extends ResourceBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.factory')->get('maidenov_user_api');
    return $instance;
  }

  /**
   * Responds to POST requests.
   *
   * Registers a new user account.
   *
   * @param array $data
   *   The registration data.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
   */
  public function post(array $data) {
    // Validate required fields.
    if (empty($data['email'])) {
      throw new BadRequestHttpException('Email is required.');
    }

    if (empty($data['password'])) {
      throw new BadRequestHttpException('Password is required.');
    }

    // Validate email format.
    if (!\Drupal::service('email.validator')->isValid($data['email'])) {
      throw new UnprocessableEntityHttpException('Invalid email format.');
    }

    try {
      // Check if user already exists.
      $existing_user = user_load_by_mail($data['email']);
      if ($existing_user) {
        throw new UnprocessableEntityHttpException('A user with this email already exists.');
      }

      // Check if username already exists (if provided).
      $username = $data['username'] ?? $data['email'];
      $existing_user_by_name = user_load_by_name($username);
      if ($existing_user_by_name) {
        throw new UnprocessableEntityHttpException('A user with this username already exists.');
      }

      // Create the user account.
      $user = User::create([
        'name' => $username,
        'mail' => $data['email'],
        'pass' => $data['password'],
        'status' => 1,
        'init' => $data['email'],
      ]);

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

      $this->logger->notice('User @name registered successfully.', [
        '@name' => $user->getAccountName(),
      ]);

      return new ModifiedResourceResponse([
        'message' => 'User registered successfully.',
        'uid' => (int) $user->id(),
        'email' => $user->getEmail(),
        'username' => $user->getAccountName(),
      ], 201);

    }
    catch (UnprocessableEntityHttpException $e) {
      throw $e;
    }
    catch (BadRequestHttpException $e) {
      throw $e;
    }
    catch (\Exception $e) {
      $this->logger->error('Registration failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw new BadRequestHttpException('Registration failed. Please try again.');
    }
  }

}
