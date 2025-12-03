# Testing Guide - Complete Instructions

## Overview

This guide walks you through testing the Data Processing Service API using Postman. The collection includes 22 pre-configured requests organized by functionality.

---

## Part 1: Setup & Configuration

### Step 1.1: Install Postman (if needed)

Download from: https://www.postman.com/downloads/

### Step 1.2: Import Collection

1. Open **Postman**
2. Click the **Import** button (top left area)
3. Select **Upload Files** tab
4. Browse to `/Users/mihai/Projects/bilogram-interview/postman_collection.json`
5. Click **Import**

You should see "Data Processing Service API" in your Collections

### Step 1.3: Start the Application

Open terminal in project root:

```bash
# Start all containers
make up

# Run migrations
make migrate

# Verify everything is running
make status
```

Expected output:
```
NAME                    STATUS
data-processing-app     Up
data-processing-db      Up
data-processing-nginx   Up
data-processing-redis   Up
```

### Step 1.4: Verify Collection Variables

1. In Postman, select **Data Processing Service API** collection
2. Click **Variables** tab
3. Verify `base_url` is set to `http://localhost:8080`
4. Save if you made changes

---

## Part 2: Test Execution

### Phase 1: Data Ingestion (Warm-up)

**Objective**: Create test data and verify record storage

**Expected Time**: 3 minutes

#### Test 1.1: Create First Record
- **Collection**: Data Ingestion → 1. Create New Record
- **Click**: Send
- **Expected**:
  - Status: **201 Created**
  - Response contains `"isDuplicate": false`
  - Response includes auto-generated `id` field
  - `recordId` is "rec-001"

#### Test 1.2: Create Second Record
- **Collection**: Data Ingestion → 2. Create Another Record (Different ID)
- **Click**: Send
- **Expected**:
  - Status: **201 Created**
  - Different `id` than first record
  - `recordId` is "rec-002"

#### Test 1.3: Create Third Record
- **Collection**: Data Ingestion → 3. Create Record - Different Destination
- **Click**: Send
- **Expected**:
  - Status: **201 Created**
  - `destinationId` is "customer-002" (different from previous)
  - `type` is "negative"

**✅ Milestone 1 Achieved**: You've created 3 records with different characteristics

---

### Phase 2: Idempotency Testing (Critical!)

**Objective**: Verify duplicate records return 200, not 201

**Expected Time**: 2 minutes

