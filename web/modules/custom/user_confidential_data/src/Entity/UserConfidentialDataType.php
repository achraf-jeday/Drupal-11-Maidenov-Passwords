<?php

namespace Drupal\user_confidential_data\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the User Confidential Data Type entity.
 *
 * @ConfigEntityType(
 *   id = "user_confidential_data_type",
 *   label = @Translation("User Confidential Data Type"),
 *   label_collection = @Translation("User Confidential Data Types"),
 *   label_singular = @Translation("User Confidential Data Type"),
 *   label_plural = @Translation("User Confidential Data Types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count User Confidential Data Type",
 *     plural = "@count User Confidential Data Types",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\user_confidential_data\UserConfidentialDataTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\user_confidential_data\Form\UserConfidentialDataTypeForm",
 *       "edit" = "Drupal\user_confidential_data\Form\UserConfidentialDataTypeForm",
 *       "delete" = "Drupal\user_confidential_data\Form\UserConfidentialDataTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     },
 *   },
 *   config_prefix = "type",
 *   admin_permission = "administer user confidential data",
 *   bundle_of = "user_confidential_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/structure/user-confidential-data-type",
 *     "add-form" = "/admin/structure/user-confidential-data-type/add",
 *     "edit-form" = "/admin/structure/user-confidential-data-type/{user_confidential_data_type}/edit",
 *     "delete-form" = "/admin/structure/user-confidential-data-type/{user_confidential_data_type}/delete",
 *     "field_ui_base_route" = "/admin/structure/user-confidential-data-type"
 *   },
 *   config_export = {
 *     "id",
 *     "label"
 *   }
 * )
 */
class UserConfidentialDataType extends ConfigEntityBundleBase implements UserConfidentialDataTypeInterface {

  /**
   * The User Confidential Data Type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The User Confidential Data Type label.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

}