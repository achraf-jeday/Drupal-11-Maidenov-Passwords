<?php

namespace Drupal\user_confidential_data\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Cache\CacheableResponseInterface;

/**
 * Adds user cache context to JSON:API user_confidential_data responses.
 *
 * This ensures Dynamic Page Cache creates separate cache entries per user.
 */
class UserConfidentialDataCacheSubscriber implements EventSubscriberInterface {

  /**
   * Adds user cache context to the response.
   *
   * This runs at priority 10 during KernelEvents::RESPONSE,
   * which is AFTER JSON:API builds the response but BEFORE
   * Dynamic Page Cache stores it (priority 0).
   */
  public function onResponse(ResponseEvent $event) {
    $request = $event->getRequest();
    $response = $event->getResponse();
    $path = $request->getPathInfo();

    // Check if this is a JSON:API request for user_confidential_data.
    if (!str_starts_with($path, '/jsonapi/user_confidential_data')) {
      return;
    }

    // Add cache contexts to cacheable responses.
    if ($response instanceof CacheableResponseInterface) {
      $cache_metadata = $response->getCacheableMetadata();

      // Add user and user.permissions cache contexts.
      // This tells Drupal's cache system to create separate cache entries per user.
      $cache_metadata->addCacheContexts(['user', 'user.permissions']);

      // Add entity type list cache tag for proper invalidation.
      $cache_metadata->addCacheTags(['user_confidential_data_list']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Priority 10: Run AFTER JSON:API (priority -128) but BEFORE
    // Dynamic Page Cache stores the response (priority 0).
    $events[KernelEvents::RESPONSE][] = ['onResponse', 10];
    return $events;
  }

}
