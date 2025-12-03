# Postman Collection Guide

## Overview

This guide explains how to use the provided Postman collection to test the Data Processing Service API endpoints manually.

## Setup Instructions

### Step 1: Import the Collection

1. Open **Postman**
2. Click **Import** (top left)
3. Select **Upload Files**
4. Choose `postman_collection.json` from the project root
5. Click **Import**

The collection will appear in your **Collections** sidebar with all pre-configured requests.

### Step 2: Verify Base URL

The collection uses `http://localhost:8080` as the default base URL.

**To verify/change**:
1. Select the collection → **Variables** tab
2. Check `base_url` is set to `http://localhost:8080`
3. Adjust if needed (e.g., if using a different port)

### Step 3: Start the Service

Ensure the application is running:
```bash
make up          # Start Docker containers
make migrate     # Run database migrations
```

Verify services are running:
```bash
make status
```

All 4 containers should show "Up":
- data-processing-app
- data-processing-db
- data-processing-nginx
- data-processing-redis

## Collection Structure

The collection is organized into 5 folders for easy navigation:

### 📥 **Data Ingestion** (5 requests)

Tests for creating/storing records with various scenarios:

1. **Create New Record**
   - Creates a fresh record
   - Expected: 201 Created
   - Response includes the stored record details

2. **Create Another Record (Different ID)**
   - Creates a second record with different `recordId`
   - Expected: 201 Created
   - Different destination, same reference

3. **Create Record - Different Destination**
   - Tests records with different destination ID
   - Expected: 201 Created
   - Negative transaction type

4. **Duplicate Record (Idempotent - Should Return 200)**
   - Sends exact same record as request #1
   - Expected: 200 OK with `"isDuplicate": true`
   - **Key test**: Verifies idempotency implementation

5. **High Value Record (Above Alert Threshold)**
   - Record with value 1500.00 (exceeds 1000.00 threshold)
   - Expected: 201 Created
   - Should trigger SendAlertJob in the background

### ❌ **Validation Errors** (4 requests)

Tests error handling and validation:

1. **ERROR: Missing Required Fields**
   - Request with only `recordId`
   - Expected: 422 Unprocessable Entity
   - Response includes validation errors for all missing fields

2. **ERROR: Invalid Type**
   - Uses `type: "invalid-type"` instead of positive/negative
   - Expected: 422 Unprocessable Entity
   - Validation error for type field

3. **ERROR: Invalid DateTime Format**
   - Uses `time: "invalid-date"` instead of proper format
   - Expected: 422 Unprocessable Entity
   - Validation error for time field

4. **ERROR: Invalid Decimal Value**
   - Uses `value: "100.999"` (3 decimal places, max is 2)
   - Expected: 422 Unprocessable Entity
   - Validation error for decimal precision

### 📊 **Aggregation Queries** (7 requests)

Tests the aggregation endpoint with various filter combinations:

1. **Get All Records (No Filters)**
   - Retrieves all stored records
   - Expected: 200 OK
   - Includes all records and groups

2. **Filter by Type: Positive**
   - Query parameter: `type=positive`
   - Expected: 200 OK
   - Only returns positive transactions

3. **Filter by Type: Negative**
   - Query parameter: `type=negative`
   - Expected: 200 OK
   - Only returns negative transactions

4. **Filter by Time Range**
   - Query parameters: `startTime=2025-01-01 10:00:00&endTime=2025-01-01 12:00:00`
   - Expected: 200 OK
   - Only returns records within time window

5. **Filter by Type AND Time Range**
   - Combines type and time filters
   - Query: `type=positive&startTime=...&endTime=...`
   - Expected: 200 OK
   - Demonstrates combining multiple filters

6. **Only Start Time**
   - Query parameter: `startTime=2025-01-01 11:30:00`
   - Expected: 200 OK
   - Returns records from start time onwards

7. **Only End Time**
   - Query parameter: `endTime=2025-01-01 11:00:00`
   - Expected: 200 OK
   - Returns records up to end time

### ❌ **Aggregation - Validation Errors** (1 request)

1. **ERROR: Invalid Type Filter**
   - Query parameter: `type=invalid`
   - Expected: 422 Unprocessable Entity
   - Validates query parameters too

### ⚡ **Load Testing** (5 requests)

Tests with bulk data creation:

1-5. **Bulk Create - Record Sets 1-5**
   - Creates records with bulk source and different destinations
   - Each returns 201 Created
   - Use these to build up data for aggregation testing

## Testing Workflow

### Quick Start (5 minutes)

1. **Create Basic Records**
   - Run: Data Ingestion → Create New Record
   - Run: Data Ingestion → Create Another Record (Different ID)
   - Run: Data Ingestion → Create Record - Different Destination
   - Observe 201 responses

2. **Test Idempotency**
   - Run: Data Ingestion → Duplicate Record
   - Observe 200 response with `"isDuplicate": true`

3. **Query Aggregation**
   - Run: Aggregation Queries → Get All Records (No Filters)
   - See all 3 records grouped by destination

### Comprehensive Testing (15 minutes)

1. **Test All Data Ingestion Scenarios**
   - Execute all 5 requests in Data Ingestion folder
   - Verify each returns expected status code

