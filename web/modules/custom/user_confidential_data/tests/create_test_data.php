<?php

/**
 * @file
 * Script to create 250 test entities with realistic encrypted data.
 *
 * Usage: drush php:script web/modules/custom/user_confidential_data/tests/create_test_data.php
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
  'Kevin', 'Emma', 'Brian', 'Donna', 'George', 'Michelle', 'Timothy', 'Carol',
  'Ronald', 'Amanda', 'Edward', 'Melissa', 'Jason', 'Deborah', 'Jeffrey', 'Stephanie',
  'Ryan', 'Rebecca', 'Jacob', 'Laura', 'Gary', 'Sharon', 'Nicholas', 'Cynthia',
  'Eric', 'Kathleen', 'Jonathan', 'Amy', 'Stephen', 'Angela', 'Larry', 'Shirley',
  'Justin', 'Anna', 'Scott', 'Brenda', 'Brandon', 'Pamela', 'Benjamin', 'Nicole'
];

$last_names = [
  'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
  'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson',
  'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Thompson', 'White',
  'Harris', 'Clark', 'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King',
  'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores', 'Green', 'Adams',
  'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell', 'Carter', 'Roberts',
  'Phillips', 'Evans', 'Turner', 'Parker', 'Collins', 'Edwards', 'Stewart', 'Morris',
  'Rogers', 'Reed', 'Cook', 'Morgan', 'Bell', 'Murphy', 'Bailey', 'Cooper',
  'Richardson', 'Cox', 'Howard', 'Ward', 'Peterson', 'Gray', 'Ramirez', 'James'
];

$service_types = [
  'Banking', 'Email', 'Social Media', 'Work Portal', 'Cloud Storage',
  'VPN', 'Database', 'Admin Panel', 'Payment Gateway', 'API',
  'FTP', 'SSH', 'Remote Desktop', 'Backup System', 'Monitoring',
  'Development', 'Production', 'Staging', 'CRM', 'File Server',
  'Mail Server', 'Web Hosting', 'Domain Registrar', 'SSL Certificate',
  'Code Repository', 'Project Management', 'Communication', 'Accounting',
  'Inventory', 'Support System', 'Analytics', 'Marketing Platform',
  'E-commerce', 'Content Management', 'Customer Portal', 'Employee Portal',
  'Learning Management', 'Time Tracking', 'Invoicing', 'HR System'
];

$companies = [
  'Acme Corp', 'TechVentures Inc', 'Global Systems', 'DataFlow Solutions',
  'CloudNet Services', 'SecureBase', 'WebPro Technologies', 'Digital Dynamics',
  'InfoTech Group', 'NetWorks LLC', 'CyberSafe Inc', 'SmartSystems Co',
  'Innovate Digital', 'Premier Solutions', 'NextGen Tech', 'Elite Services',
  'ProActive Systems', 'Quantum Networks', 'Velocity Digital', 'Zenith Corp',
  'Apex Technologies', 'Fusion Systems', 'Horizon Digital', 'Pinnacle Group',
  'Summit Solutions', 'Vertex Technologies', 'Catalyst Systems', 'Nexus Corp',
  'Optimal Services', 'Prime Networks', 'Dynamic Solutions', 'Stellar Systems',
  'Unity Technologies', 'Vanguard Digital', 'Omega Services', 'Alpha Corp'
];

$domains = [
  'example.com', 'test.com', 'demo.org', 'sample.net', 'mysite.com',
  'webapp.io', 'services.net', 'platform.org', 'systems.com', 'portal.net',
  'secure.com', 'private.org', 'confidential.net', 'encrypted.com', 'protected.org',
  'business.com', 'enterprise.net', 'corporate.com', 'company.io', 'digital.tech',
  'cloud.services', 'web.app', 'online.store', 'tech.solutions', 'data.systems'
];

$password_patterns = [
  ['prefix' => 'Pass', 'suffix' => '!'],
  ['prefix' => 'Secure', 'suffix' => '@123'],
  ['prefix' => 'Admin', 'suffix' => '#2024'],
  ['prefix' => 'Super', 'suffix' => '$ecret'],
  ['prefix' => 'My', 'suffix' => '&Pass'],
  ['prefix' => 'Strong', 'suffix' => '*Key'],
  ['prefix' => 'Safe', 'suffix' => '!@#'],
  ['prefix' => 'Secret', 'suffix' => '2024'],
];

$url_paths = [
  '/admin/dashboard', '/login', '/portal', '/app/main', '/secure/access',
  '/dashboard', '/panel', '/console', '/admin', '/manager',
  '/account', '/profile', '/settings', '/api/v1', '/api/v2',
  '/backend', '/cms', '/control', '/manage', '/system'
];

$url_protocols = ['https://www.', 'https://', 'http://'];

echo "Creating 250 test entities with realistic encrypted data...\n\n";

$created_count = 0;
$batch_size = 50;

for ($i = 1; $i <= 250; $i++) {
  // Generate random data.
  $first_name = $first_names[array_rand($first_names)];
  $last_name = $last_names[array_rand($last_names)];
  $full_name = $first_name . ' ' . $last_name;

  // Generate realistic username variations
  $username_patterns = [
    strtolower($first_name . $last_name . rand(10, 99)),
    strtolower($first_name[0] . $last_name . rand(100, 999)),
    strtolower($first_name . '.' . $last_name),
    strtolower($first_name . '_' . $last_name . rand(1, 50)),
    strtolower($last_name . $first_name[0] . rand(10, 99)),
  ];
  $username = $username_patterns[array_rand($username_patterns)];

  // Generate email
  $email = strtolower($first_name . '.' . $last_name . rand(1, 99)) . '@' . $domains[array_rand($domains)];

  // Generate realistic password
  $password_pattern = $password_patterns[array_rand($password_patterns)];
  $password = $password_pattern['prefix'] . rand(1000, 9999) . $password_pattern['suffix'];

  // Generate realistic link/URL
  $protocol = $url_protocols[array_rand($url_protocols)];
  $domain = $domains[array_rand($domains)];
  $path = $url_paths[array_rand($url_paths)];
  $link = $protocol . $domain . $path;

  // Generate realistic notes
  $service_type = $service_types[array_rand($service_types)];
  $company = $companies[array_rand($companies)];

  $note_templates = [
    "Login credentials for $company $service_type account. Last updated: " . date('Y-m-d', strtotime('-' . rand(1, 365) . ' days')),
    "Access details for $service_type at $company. Password expires in " . rand(30, 180) . " days.",
    "$company $service_type - Two-factor authentication enabled. Backup codes stored securely.",
    "Personal $service_type account for $company. Contact: support@$domain for assistance.",
    "$service_type credentials - $company. Auto-renewal enabled. Billing on " . date('M d', strtotime('+' . rand(1, 60) . ' days')),
    "Primary $service_type access for $company operations. VPN required for external access.",
    "$company $service_type portal. SSO enabled. Use company email for login.",
    "Temporary $service_type credentials for $company. Valid until " . date('Y-m-d', strtotime('+' . rand(30, 90) . ' days')),
    "$service_type admin access - $company. Requires IP whitelisting and MFA verification.",
    "Shared $service_type account for $company team. Password rotation: quarterly.",
  ];

  $notes = $note_templates[array_rand($note_templates)];

  // Create entity with all fields.
  $entity = $storage->create([
    'type' => 'type_1',
    'user_id' => $user_id,
    'name' => "$company - $service_type",
    'email' => $email,
    'username' => $username,
    'password' => $password,
    'link' => $link,
    'notes' => $notes,
  ]);

  $entity->save();
  $created_count++;

  // Show progress every 50 entities.
  if ($i % $batch_size == 0) {
    echo "Created $i entities...\n";
  }
}

echo "\n✓ Successfully created $created_count entities!\n";

// Show some statistics.
echo "\n📊 Statistics:\n";
echo "- Total entities created: $created_count\n";

$all_entities = $storage->loadMultiple();
echo "- Total entities in database: " . count($all_entities) . "\n";

// Test some filters.
echo "\n🔍 Testing encrypted field searches:\n";

$query1 = $storage->getQuery()
  ->condition('notes', 'Banking', 'CONTAINS')
  ->accessCheck(FALSE);
$result1 = $query1->execute();
echo "- Entities with 'Banking' in notes: " . count($result1) . "\n";

$query2 = $storage->getQuery()
  ->condition('notes', 'Admin', 'CONTAINS')
  ->accessCheck(FALSE);
$result2 = $query2->execute();
echo "- Entities with 'Admin' in notes: " . count($result2) . "\n";

$query3 = $storage->getQuery()
  ->condition('email', 'example.com', 'CONTAINS')
  ->accessCheck(FALSE);
$result3 = $query3->execute();
echo "- Entities with 'example.com' email: " . count($result3) . "\n";

$query4 = $storage->getQuery()
  ->condition('name', 'Corp', 'CONTAINS')
  ->accessCheck(FALSE);
$result4 = $query4->execute();
echo "- Entities with 'Corp' in name: " . count($result4) . "\n";

echo "\n📝 Sample API queries:\n";
echo "- Filter by name containing 'Banking':\n";
echo "  GET /jsonapi/user_confidential_data/user_confidential_data?filter[name][operator]=CONTAINS&filter[name][value]=Banking\n\n";
echo "- Filter by email domain:\n";
echo "  GET /jsonapi/user_confidential_data/user_confidential_data?filter[email][operator]=CONTAINS&filter[email][value]=example.com\n\n";
echo "- Filter by notes containing 'VPN':\n";
echo "  GET /jsonapi/user_confidential_data/user_confidential_data?filter[notes][operator]=CONTAINS&filter[notes][value]=VPN\n\n";

// Show sample entities
echo "\n🔐 Sample encrypted data (first 3 entities):\n";
$sample_entities = array_slice($all_entities, 0, 3, true);
foreach ($sample_entities as $sample) {
  echo "\nID: " . $sample->id() . "\n";
  echo "  Name: " . $sample->get('name')->value . "\n";
  echo "  Username: " . $sample->get('username')->value . "\n";
  echo "  Email: " . $sample->get('email')->value . "\n";
  echo "  Link: " . $sample->get('link')->value . "\n";
  echo "  Password: " . substr($sample->get('password')->value, 0, 4) . "****\n";
  echo "  Notes: " . substr($sample->get('notes')->value, 0, 50) . "...\n";
}

echo "\n✅ All done! Ready for backend testing.\n";
