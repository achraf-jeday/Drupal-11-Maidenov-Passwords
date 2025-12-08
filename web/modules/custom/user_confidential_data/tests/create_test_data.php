<?php

/**
 * @file
 * Script to create 300 test entities with random encrypted data.
 *
 * Usage: drush php:script create_test_data.php
 */

use Drupal\user_confidential_data\Entity\UserConfidentialData;

// Get the entity type manager.
$entity_type_manager = \Drupal::entityTypeManager();
$storage = $entity_type_manager->getStorage('user_confidential_data');

// Get current user ID (default to UID 1).
$current_user = \Drupal::currentUser();
$user_id = $current_user->id() ?: 1;

// Arrays of random data components.
$first_names = [
  'John', 'Jane', 'Michael', 'Sarah', 'David', 'Emily', 'Robert', 'Lisa',
  'James', 'Mary', 'William', 'Patricia', 'Richard', 'Jennifer', 'Thomas',
  'Linda', 'Charles', 'Barbara', 'Daniel', 'Elizabeth', 'Matthew', 'Susan',
  'Anthony', 'Jessica', 'Mark', 'Karen', 'Donald', 'Nancy', 'Steven', 'Betty',
  'Paul', 'Margaret', 'Andrew', 'Sandra', 'Joshua', 'Ashley', 'Kenneth', 'Kimberly',
  'Kevin', 'Emily', 'Brian', 'Donna', 'George', 'Michelle', 'Timothy', 'Carol',
  'Ronald', 'Amanda', 'Edward', 'Melissa'
];

$last_names = [
  'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
  'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson',
  'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Thompson', 'White',
  'Harris', 'Clark', 'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King',
  'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores', 'Green', 'Adams',
  'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell', 'Carter', 'Roberts'
];

$note_prefixes = [
  'Password for', 'Private data for', 'Confidential info about', 'Secret notes on',
  'Restricted access to', 'Sensitive information regarding', 'Classified details of',
  'Personal credentials for', 'Private account details for', 'Secure data about',
  'Protected information on', 'Confidential password for', 'Private key for',
  'Encrypted data regarding', 'Secure credentials for', 'Important password for',
  'Critical data about', 'Sensitive notes on', 'Restricted information for',
  'Private notes about', 'Confidential access to', 'Secret credentials for',
  'Protected notes on', 'Personal information about', 'Secure notes regarding'
];

$note_suffixes = [
  'banking account', 'email service', 'social media', 'work portal',
  'cloud storage', 'vpn access', 'database server', 'admin panel',
  'payment gateway', 'api credentials', 'ftp server', 'ssh access',
  'remote desktop', 'backup system', 'monitoring tool', 'development environment',
  'production server', 'staging environment', 'customer database', 'file server',
  'mail server', 'web hosting', 'domain registrar', 'ssl certificate',
  'code repository', 'project management', 'communication platform', 'crm system',
  'accounting software', 'inventory system'
];

$domains = [
  'example.com', 'test.com', 'demo.org', 'sample.net', 'mysite.com',
  'webapp.io', 'services.net', 'platform.org', 'systems.com', 'portal.net',
  'secure.com', 'private.org', 'confidential.net', 'encrypted.com', 'protected.org'
];

echo "Creating 300 test entities with random encrypted data...\n\n";

$created_count = 0;
$batch_size = 50;

for ($i = 1; $i <= 300; $i++) {
  // Generate random data.
  $first_name = $first_names[array_rand($first_names)];
  $last_name = $last_names[array_rand($last_names)];
  $full_name = $first_name . ' ' . $last_name;

  $username = strtolower($first_name . $last_name . rand(100, 999));
  $email = strtolower($first_name . '.' . $last_name . rand(1, 99)) . '@' . $domains[array_rand($domains)];

  $note_prefix = $note_prefixes[array_rand($note_prefixes)];
  $note_suffix = $note_suffixes[array_rand($note_suffixes)];
  $notes = $note_prefix . ' ' . $note_suffix . '. Created on ' . date('Y-m-d H:i:s');

  // Create entity.
  $entity = $storage->create([
    'type' => 'type_1',
    'user_id' => $user_id,
    'name' => $full_name,
    'email' => $email,
    'username' => $username,
    'notes' => $notes,
    'status' => 1,
  ]);

  $entity->save();
  $created_count++;

  // Show progress every 50 entities.
  if ($i % $batch_size == 0) {
    echo "Created $i entities...\n";
  }
}

echo "\n✓ Successfully created $created_count entities!\n";
echo "\nSample queries you can run:\n";
echo "- Filter by name containing 'Smith':\n";
echo "  GET /jsonapi/user_confidential_data/user_confidential_data?filter[name][operator]=CONTAINS&filter[name][value]=Smith\n\n";
echo "- Filter by notes containing 'Password':\n";
echo "  GET /jsonapi/user_confidential_data/user_confidential_data?filter[notes][operator]=CONTAINS&filter[notes][value]=Password\n\n";
echo "- Filter by notes containing 'Private data':\n";
echo "  GET /jsonapi/user_confidential_data/user_confidential_data?filter[notes][operator]=CONTAINS&filter[notes][value]=Private data\n\n";
echo "- Filter by email domain:\n";
echo "  GET /jsonapi/user_confidential_data/user_confidential_data?filter[email][operator]=CONTAINS&filter[email][value]=example.com\n\n";

// Show some statistics.
echo "\nStatistics:\n";
echo "- Total entities created: $created_count\n";

$all_entities = $storage->loadMultiple();
echo "- Total entities in database: " . count($all_entities) . "\n";

// Test some filters.
echo "\nTesting filters:\n";

$query1 = $storage->getQuery()
  ->condition('notes', 'Password', 'CONTAINS')
  ->accessCheck(FALSE);
$result1 = $query1->execute();
echo "- Entities with 'Password' in notes: " . count($result1) . "\n";

$query2 = $storage->getQuery()
  ->condition('notes', 'Private data', 'CONTAINS')
  ->accessCheck(FALSE);
$result2 = $query2->execute();
echo "- Entities with 'Private data' in notes: " . count($result2) . "\n";

$query3 = $storage->getQuery()
  ->condition('notes', 'Confidential', 'CONTAINS')
  ->accessCheck(FALSE);
$result3 = $query3->execute();
echo "- Entities with 'Confidential' in notes: " . count($result3) . "\n";

$query4 = $storage->getQuery()
  ->condition('email', 'example.com', 'CONTAINS')
  ->accessCheck(FALSE);
$result4 = $query4->execute();
echo "- Entities with 'example.com' email: " . count($result4) . "\n";

echo "\n✓ All done!\n";