#### Test 2.1: Send Duplicate Request
- **Collection**: Data Ingestion → 4. Duplicate Record (Idempotent - Should Return 200)
- **Note**: This is the SAME data as Test 1.1
- **Click**: Send
- **Expected**:
  - Status: **200 OK** (NOT 201!)
  - Response contains `"isDuplicate": true`
  - Same `id` as the original record (database didn't create new entry)
  - Message: "Record already processed (idempotent response)"

**🎯 Key Verification**:
- First request with same recordId → 201
- Second request with same recordId → 200
- **This proves idempotency is working!**

---

### Phase 3: Alert Threshold Testing

**Objective**: Test records that exceed alert threshold (1000.00)

**Expected Time**: 1 minute

#### Test 3.1: Create High-Value Record
- **Collection**: Data Ingestion → 5. High Value Record (Above Alert Threshold)
- **Record details**:
  - `recordId`: "rec-alert-001"
  - `value`: 1500.00 (exceeds 1000.00 threshold)
- **Click**: Send
- **Expected**:
  - Status: **201 Created**
  - Record stored successfully
  - (In background: SendAlertJob dispatched to queue)

**📝 Note**: The alert job runs asynchronously. You won't see a response change, but the job was queued.

---

### Phase 4: Validation Error Testing

**Objective**: Verify validation and error handling

**Expected Time**: 4 minutes

#### Test 4.1: Missing Required Fields
- **Collection**: Validation Errors → ERROR: Missing Required Fields
- **Request data**: Only `recordId` provided
- **Click**: Send
- **Expected**:
  - Status: **422 Unprocessable Entity**
  - Response includes `errors` object
  - Lists all missing fields: `time`, `sourceId`, `destinationId`, `type`, `value`, `unit`, `reference`

**Verify errors array contains these fields**:
```json
{
  "errors": {
    "time": ["The time field is required."],
    "sourceId": ["The sourceId field is required."],
    ...
  }
}
```

#### Test 4.2: Invalid Type
- **Collection**: Validation Errors → ERROR: Invalid Type
- **Request data**: `type: "invalid-type"`
- **Click**: Send
- **Expected**:
  - Status: **422 Unprocessable Entity**
  - Error message: includes valid values (positive|negative)
  - Only `type` field in errors object

#### Test 4.3: Invalid DateTime Format
- **Collection**: Validation Errors → ERROR: Invalid DateTime Format
- **Request data**: `time: "invalid-date"`
- **Click**: Send
- **Expected**:
  - Status: **422 Unprocessable Entity**
  - Error message: includes expected format (Y-m-d H:i:s)

#### Test 4.4: Invalid Decimal Value
- **Collection**: Validation Errors → ERROR: Invalid Decimal Value
- **Request data**: `value: "100.999"` (3 decimal places)
- **Click**: Send
- **Expected**:
  - Status: **422 Unprocessable Entity**
  - Error message: mentions max decimal places

**✅ Milestone 2 Achieved**: All validation is working correctly

---

### Phase 5: Aggregation Queries - No Filters

**Objective**: Test basic aggregation and grouping

**Expected Time**: 3 minutes

#### Test 5.1: Get All Records
- **Collection**: Aggregation Queries → 1. Get All Records (No Filters)
- **Click**: Send
- **Expected**:
  - Status: **200 OK**
  - `data.count`: 3 (the 3 records you created in Phase 1)
  - `data.records`: Array with 3 records
  - `data.groups`: Array with 2 groups (one for each destination)

**Check groups array**:
```json
{
  "groups": [
    {
      "destinationId": "customer-001",
      "recordCount": 2,
      "totalValue": "2250.75"  // Sum of rec-001 (1500.50) + rec-002 (750.25)
    },
    {
      "destinationId": "customer-002",
      "recordCount": 1,
      "totalValue": "-250"  // rec-003 value
    }
  ]
}
```

**🎯 Key Verification**:
- Count is correct (3 records created)
- Grouping by destinationId is correct
- TotalValue calculation is correct (sums values per destination)

---

### Phase 6: Aggregation Queries - Type Filter

**Objective**: Test filtering by positive/negative

**Expected Time**: 2 minutes

#### Test 6.1: Filter Type = Positive
- **Collection**: Aggregation Queries → 2. Filter by Type: Positive
- **Query Parameter**: `type=positive`
- **Click**: Send
- **Expected**:
  - Status: **200 OK**
  - `data.count`: 2 (only rec-001 and rec-002)
  - `data.records`: Only records with `type: "positive"`
  - `data.groups`: Should show only customer-001 (both positive records)
  - `totalValue`: 2250.75 (sum of positive records for that destination)

#### Test 6.2: Filter Type = Negative
- **Collection**: Aggregation Queries → 3. Filter by Type: Negative
- **Query Parameter**: `type=negative`
- **Click**: Send
- **Expected**:
  - Status: **200 OK**
  - `data.count`: 1 (only rec-003)
  - `data.records`: Only records with `type: "negative"`
  - `data.groups`: Only customer-002
  - `totalValue`: -250

**✅ Milestone 3 Achieved**: Filtering by type works correctly

---

### Phase 7: Aggregation Queries - Time Filtering

**Objective**: Test time-based filtering

**Expected Time**: 2 minutes

#### Test 7.1: Time Range Filter
- **Collection**: Aggregation Queries → 4. Filter by Time Range
- **Query Parameters**:
  - `startTime=2025-01-01 10:00:00`
  - `endTime=2025-01-01 12:00:00`
- **Click**: Send
- **Expected**:
  - Status: **200 OK**
  - `data.count`: 2 (rec-001 at 10:00 and rec-002 at 11:00 fall within range)
  - Excludes rec-003 (at 12:00 is boundary - check if inclusive)
  - Excludes rec-alert-001 (at 13:00 is outside range)

#### Test 7.2: Combined Filters
- **Collection**: Aggregation Queries → 5. Filter by Type AND Time Range
- **Query Parameters**:
  - `type=positive`
  - `startTime=2025-01-01 10:00:00`
  - `endTime=2025-01-01 14:00:00`
- **Click**: Send
- **Expected**:
  - Status: **200 OK**
  - `data.count`: 3 (rec-001, rec-002 are positive in range; rec-alert-001 is also positive in range)
  - Groups correctly by destination
  - TotalValues reflect only filtered data

---

### Phase 8: Load Testing - Bulk Data Creation

**Objective**: Create more data for comprehensive aggregation testing

**Expected Time**: 2 minutes

#### Execute All 5 Bulk Create Requests

Run each of these in sequence:
- Data Ingestion → Bulk Create - Record Set 1
- Data Ingestion → Bulk Create - Record Set 2
- Data Ingestion → Bulk Create - Record Set 3
- Data Ingestion → Bulk Create - Record Set 4
- Data Ingestion → Bulk Create - Record Set 5

**Expected**: Each returns **201 Created**

**After completion**: You now have 8 total records (3 from Phase 1 + 5 bulk)

---

### Phase 9: Aggregation on Bulk Data

**Objective**: Verify aggregation scales with more data

**Expected Time**: 2 minutes

#### Test 9.1: Get All Records (After Bulk Load)
- **Collection**: Aggregation Queries → 1. Get All Records (No Filters)
- **Click**: Send
- **Expected**:
  - Status: **200 OK**
  - `data.count`: 8 (3 + 5)
  - `data.groups`: Now 4 groups (bulk-dest-1, bulk-dest-2, customer-001, customer-002)
  - All totals calculated correctly

#### Test 9.2: Verify Grouping Accuracy
Look at the response and verify:
```
bulk-dest-1:
  - Count: 3 records (bulk-001, bulk-002, bulk-005)
  - Total: 500 + 350.75 + 650.25 = 1501.00

bulk-dest-2:
  - Count: 2 records (bulk-003, bulk-004)
  - Total: -100 + 800.50 = 700.50
```

---

### Phase 10: Error Handling in Aggregation

**Objective**: Test error scenarios in query API

**Expected Time**: 1 minute

#### Test 10.1: Invalid Type Filter
- **Collection**: Aggregation - Validation Errors → ERROR: Invalid Type Filter
- **Query Parameter**: `type=unknown`
- **Click**: Send
- **Expected**:
  - Status: **422 Unprocessable Entity**
  - Error message: mentions valid values (positive|negative)

---

## Part 3: Verification Checklist

### ✅ Ingestion API

- [ ] Create new record returns 201
- [ ] Create multiple records works
- [ ] Duplicate record returns 200
- [ ] Duplicate has `isDuplicate: true`
- [ ] High-value record created (above alert threshold)
- [ ] Missing fields returns 422 with all errors
- [ ] Invalid type returns 422
- [ ] Invalid datetime returns 422
- [ ] Invalid decimal returns 422

### ✅ Aggregation API

- [ ] Get all records returns all data
- [ ] Filter by type=positive works
- [ ] Filter by type=negative works
- [ ] Time range filter works
- [ ] Combined filters work
- [ ] Start time only works
- [ ] End time only works
- [ ] Groups calculated correctly
- [ ] Totals calculated correctly
- [ ] Invalid type filter returns 422

### ✅ Data Consistency

- [ ] Record count matches created records
- [ ] Destination grouping is accurate
- [ ] Total values are correct sums
- [ ] Timestamps are preserved
- [ ] All fields stored correctly

---

## Part 4: Analysis & Observations

### Response Times

After running all tests, check Postman's timing information:

**Expected Baseline**:
- POST /api/records: 50-70ms
- GET /api/records/aggregate: 10-30ms

**Actual Times**:
- POST: _____ ms
- GET: _____ ms

### Data Accuracy

Verify a few key calculations:

**Test**: customer-001 totals
- Records: rec-001 (1500.50) + rec-002 (750.25)
- Expected total: 2250.75
- Actual: _______
- ✅ Match?

**Test**: Negative records
- Records: rec-003 (-250.00)
- Expected total: -250.00
- Actual: _______
- ✅ Match?

---

## Part 5: Beyond Basic Testing

### Option 1: Create Custom Records

Try creating your own record with:
1. Different values
2. Different timestamps
3. Different destinations
4. Test filtering with your data

### Option 2: Stress Testing

Run the bulk create requests multiple times to see how the system handles:
- Larger datasets
- More grouping combinations
- Query performance

### Option 3: Database Inspection

While tests are running, inspect the database:

```bash
make db-shell

# View all records
SELECT * FROM records;

# See grouping
SELECT destination_id, COUNT(*), SUM(value)
FROM records
GROUP BY destination_id;

# Exit
\q
```

---

## Troubleshooting

### Connection Refused
```bash
make up && make migrate
```

### 404 Not Found
- Check base_url in Variables: should be `http://localhost:8080`
- Verify Nginx is running: `make status`

### 422 on Valid Data
- Check field formats:
  - Time: `YYYY-MM-DD HH:MM:SS`
  - Type: exactly `positive` or `negative`
  - Value: decimal with max 2 places

### Aggregation Returns Empty
- Make sure you created records first!
- Check count from GET all records

---

## Summary

By completing all phases, you have verified:

✅ **Data Ingestion**
- Records are stored correctly
- Validation works
- Idempotency is guaranteed

✅ **Aggregation Queries**
- Records can be filtered by type
- Records can be filtered by time
- Records are grouped correctly
- Totals are calculated accurately

✅ **Error Handling**
- Invalid input rejected with proper errors
- Clear error messages provided
- Appropriate HTTP status codes

✅ **Performance**
- Response times are within baseline
- Handles bulk data efficiently

---

## Next Steps

1. **Present Results**: Show test execution and results
2. **Code Review**: Walk through implementation
3. **Deploy**: Ready for production
4. **Monitor**: Watch real-world usage

---

**Testing Complete!** 🎉

For detailed API documentation, see:
- API_EXAMPLES.md
- DESIGN.md
- IMPLEMENTATION_SUMMARY.md
