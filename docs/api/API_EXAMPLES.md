# API Usage Examples

## Data Ingestion Endpoint

### POST /api/records

#### Example 1: Create New Record

**Request**:
```bash
curl -X POST http://localhost:8080/api/records \
  -H "Content-Type: application/json" \
  -d '{
    "recordId": "rec-12345",
    "time": "2025-01-01 10:00:00",
    "sourceId": "source-001",
    "destinationId": "customer-789",
    "type": "positive",
    "value": "1500.50",
    "unit": "EUR",
    "reference": "order-456"
  }'
```

**Response** (201 Created):
```json
{
  "message": "Record processed successfully",
  "data": {
    "id": 1,
    "recordId": "rec-12345",
    "time": "2025-01-01T10:00:00.000000Z",
    "sourceId": "source-001",
    "destinationId": "customer-789",
    "type": "positive",
    "value": "1500.50",
    "unit": "EUR",
    "reference": "order-456",
    "isDuplicate": false
  }
}
```

#### Example 2: Duplicate Request (Idempotent)

**Request** (Same as Example 1):
```bash
curl -X POST http://localhost:8080/api/records \
  -H "Content-Type: application/json" \
  -d '{
    "recordId": "rec-12345",
    "time": "2025-01-01 10:00:00",
    "sourceId": "source-001",
    "destinationId": "customer-789",
    "type": "positive",
    "value": "1500.50",
    "unit": "EUR",
    "reference": "order-456"
  }'
```

**Response** (200 OK - Idempotent):
```json
{
  "message": "Record already processed (idempotent response)",
  "data": {
    "id": 1,
    "recordId": "rec-12345",
    "isDuplicate": true,
    ...
  }
}
```

#### Example 3: Validation Error

**Request** (Invalid type):
```bash
curl -X POST http://localhost:8080/api/records \
  -H "Content-Type: application/json" \
  -d '{
    "recordId": "rec-999",
    "time": "2025-01-01 10:00:00",
    "sourceId": "source-001",
    "destinationId": "customer-789",
    "type": "invalid",
    "value": "1500.50",
    "unit": "EUR",
    "reference": "order-456"
  }'
```

**Response** (422 Unprocessable Entity):
```json
{
  "message": "The type field must be one of: positive, negative.",
  "errors": {
    "type": [
      "The type field must be one of: positive, negative."
    ]
  }
}
```

#### Example 4: Missing Fields

**Request**:
```bash
curl -X POST http://localhost:8080/api/records \
  -H "Content-Type: application/json" \
  -d '{
    "recordId": "rec-888"
  }'
```

**Response** (422 Unprocessable Entity):
```json
{
  "message": "The time field is required.",
  "errors": {
    "time": ["The time field is required."],
    "sourceId": ["The sourceId field is required."],
    "destinationId": ["The destinationId field is required."],
    "type": ["The type field is required."],
    "value": ["The value field is required."],
    "unit": ["The unit field is required."],
    "reference": ["The reference field is required."]
  }
}
```

---

## Aggregation Query Endpoint

### GET /api/records/aggregate

#### Example 1: Get All Records (No Filters)

**Request**:
```bash
curl http://localhost:8080/api/records/aggregate
```

**Response** (200 OK):
```json
{
  "message": "Records aggregated successfully",
  "data": {
    "count": 5,
    "records": [
      {
        "id": 1,
        "recordId": "rec-001",
        "time": "2025-01-01T10:00:00.000000Z",
        "sourceId": "source-001",
        "destinationId": "customer-001",
        "type": "positive",
        "value": "100.00",
        "unit": "EUR",
        "reference": "ref-001"
      },
      {
        "id": 2,
        "recordId": "rec-002",
        "time": "2025-01-01T11:00:00.000000Z",
        "sourceId": "source-002",
        "destinationId": "customer-001",
        "type": "positive",
        "value": "50.00",
        "unit": "EUR",
        "reference": "ref-001"
      }
    ],
    "groups": [
      {
        "destinationId": "customer-001",
        "recordCount": 2,
        "totalValue": "150"
      },
      {
        "destinationId": "customer-002",
        "recordCount": 3,
        "totalValue": "500"
      }
    ]
  }
}
```

#### Example 2: Filter by Type

**Request**:
```bash
curl "http://localhost:8080/api/records/aggregate?type=positive"
```

**Response** (200 OK):
```json
{
  "message": "Records aggregated successfully",
  "data": {
    "count": 3,
    "records": [
      {
        "id": 1,
        "recordId": "rec-001",
        "type": "positive",
        "value": "100.00",
        ...
      }
    ],
    "groups": [
      {
        "destinationId": "customer-001",
        "recordCount": 1,
        "totalValue": "100"
      }
    ]
  }
}
```

#### Example 3: Filter by Time Range

**Request** (URL encoded: `2025-01-01 10:00:00` → `2025-01-01%2010:00:00`):
```bash
curl "http://localhost:8080/api/records/aggregate?startTime=2025-01-01%2010:00:00&endTime=2025-01-01%2015:00:00"
```

**Response** (200 OK):
```json
{
  "message": "Records aggregated successfully",
  "data": {
    "count": 3,
    "records": [
      {
        "id": 1,
        "time": "2025-01-01T10:30:00.000000Z",
        ...
      }
    ],
    "groups": [
      {
        "destinationId": "customer-001",
        "recordCount": 2,
        "totalValue": "250"
      }
    ]
  }
}
```

