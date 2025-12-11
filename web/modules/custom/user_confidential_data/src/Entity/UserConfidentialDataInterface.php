<?php

namespace Drupal\user_confidential_data\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining User Confidential Data entities.
 */
interface UserConfidentialDataInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the User Confidential Data name.
   *
   * @return string
   *   Name of the User Confidential Data.
   */
  public function getName();

  /**
   * Sets the User Confidential Data name.
   *
   * @param string $name
   *   The User Confidential Data name.
   *
   * @return \Drupal\user_confidential_data\Entity\UserConfidentialDataInterface
   *   The called User Confidential Data entity.
   */
  public function setName($name);

  /**
   * Gets the User Confidential Data creation timestamp.
   *
   * @return int
   *   Creation timestamp of the User Confidential Data.
   */
  public function getCreatedTime();

  /**
   * Sets the User Confidential Data creation timestamp.
   *
   * @param int $timestamp
   *   The User Confidential Data creation timestamp.
   *
   * @return \Drupal\user_confidential_data\Entity\UserConfidentialDataInterface
   *   The called User Confidential Data entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the associated user entity for this confidential data.
   *
   * @return \Drupal\user\UserInterface|null
   *   The user entity, or NULL if not associated.
   */
  public function getUser();

}