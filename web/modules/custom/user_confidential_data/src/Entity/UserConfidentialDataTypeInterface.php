<?php

namespace Drupal\user_confidential_data\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining User Confidential Data Type entities.
 */
interface UserConfidentialDataTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the User Confidential Data Type ID.
   *
   * @return string
   *   The User Confidential Data Type ID.
   */
  public function getId();

  /**
   * Gets the User Confidential Data Type label.
   *
   * @return string
   *   The User Confidential Data Type label.
   */
  public function label();

}