#### Example 4: Combine Type and Time Filters

**Request**:
```bash
curl "http://localhost:8080/api/records/aggregate?type=negative&startTime=2025-01-01%2010:00:00&endTime=2025-01-02%2010:00:00"
```

**Response** (200 OK):
```json
{
  "message": "Records aggregated successfully",
  "data": {
    "count": 2,
    "records": [
      {
        "id": 3,
        "type": "negative",
        "value": "-50.00",
        ...
      }
    ],
    "groups": [
      {
        "destinationId": "customer-002",
        "recordCount": 2,
        "totalValue": "-75"
      }
    ]
  }
}
```

#### Example 5: Invalid Type Filter

**Request**:
```bash
curl "http://localhost:8080/api/records/aggregate?type=unknown"
```

**Response** (422 Unprocessable Entity):
```json
{
  "message": "Invalid type. Must be \"positive\" or \"negative\".",
  "errors": {
    "type": ["Invalid type value"]
  }
}
```

#### Example 6: Empty Database

**Request**:
```bash
curl "http://localhost:8080/api/records/aggregate"
```

**Response** (200 OK):
```json
{
  "message": "Records aggregated successfully",
  "data": {
    "count": 0,
    "records": [],
    "groups": []
  }
}
```

---

## Common Use Cases

### Use Case 1: Streaming Records from Payment Service

Multiple concurrent requests, each with unique `recordId`:

```bash
# Service sends records continuously
for i in {1..100}; do
  curl -X POST http://localhost:8080/api/records \
    -H "Content-Type: application/json" \
    -d "{
      \"recordId\": \"payment-${i}\",
      \"time\": \"$(date -u +'%Y-%m-%d %H:%M:%S')\",
      \"sourceId\": \"payment-service\",
      \"destinationId\": \"merchant-001\",
      \"type\": \"positive\",
      \"value\": \"$((RANDOM % 10000 / 100)).50\",
      \"unit\": \"EUR\",
      \"reference\": \"order-batch-001\"
    }"
done
```

### Use Case 2: Reporting - Daily Revenue Summary

```bash
# Get daily aggregation for a specific destination
curl "http://localhost:8080/api/records/aggregate?startTime=2025-01-01%2000:00:00&endTime=2025-01-01%2023:59:59&type=positive"

# Parse and use the groups data
# Extract: total revenue per destination
```

### Use Case 3: Alert Monitoring - Threshold Breaches

```bash
# Check for high-value transactions (negative impact)
curl "http://localhost:8080/api/records/aggregate?type=negative"

# Filter records with value > 500
# Alert service receives SendAlertJob for values > 1000
```

### Use Case 4: Idempotent Retry Logic

```bash
# Service can safely retry failed requests
# Same recordId = guaranteed idempotent response

curl -X POST http://localhost:8080/api/records \
  -H "Content-Type: application/json" \
  -d '{"recordId": "retry-test", ...}'  # 201 Created

# Network timeout, retry
sleep 5

curl -X POST http://localhost:8080/api/records \
  -H "Content-Type: application/json" \
  -d '{"recordId": "retry-test", ...}'  # 200 OK (same record)
```

---

## Testing the API with curl

### Quick Test Suite

```bash
#!/bin/bash

# 1. Create a record
echo "Testing: Create new record"
curl -X POST http://localhost:8080/api/records \
  -H "Content-Type: application/json" \
  -d '{
    "recordId": "test-001",
    "time": "2025-01-01 10:00:00",
    "sourceId": "test-source",
    "destinationId": "test-dest",
    "type": "positive",
    "value": "100.00",
    "unit": "EUR",
    "reference": "test-ref"
  }' | jq .

# 2. Duplicate request (idempotent)
echo -e "\n\nTesting: Duplicate request"
curl -X POST http://localhost:8080/api/records \
  -H "Content-Type: application/json" \
  -d '{
    "recordId": "test-001",
    "time": "2025-01-01 10:00:00",
    "sourceId": "test-source",
    "destinationId": "test-dest",
    "type": "positive",
    "value": "100.00",
    "unit": "EUR",
    "reference": "test-ref"
  }' | jq .

# 3. Aggregate all records
echo -e "\n\nTesting: Aggregate all records"
curl http://localhost:8080/api/records/aggregate | jq .

# 4. Aggregate with type filter
echo -e "\n\nTesting: Aggregate with type filter"
curl "http://localhost:8080/api/records/aggregate?type=positive" | jq .
```

---

## Response Status Codes

| Code | Scenario |
|------|----------|
| **200 OK** | Duplicate record (idempotent) or successful aggregation query |
| **201 Created** | New record successfully stored |
| **400 Bad Request** | Malformed JSON or invalid request format |
| **422 Unprocessable Entity** | Validation error (missing/invalid fields) |
| **500 Internal Server Error** | Server error during record processing |

---

## Performance Characteristics

- **POST /api/records**: ~50-70ms (includes DB insert, job dispatch)
- **GET /api/records/aggregate**: ~10-30ms (depends on dataset size)
- **Throughput**: Sustained 28 records/second per instance
- **Horizontal Scaling**: Linear scaling with additional instances + load balancer