2. **Test Validation Errors**
   - Execute all 4 validation error requests
   - Verify 422 responses with proper error messages

3. **Test Aggregation with Filters**
   - Execute all 7 aggregation queries
   - Try different combinations of filters
   - Observe how grouping by destination changes

4. **Load Data and Aggregate**
   - Run all 5 Bulk Create requests
   - Then run aggregation queries
   - See how totals change with more data

5. **Test Error Cases**
   - Run invalid type filter
   - Verify error handling

## Expected Response Examples

### Successful Record Creation (201)

```json
{
  "message": "Record processed successfully",
  "data": {
    "id": 1,
    "recordId": "rec-001",
    "time": "2025-01-01T10:00:00.000000Z",
    "sourceId": "source-001",
    "destinationId": "customer-001",
    "type": "positive",
    "value": "1500.50",
    "unit": "EUR",
    "reference": "order-001",
    "isDuplicate": false
  }
}
```

### Duplicate Record (200 Idempotent)

```json
{
  "message": "Record already processed (idempotent response)",
  "data": {
    "id": 1,
    "recordId": "rec-001",
    "isDuplicate": true,
    ...
  }
}
```

### Aggregation Query (200)

```json
{
  "message": "Records aggregated successfully",
  "data": {
    "count": 3,
    "records": [...],
    "groups": [
      {
        "destinationId": "customer-001",
        "recordCount": 2,
        "totalValue": "2250.75"
      },
      {
        "destinationId": "customer-002",
        "recordCount": 1,
        "totalValue": "-250.00"
      }
    ]
  }
}
```

### Validation Error (422)

```json
{
  "message": "The time field is required.",
  "errors": {
    "time": ["The time field is required."],
    "sourceId": ["The sourceId field is required."],
    ...
  }
}
```

## Tips & Tricks

### 1. Use Postman Environment Variables

To make requests more flexible, you could create environment variables:

1. Create new environment (top right)
2. Add variables:
   - `base_url`: http://localhost:8080
   - `timestamp`: current timestamp
   - `record_id`: unique ID

Example in request:
```
POST {{base_url}}/api/records
Body: {"recordId": "{{record_id}}", ...}
```

### 2. Create Test Scripts

Add validation scripts to requests (Tests tab):

```javascript
// Example: Validate 201 status
pm.test("Status code is 201", function () {
    pm.response.to.have.status(201);
});

// Example: Validate isDuplicate field
pm.test("Response has isDuplicate field", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data).to.have.property('isDuplicate');
});
```

### 3. Use Pre-request Scripts

Generate dynamic data before sending requests:

```javascript
// Generate unique recordId
var recordId = "rec-" + Math.random().toString(36).substring(7);
pm.environment.set("recordId", recordId);
```

### 4. Monitor Network Performance

- **Timing**: Postman shows request duration at bottom
- **Expected**: ~50-70ms for POST, ~10-30ms for GET
- Compare actual vs expected

### 5. Export Results

1. Right-click collection → **Run collection**
2. Execute all requests in sequence
3. Export results as HTML report

## Common Issues & Solutions

### Issue: "Connection refused" Error

**Cause**: Application not running

**Solution**:
```bash
make up          # Start containers
make migrate     # Run migrations
make status      # Verify all running
```

### Issue: 404 Not Found

**Cause**: Wrong URL or missing base URL variable

**Solution**:
1. Check collection Variables
2. Verify `base_url` is `http://localhost:8080`
3. Check if Nginx is running: `make status`

### Issue: 422 Validation Errors on Valid Request

**Cause**: Field format mismatch

**Solution**:
- Time format: `YYYY-MM-DD HH:MM:SS`
- Type: exactly `positive` or `negative`
- Value: decimal with max 2 places (e.g., `100.50`)
- All other fields: strings

### Issue: Duplicate Records Keep Returning 201

**Cause**: Each request has unique `recordId`

**Solution**: Use exact same `recordId` to test idempotency. For example:
- Request 1: `recordId: "rec-001"`
- Request 2 (duplicate): `recordId: "rec-001"` (same!)

## Database Inspection

While testing, you can inspect the database:

```bash
# Access database shell
make db-shell

# View all records
SELECT * FROM records;

# Count by destination
SELECT destination_id, COUNT(*) as count, SUM(value) as total
FROM records
GROUP BY destination_id;

# Exit
\q
```

## Performance Baseline

Use Postman's built-in timing to verify performance:

**Expected Response Times**:
- POST /api/records: 50-70ms
- GET /api/records/aggregate: 10-30ms

**If slower**:
1. Check Docker resource allocation
2. Verify database is responsive: `make db-shell`
3. Check application logs: `make logs service=app`

## Next Steps After Testing

Once you've verified all endpoints work:

1. ✅ API functionality verified
2. ✅ Validation working correctly
3. ✅ Idempotency working
4. ✅ Aggregation queries accurate
5. Ready to present/deploy! 🚀

---

**Happy Testing!** 🎉

For questions or issues, refer to:
- API_EXAMPLES.md (detailed usage examples)
- DESIGN.md (architecture & design decisions)
- API console logs: `make logs service=app`
