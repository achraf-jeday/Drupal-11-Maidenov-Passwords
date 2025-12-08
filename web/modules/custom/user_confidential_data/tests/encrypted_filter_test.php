<?php

/**
 * @file
 * Simple test script to verify encrypted field filtering works.
 *
 * Usage: drush php:script encrypted_filter_test.php
 */

use Drupal\user_confidential_data\Entity\UserConfidentialData;

// Get the entity type manager.
$entity_type_manager = \Drupal::entityTypeManager();
$storage = $entity_type_manager->getStorage('user_confidential_data');

// Test 1: Create test entities with encrypted data.
echo "Creating test entities...\n";

// Get current user ID (default to UID 1).
$current_user = \Drupal::currentUser();
$user_id = $current_user->id() ?: 1;

$test_data = [
  [
    'type' => 'type_1',
    'user_id' => $user_id,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'username' => 'johndoe',
    'notes' => 'Important notes about John',
    'status' => 1,
  ],
  [
    'type' => 'type_1',
    'user_id' => $user_id,
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'username' => 'janesmith',
    'notes' => 'Regular notes about Jane',
    'status' => 1,
  ],
  [
    'type' => 'type_1',
    'user_id' => $user_id,
    'name' => 'Bob Johnson',
    'email' => 'bob@test.com',
    'username' => 'bobjohnson',
    'notes' => 'Important notes about Bob',
    'status' => 1,
  ],
];

$created_ids = [];
foreach ($test_data as $data) {
  $entity = $storage->create($data);
  $entity->save();
  $created_ids[] = $entity->id();
  echo "Created entity {$entity->id()}: {$data['name']}\n";
}

// Test 2: Test filtering by name.
echo "\n--- Test 2: Filter by name='John Doe' ---\n";
$query = $storage->getQuery()
  ->condition('name', 'John Doe')
  ->accessCheck(FALSE);
echo "Query class: " . get_class($query) . "\n";
$result = $query->execute();
echo "Found " . count($result) . " entities\n";
foreach ($result as $id) {
  $entity = $storage->load($id);
  echo "  - ID {$id}: {$entity->get('name')->value}\n";
}

// Test 3: Test filtering by email.
echo "\n--- Test 3: Filter by email='jane@example.com' ---\n";
$query = $storage->getQuery()
  ->condition('email', 'jane@example.com')
  ->accessCheck(FALSE);
$result = $query->execute();
echo "Found " . count($result) . " entities\n";
foreach ($result as $id) {
  $entity = $storage->load($id);
  echo "  - ID {$id}: {$entity->get('email')->value}\n";
}

// Test 4: Test filtering by username.
echo "\n--- Test 4: Filter by username='bobjohnson' ---\n";
$query = $storage->getQuery()
  ->condition('username', 'bobjohnson')
  ->accessCheck(FALSE);
$result = $query->execute();
echo "Found " . count($result) . " entities\n";
foreach ($result as $id) {
  $entity = $storage->load($id);
  echo "  - ID {$id}: {$entity->get('username')->value}\n";
}

// Test 5: Test filtering with CONTAINS operator.
echo "\n--- Test 5: Filter by notes CONTAINS 'Important' ---\n";
$query = $storage->getQuery()
  ->condition('notes', 'Important', 'CONTAINS')
  ->accessCheck(FALSE);
$result = $query->execute();
echo "Found " . count($result) . " entities\n";
foreach ($result as $id) {
  $entity = $storage->load($id);
  echo "  - ID {$id}: {$entity->get('name')->value} - {$entity->get('notes')->value}\n";
}

// Test 6: Test multiple filters.
echo "\n--- Test 6: Filter by name='John Doe' AND email='john@example.com' ---\n";
$query = $storage->getQuery()
  ->condition('name', 'John Doe')
  ->condition('email', 'john@example.com')
  ->accessCheck(FALSE);
$result = $query->execute();
echo "Found " . count($result) . " entities\n";
foreach ($result as $id) {
  $entity = $storage->load($id);
  echo "  - ID {$id}: {$entity->get('name')->value} ({$entity->get('email')->value})\n";
}

// Test 7: Test filtering by status (non-encrypted) + name (encrypted).
echo "\n--- Test 7: Filter by status=1 AND name='Jane Smith' ---\n";
$query = $storage->getQuery()
  ->condition('status', 1)
  ->condition('name', 'Jane Smith')
  ->accessCheck(FALSE);
$result = $query->execute();
echo "Found " . count($result) . " entities\n";
foreach ($result as $id) {
  $entity = $storage->load($id);
  echo "  - ID {$id}: {$entity->get('name')->value} (status: {$entity->get('status')->value})\n";
}

// Cleanup: Delete test entities.
echo "\n--- Cleanup: Deleting test entities ---\n";
foreach ($created_ids as $id) {
  $entity = $storage->load($id);
  if ($entity) {
    $entity->delete();
    echo "Deleted entity {$id}\n";
  }
}

echo "\n✓ All tests completed!\n";
