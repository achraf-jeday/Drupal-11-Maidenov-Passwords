<?php

namespace Drupal\user_confidential_data\Entity\Query\Sql;

use Drupal\Core\Entity\Query\Sql\Query as BaseQuery;

/**
 * Custom entity query for user_confidential_data with encrypted field support.
 */
class Query extends BaseQuery {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Get the encryption service.
    $encryption_service = \Drupal::service('user_confidential_data.field_encryption');
    $encrypted_fields = $encryption_service->getEncryptedFields();

    // Extract encrypted field conditions from the query.
    $encrypted_conditions = [];
    $this->extractEncryptedConditions($this->condition, $encrypted_fields, $encrypted_conditions);

    // If there are no encrypted field conditions, use the default behavior.
    if (empty($encrypted_conditions)) {
      return parent::execute();
    }

    // Store the encrypted field filters and create a new clean query for non-encrypted fields.
    $storage = \Drupal::entityTypeManager()->getStorage($this->entityTypeId);
    $clean_query = $storage->getQuery();

    // Copy access check.
    $clean_query->accessCheck($this->accessCheck ?? FALSE);

    // Copy only non-encrypted conditions to the clean query.
    $this->copyNonEncryptedConditions($this->condition, $clean_query, $encrypted_fields);

    // Execute the clean query with only non-encrypted conditions.
    $result = $clean_query->execute();

    // If no results, return empty array.
    if (empty($result)) {
      return $result;
    }

    // Load the entities.
    $entities = $storage->loadMultiple($result);

    // Filter entities by encrypted field values (already decrypted by storage).
    $filtered_ids = [];
    foreach ($entities as $entity) {
      if ($this->matchesEncryptedConditions($entity, $encrypted_conditions)) {
        $filtered_ids[] = $entity->id();
      }
    }

