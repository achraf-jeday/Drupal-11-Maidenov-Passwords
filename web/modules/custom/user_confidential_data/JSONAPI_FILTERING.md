# JSON:API Filtering for Encrypted Fields

This module now supports filtering on encrypted fields through JSON:API. The encrypted fields (`name`, `email`, `username`, and `notes`) are automatically decrypted before filtering is applied.

## How It Works

The filtering mechanism works as follows:

1. **Custom Query Class**: A custom entity query class (`\Drupal\user_confidential_data\Entity\Query\Sql\Query`) intercepts queries for `user_confidential_data` entities.

2. **Condition Extraction**: When encrypted field conditions are detected, they are:
   - Extracted from the main query
   - Removed from the database query (since filtering on encrypted values won't work)

3. **Load and Decrypt**: The query executes without the encrypted field conditions, loading all entities that match the non-encrypted criteria.

4. **Post-Load Filtering**: The loaded entities are automatically decrypted by the storage handler, and then filtered in memory based on the decrypted values.

## Supported Encrypted Fields

The following fields support filtering:

- `name` - String field
- `email` - Email field
- `username` - String field
- `notes` - String long field

## JSON:API Filter Examples

### Basic Equality Filter

Filter by exact name match:
```
GET /jsonapi/user_confidential_data/user_confidential_data?filter[name]=John Doe
```

### Email Filter

Filter by email address:
```
GET /jsonapi/user_confidential_data/user_confidential_data?filter[email]=john@example.com
```

### Username Filter

Filter by username:
```
GET /jsonapi/user_confidential_data/user_confidential_data?filter[username]=johndoe
```

### Notes Filter

Filter by notes content:
```
GET /jsonapi/user_confidential_data/user_confidential_data?filter[notes][operator]=CONTAINS&filter[notes][value]=important
```

### Multiple Filters

Combine multiple encrypted field filters:
```
GET /jsonapi/user_confidential_data/user_confidential_data?filter[name]=John Doe&filter[email]=john@example.com
```

### Mix with Non-Encrypted Filters

Combine encrypted field filters with regular field filters:
```
GET /jsonapi/user_confidential_data/user_confidential_data?filter[status]=1&filter[name]=John Doe
```

## Supported Operators

The following operators are supported for encrypted field filtering:

- `=` (equals) - Default operator
- `<>`, `!=` (not equals)
- `>` (greater than)
- `>=` (greater than or equal)
- `<` (less than)
- `<=` (less than or equal)
- `CONTAINS` - For substring matching
- `STARTS_WITH` - For prefix matching
- `ENDS_WITH` - For suffix matching
- `IN` - For matching against an array of values
- `NOT IN` - For excluding an array of values
- `IS NULL` - For checking if field is empty
- `IS NOT NULL` - For checking if field is not empty

### Operator Examples

**CONTAINS operator:**
```
GET /jsonapi/user_confidential_data/user_confidential_data?filter[name][operator]=CONTAINS&filter[name][value]=John
```

**STARTS_WITH operator:**
```
GET /jsonapi/user_confidential_data/user_confidential_data?filter[email][operator]=STARTS_WITH&filter[email][value]=admin
```

**ENDS_WITH operator:**
```
GET /jsonapi/user_confidential_data/user_confidential_data?filter[email][operator]=ENDS_WITH&filter[email][value]=@example.com
```

## Performance Considerations

Since encrypted field filtering happens in memory after entities are loaded:

1. **Load All First**: Queries with only encrypted field conditions will load ALL entities from the database before filtering.

2. **Optimization**: To improve performance, combine encrypted field filters with non-encrypted field filters to reduce the initial dataset:
   ```
   # Good - limits initial load by user_id
   filter[user_id]=123&filter[name]=John Doe

   # Less optimal - loads all entities
   filter[name]=John Doe
   ```

3. **Pagination**: Use pagination to limit the number of entities loaded:
   ```
   ?filter[name]=John&page[limit]=10&page[offset]=0
   ```

## Technical Implementation

### Files Modified/Created

1. **Storage Handler** (`src/Storage/UserConfidentialDataStorage.php`):
   - Added `loadByProperties()` method to handle encrypted field conditions
   - Added `filterByEncryptedFields()` helper method

2. **Custom Query Class** (`src/Entity/Query/Sql/Query.php`):
   - Overrides `execute()` to extract and handle encrypted field conditions
   - Implements post-load filtering logic

3. **Query Factory** (`src/Entity/Query/Sql/QueryFactory.php`):
   - Factory to create custom Query instances

4. **Services** (`user_confidential_data.services.yml`):
   - Registered query factory as `entity.query.sql.user_confidential_data`
   - Tagged with `entity.query_factory` for the `user_confidential_data` entity type

### Service Registration

The custom query factory is registered in `user_confidential_data.services.yml`:

```yaml
entity.query.sql.user_confidential_data:
  class: Drupal\user_confidential_data\Entity\Query\Sql\QueryFactory
  arguments: ['@database']
  tags:
    - { name: entity.query_factory, entity_type: user_confidential_data }
```

## Testing

To test the filtering functionality:

1. **Create test entities** with encrypted data
2. **Query via JSON:API** using the filter examples above
3. **Verify results** match the expected decrypted values

Example test request:
```bash
curl -X GET "https://your-site.com/jsonapi/user_confidential_data/user_confidential_data?filter[name]=Test%20User" \
  -H "Accept: application/vnd.api+json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Debugging

To debug filtering issues:

1. **Check Drupal logs**: Look for encryption/decryption errors
2. **Verify encryption key**: Ensure the encryption key is properly configured
3. **Test decryption**: Load an entity directly and verify fields are decrypted
4. **Query without filters**: Confirm entities load correctly without filters

## Limitations

1. **Case Sensitivity**: String comparisons are case-insensitive by default
2. **Performance**: Large datasets may experience slower query times due to in-memory filtering
3. **Complex Queries**: Nested condition groups may have limited support
4. **Aggregate Queries**: Aggregate queries on encrypted fields are not fully supported

## Future Enhancements

Potential improvements for future versions:

- Implement result caching for frequently used filters
- Add support for more complex query patterns
- Optimize memory usage for large datasets
- Add query profiling and performance metrics
