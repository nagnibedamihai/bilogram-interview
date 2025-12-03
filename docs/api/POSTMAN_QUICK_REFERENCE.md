# Postman Collection - Quick Reference Card

## Import & Setup (30 seconds)

```
1. Open Postman
2. Click Import → Upload Files
3. Select: postman_collection.json
4. Verify base_url in Variables tab
5. Start service: make up && make migrate
```

## Request Quick Links

### ✅ Data Ingestion (All should return 201 or 200)

| # | Request | Expected | Key Field |
|---|---------|----------|-----------|
| 1 | Create New Record | 201 | recordId: rec-001 |
| 2 | Create Another Record | 201 | recordId: rec-002 |
| 3 | Different Destination | 201 | destinationId: customer-002 |
| 4 | **Duplicate Record** | **200** | **isDuplicate: true** |
| 5 | High Value (Alert) | 201 | value: 1500.00 |

### ❌ Validation Errors (All should return 422)

| Error Type | Issue | Test |
|------------|-------|------|
| Missing Fields | Only `recordId` provided | See 1 error request |
| Invalid Type | type: "invalid-type" | See 2 error request |
| Bad DateTime | time: "invalid-date" | See 3 error request |
| Bad Decimal | value: "100.999" (3 decimals) | See 4 error request |

### 📊 Aggregation Queries (All should return 200)

| Query | Parameter | Use Case |
|-------|-----------|----------|
| Get All | (none) | See all records |
| Positive | `?type=positive` | Inflows only |
| Negative | `?type=negative` | Outflows only |
| Time Range | `?startTime=...&endTime=...` | Date range report |
| Combined | `?type=positive&startTime=...` | Filtered report |
| Start Only | `?startTime=...` | From date onwards |
| End Only | `?endTime=...` | Until date |

### ⚡ Load Testing (5 bulk records)

- Creates records: bulk-001 through bulk-005
- Use before running aggregation queries
- Builds dataset for testing grouping

---

## Sample Test Sequence (10 minutes)

### Phase 1: Happy Path (2 min)
```
1. Create New Record → Check 201 response
2. Create Another Record → Check 201 response
3. Get All Records → Check 2 records returned
```

### Phase 2: Idempotency (1 min)
```
4. Duplicate Record → Check 200 response
5. Check isDuplicate: true in response
```

### Phase 3: Validation (2 min)
```
6. Missing Fields → Check 422 with errors
7. Invalid Type → Check 422 with type error
8. Invalid DateTime → Check 422 with time error
```

### Phase 4: Aggregation (3 min)
```
9. Get All Records → Verify count = 3
10. Filter by Type: Positive → Should exclude negative
11. Filter by Type: Negative → Should show only 1
12. Time Range → Adjust times, observe filtering
```

### Phase 5: Bulk Load (2 min)
```
13-17. Run all 5 Bulk Create requests
18. Get All Records → Count should be 8 (3 + 5)
19. Check Groups → Should group by destination
```

---

## Key Response Fields

### POST /api/records Response (201/200)
```json
{
  "message": "Record processed successfully",
  "data": {
    "id": <auto-increment>,
    "recordId": "<your-id>",
    "isDuplicate": true/false,
    "time": "2025-01-01T10:00:00Z",
    "value": "1500.50"
  }
}
```

### GET /api/records/aggregate Response (200)
```json
{
  "data": {
    "count": <total-records>,
    "records": [{...}, {...}],
    "groups": [
      {
        "destinationId": "<id>",
        "recordCount": <count>,
        "totalValue": "<sum>"
      }
    ]
  }
}
```

### Error Response (422)
```json
{
  "message": "Validation message",
  "errors": {
    "fieldName": ["Error detail"]
  }
}
```

---

## HTTP Status Codes Cheat Sheet

| Code | Meaning | When |
|------|---------|------|
| **200** | OK | Duplicate record (idempotent), successful query |
| **201** | Created | New record successfully stored |
| **400** | Bad Request | Malformed JSON |
| **422** | Validation Error | Invalid field values |
| **500** | Server Error | Database/application error |

---

## Common Query Parameters

### Time Format
```
YYYY-MM-DD HH:MM:SS
Example: 2025-01-01 10:00:00
URL: 2025-01-01%2010:00:00 (space = %20)
```

### Type Values
```
positive  → Revenue/inflows
negative  → Costs/refunds/outflows
```

### Special Values
```
recordId: Any unique string (per ingestion)
value: Decimal up to 2 places (e.g., 100.50)
destinationId: Groups aggregation results
reference: Related records in notifications
```

---

## Tips for Testing

### Test Idempotency
1. Note the exact `recordId` from a successful request
2. Send the EXACT same request again
3. Should get 200 instead of 201
4. Response should have `"isDuplicate": true`

### Test Aggregation Grouping
1. Create records with different `destinationId` values
2. Run aggregation query
3. Check `groups` array has entries for each destination
4. Verify `totalValue` is sum of all values for that destination

### Test Time Filtering
1. Note the `time` values of your records
2. Use `startTime` and `endTime` that span some records
3. Verify returned count matches records in time range

### Test Type Filtering
1. Create both positive and negative records
2. Query with `?type=positive` → should exclude negative
3. Query with `?type=negative` → should exclude positive

---

## Performance Baseline

Check request timing in Postman:
- **POST /api/records**: Should be ~50-70ms
- **GET /api/records/aggregate**: Should be ~10-30ms

---

## Database Inspection

While testing:
```bash
make db-shell

# View all records
SELECT * FROM records ORDER BY time;

# Group summary
SELECT destination_id, COUNT(*) as count, SUM(value) as total
FROM records GROUP BY destination_id;

# Exit
\q
```

---

## Troubleshooting Quick Links

| Problem | Solution |
|---------|----------|
| Connection refused | `make up && make migrate` |
| 404 Not Found | Check base_url in Variables |
| 422 on valid data | Check time format: YYYY-MM-DD HH:MM:SS |
| Duplicate returns 201 | Use exact same `recordId` |
| Aggregation empty | Create records first with POST |

---

## Request Template

Use this for creating custom requests:

```
POST http://localhost:8080/api/records
Content-Type: application/json

{
  "recordId": "unique-id",
  "time": "2025-01-01 10:00:00",
  "sourceId": "source-name",
  "destinationId": "dest-name",
  "type": "positive",
  "value": "100.00",
  "unit": "EUR",
  "reference": "ref-name"
}
```

---

## Expected Test Results

### Successful Testing Checklist
- [ ] 5 data ingestion requests all return expected status
- [ ] Duplicate record returns 200 (not 201)
- [ ] All 4 validation errors return 422
- [ ] All 7 aggregation queries return 200
- [ ] Aggregation groups data correctly by destination
- [ ] Time filtering works correctly
- [ ] Type filtering works correctly
- [ ] Response times are within baseline

### When All Tests Pass ✅
- API is working correctly
- Validation is proper
- Idempotency is implemented
- Aggregation is accurate
- Ready for production!

---

**Last Updated**: January 2025
**Collection Version**: 1.0
**API Version**: v1