    return $filtered_ids;
  }

  /**
   * Extracts encrypted field conditions from a condition group.
   *
   * @param \Drupal\Core\Entity\Query\ConditionInterface $condition_group
   *   The condition group.
   * @param array $encrypted_fields
   *   Array of encrypted field names.
   * @param array &$encrypted_conditions
   *   Array to store extracted encrypted conditions.
   */
  protected function extractEncryptedConditions($condition_group, array $encrypted_fields, array &$encrypted_conditions) {
    $conditions = $condition_group->conditions();

    foreach ($conditions as $key => $condition) {
      if (!is_numeric($key)) {
        continue;
      }

      // Handle nested condition groups.
      if (isset($condition['field']) && $condition['field'] instanceof \Drupal\Core\Entity\Query\ConditionInterface) {
        $this->extractEncryptedConditions($condition['field'], $encrypted_fields, $encrypted_conditions);
      }
      // Handle regular conditions.
      elseif (isset($condition['field']) && in_array($condition['field'], $encrypted_fields)) {
        $encrypted_conditions[] = [
          'field' => $condition['field'],
          'value' => $condition['value'] ?? NULL,
          'operator' => $condition['operator'] ?? '=',
        ];
      }
    }
  }

  /**
   * Copies non-encrypted field conditions to a new query.
   *
   * @param \Drupal\Core\Entity\Query\ConditionInterface $source_conditions
   *   The source condition group.
   * @param \Drupal\Core\Entity\Query\QueryInterface $target_query
   *   The target query to copy conditions to.
   * @param array $encrypted_fields
   *   Array of encrypted field names.
   */
  protected function copyNonEncryptedConditions($source_conditions, $target_query, array $encrypted_fields) {
    $conditions = $source_conditions->conditions();

    foreach ($conditions as $key => $condition) {
      if (!is_numeric($key)) {
        continue;
      }

      // Handle nested condition groups.
      if (isset($condition['field']) && $condition['field'] instanceof \Drupal\Core\Entity\Query\ConditionInterface) {
        // For simplicity, we don't support nested condition groups with encrypted fields.
        // This would need more complex logic to handle properly.
        continue;
      }

      // Copy non-encrypted field conditions.
      if (isset($condition['field']) && !in_array($condition['field'], $encrypted_fields)) {
        $target_query->condition(
          $condition['field'],
          $condition['value'] ?? NULL,
          $condition['operator'] ?? '='
        );
      }
    }
  }

  /**
   * Checks if there are any non-encrypted field conditions.
   *
   * @param \Drupal\Core\Entity\Query\ConditionInterface $condition_group
   *   The condition group.
   * @param array $encrypted_fields
   *   Array of encrypted field names.
   *
   * @return bool
   *   TRUE if there are non-encrypted conditions, FALSE otherwise.
   */
  protected function hasNonEncryptedConditions($condition_group, array $encrypted_fields) {
    $conditions = $condition_group->conditions();

    foreach ($conditions as $key => $condition) {
      if (!is_numeric($key)) {
        continue;
      }

      // Handle nested condition groups.
      if (isset($condition['field']) && $condition['field'] instanceof \Drupal\Core\Entity\Query\ConditionInterface) {
        if ($this->hasNonEncryptedConditions($condition['field'], $encrypted_fields)) {
          return TRUE;
        }
      }
      // Check if this is a non-encrypted field condition.
      elseif (isset($condition['field']) && !in_array($condition['field'], $encrypted_fields)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Removes encrypted field conditions from a condition group.
   *
   * @param \Drupal\Core\Entity\Query\ConditionInterface $condition_group
   *   The condition group.
   * @param array $encrypted_fields
   *   Array of encrypted field names.
   */
  protected function removeEncryptedConditions($condition_group, array $encrypted_fields) {
    $conditions = &$condition_group->conditions();

    foreach ($conditions as $key => $condition) {
      if (!is_numeric($key)) {
        continue;
      }

      // Handle nested condition groups.
      if (isset($condition['field']) && $condition['field'] instanceof \Drupal\Core\Entity\Query\ConditionInterface) {
        $this->removeEncryptedConditions($condition['field'], $encrypted_fields);
      }
      // Remove encrypted field conditions.
      elseif (isset($condition['field']) && in_array($condition['field'], $encrypted_fields)) {
        unset($conditions[$key]);
      }
    }
  }

  /**
   * Checks if an entity matches all encrypted field conditions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param array $conditions
   *   Array of encrypted field conditions.
   *
   * @return bool
   *   TRUE if the entity matches all conditions, FALSE otherwise.
   */
  protected function matchesEncryptedConditions($entity, array $conditions) {
    foreach ($conditions as $condition) {
      if (!$this->matchesEncryptedCondition($entity, $condition)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Checks if an entity matches a single encrypted field condition.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param array $condition
   *   The condition to check.
   *
   * @return bool
   *   TRUE if the entity matches the condition, FALSE otherwise.
   */
  protected function matchesEncryptedCondition($entity, array $condition) {
    $field_name = $condition['field'];
    $expected_value = $condition['value'];
    $operator = $condition['operator'] ?? '=';

    // Get the field value (already decrypted by storage).
    if (!$entity->hasField($field_name)) {
      return FALSE;
    }

    $field = $entity->get($field_name);
    if ($field->isEmpty()) {
      return $operator === 'IS NULL' || $operator === '<>';
    }

    $actual_value = $field->value;

    // Apply the operator.
    return $this->compareValues($actual_value, $expected_value, $operator);
  }

  /**
   * Compares two values based on an operator.
   *
   * @param mixed $actual
   *   The actual value from the entity.
   * @param mixed $expected
   *   The expected value from the condition.
   * @param string $operator
   *   The comparison operator.
   *
   * @return bool
   *   TRUE if the comparison matches, FALSE otherwise.
   */
  protected function compareValues($actual, $expected, $operator) {
    // Normalize string values for case-insensitive comparison.
    if (is_string($actual) && is_string($expected)) {
      $actual = mb_strtolower($actual);
      $expected = mb_strtolower($expected);
    }

    switch ($operator) {
      case '=':
        return $actual == $expected;

      case '<>':
      case '!=':
        return $actual != $expected;

      case '>':
        return $actual > $expected;

      case '>=':
        return $actual >= $expected;

      case '<':
        return $actual < $expected;

      case '<=':
        return $actual <= $expected;

      case 'CONTAINS':
        return is_string($actual) && is_string($expected) &&
               strpos($actual, $expected) !== FALSE;

      case 'STARTS_WITH':
        return is_string($actual) && is_string($expected) &&
               strpos($actual, $expected) === 0;

      case 'ENDS_WITH':
        return is_string($actual) && is_string($expected) &&
               substr($actual, -strlen($expected)) === $expected;

      case 'IN':
        return is_array($expected) && in_array($actual, $expected);

      case 'NOT IN':
        return is_array($expected) && !in_array($actual, $expected);

      case 'IS NULL':
        return empty($actual);

      case 'IS NOT NULL':
        return !empty($actual);

      default:
        return FALSE;
    }
  }

}
