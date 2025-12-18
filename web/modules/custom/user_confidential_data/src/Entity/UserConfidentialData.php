<?php

namespace Drupal\user_confidential_data\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the User Confidential Data entity class.
 *
 * @ContentEntityType(
 *   id = "user_confidential_data",
 *   label = @Translation("User Confidential Data"),
 *   label_collection = @Translation("User Confidential Data"),
 *   label_singular = @Translation("User Confidential Data"),
 *   label_plural = @Translation("User Confidential Data"),
 *   label_count = @PluralTranslation(
 *     singular = "@count User Confidential Data",
 *     plural = "@count User Confidential Data",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\user_confidential_data\Storage\UserConfidentialDataStorage",
 *     "storage_schema" = "Drupal\user_confidential_data\UserConfidentialDataStorageSchema",
 *     "access" = "Drupal\user_confidential_data\UserConfidentialDataAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\user_confidential_data\UserConfidentialDataListBuilder",
 *     "form" = {
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "user_confidential_data",
 *   revision_table = "user_confidential_data_revision",
 *   admin_permission = "administer user confidential data",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "label" = "name",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "collection" = "/admin/content/user-confidential-data",
 *     "add-page" = "/admin/content/user-confidential-data/add",
 *     "add-form" = "/admin/content/user-confidential-data/add/{user_confidential_data_type}",
 *     "canonical" = "/user-confidential-data/{user_confidential_data}",
 *     "edit-form" = "/admin/content/user-confidential-data/{user_confidential_data}/edit",
 *     "delete-form" = "/admin/content/user-confidential-data/{user_confidential_data}/delete",
 *     "version-history" = "/admin/content/user-confidential-data/{user_confidential_data}/revisions",
 *     "revision" = "/admin/content/user-confidential-data/{user_confidential_data}/revisions/{user_confidential_data_revision}/view",
 *     "revision_revert" = "/admin/content/user-confidential-data/{user_confidential_data}/revisions/{user_confidential_data_revision}/revert",
 *     "revision_delete" = "/admin/content/user-confidential-data/{user_confidential_data}/revisions/{user_confidential_data_revision}/delete",
 *   },
 *   bundle_entity_type = "user_confidential_data_type",
 *   field_ui_base_route = "entity.user_confidential_data_type.collection",
 * )
 */
class UserConfidentialData extends ContentEntityBase implements UserConfidentialDataInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no revision author has been set explicitly, make the user_confidential_data
    // owner the revision author.
    if ($this->hasField('revision_user') && !$this->get('revision_user')->value) {
      $this->set('revision_user', $this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if (!$this->isNewRevision() && isset($this->original) && (!isset($record->revision_log) || $record->revision_log === '')) {
      // If we are updating an existing user_confidential_data without adding a new
      // revision, we need to make sure $entity->revision_log is reset whenever
      // $entity->revision is not set to TRUE.
      $record->revision_log = '';
      $this->set('revision_log', NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasLinkTemplate($key) {
    // Treat canonical as if it exists (we'll redirect it in toUrl()).
    if ($key === 'canonical') {
      return TRUE;
    }
    return parent::hasLinkTemplate($key);
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    // Redirect canonical requests to collection since we don't have a view page.
    if ($rel === 'canonical') {
      return \Drupal\Core\Url::fromRoute('entity.user_confidential_data.collection', [], $options);
    }
    return parent::toUrl($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the uid base field.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user this confidential data belongs to.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Add the name field.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the User Confidential Data.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 1024,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email'))
      ->setDescription(t('The email address associated with this confidential data.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 1024,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the user confidential data was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the user confidential data was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    // Revision metadata fields - required for revision functionality
    $fields['revision_user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision User'))
      ->setDescription(t('The user who created the revision.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setCardinality(1)
      ->setReadOnly(TRUE);

    $fields['revision_created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision created'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setReadOnly(TRUE);

    $fields['revision_log'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Revision log message'))
      ->setDescription(t('The log entry explaining the changes in this revision.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue('');

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Whether this translation affects the revision translation of an updated entity.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    // Add the Username field
    $fields['username'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Username'))
      ->setDescription(t('The username for this confidential data.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 1024,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Add the Password field
    $fields['password'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Password'))
      ->setDescription(t('The password for this confidential data.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 1024,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Add the Link field
    $fields['link'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Link'))
      ->setDescription(t('The link associated with this confidential data.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 2048,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'url',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Add the Notes field
    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Additional notes for this confidential data.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'basic_string',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 5,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->get('user_id')->entity;
  }

}
