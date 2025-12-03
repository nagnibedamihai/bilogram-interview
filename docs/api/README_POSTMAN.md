# Postman Testing - Complete Setup

## 🎯 Objective

Test the Data Processing Service API endpoints manually using Postman with pre-configured requests.

---

## 📦 What's Included

### Collection File
- **postman_collection.json** - 22 pre-configured requests, ready to import

### Documentation
- **POSTMAN_GUIDE.md** - Comprehensive setup and usage guide
- **POSTMAN_QUICK_REFERENCE.md** - One-page cheat sheet
- **TESTING_GUIDE.md** - Step-by-step testing procedure with expected results

---

## 🚀 Quick Start (2 minutes)

### 1. Import Collection

```
1. Open Postman
2. Click Import (top left)
3. Click "Upload Files"
4. Select: postman_collection.json
5. Click Import
```

### 2. Start Application

```bash
make up          # Start containers
make migrate     # Run migrations
make status      # Verify all running
```

### 3. Start Testing

- Open the imported collection in Postman
- Select a request from Data Ingestion folder
- Click Send
- See the response

---

## 📊 Collection Overview

### 5 Folders, 22 Requests

| Folder | Requests | Purpose |
|--------|----------|---------|
| **Data Ingestion** | 5 | Create records, test idempotency, alert threshold |
| **Validation Errors** | 4 | Missing fields, invalid types, bad formats |
| **Aggregation Queries** | 7 | Filter, group, calculate totals |
| **Aggregation Errors** | 1 | Invalid query parameters |
| **Load Testing** | 5 | Bulk data creation |

---

## 📋 Testing Checklist

### Essential Tests (10 minutes)

- [ ] **Data Ingestion**
  - [ ] Create new record (201)
  - [ ] Create duplicate (200)
  - [ ] Invalid data (422)

- [ ] **Aggregation**
  - [ ] Get all records (200)
  - [ ] Filter by type (200)
  - [ ] Invalid filter (422)

- [ ] **Verification**
  - [ ] Response status codes correct
  - [ ] Data in responses accurate
  - [ ] Error messages clear

---

## 🔍 Key Requests to Try First

### 1. Create Your First Record

**Request**: Data Ingestion → Create New Record
```json
{
  "recordId": "rec-001",
  "time": "2025-01-01 10:00:00",
  "destinationId": "customer-001",
  "type": "positive",
  "value": "1500.50"
}
```

**Expected**: 201 Created

### 2. Test Idempotency

**Request**: Data Ingestion → Duplicate Record

Same data as above, sent again

**Expected**: 200 OK with `"isDuplicate": true`

### 3. Query All Records

**Request**: Aggregation Queries → Get All Records (No Filters)

**Expected**: 200 OK with all records grouped by destination

---

## 📖 Detailed Documentation

### Read First
1. **POSTMAN_GUIDE.md** - Complete setup & usage instructions
2. **POSTMAN_QUICK_REFERENCE.md** - Quick lookup card

### Then Follow
3. **TESTING_GUIDE.md** - Step-by-step testing (10 phases)

---

## ⏱️ Estimated Time

- **Setup**: 2 minutes
- **Basic Testing**: 10 minutes
- **Complete Testing**: 30 minutes
- **Analysis**: 10 minutes

---

## 📊 What You'll Test

### Data Ingestion API
- ✅ Create new records
- ✅ Handle duplicates (idempotency)
- ✅ Alert threshold
- ✅ Input validation

### Aggregation API
- ✅ Query all records
- ✅ Filter by type
- ✅ Filter by time range
- ✅ Combine filters
- ✅ Group by destination
- ✅ Calculate totals

### Error Handling
- ✅ Missing fields (422)
- ✅ Invalid values (422)
- ✅ Invalid queries (422)

---

## 🎯 Expected Results

### All Tests Should Pass ✅

- 21 successful requests (200/201 responses)
- 1 validation error (422 response)
- All data accurate
- All grouping correct

---

## 📱 Postman Variables

The collection uses one variable:
- **base_url**: http://localhost:8080

To modify:
1. Select collection → Variables tab
2. Update base_url
3. Save

---

## 🆘 Troubleshooting

### Connection Refused
```bash
make up
make migrate
```

### 404 Not Found
- Check base_url in Variables
- Verify services running: `make status`

### 422 on Valid Data
- Check datetime format: YYYY-MM-DD HH:MM:SS
- Check type: "positive" or "negative"
- Check value: max 2 decimals

### No Data in Aggregation
- Create records first
- Verify count in "Get All Records"

---

## 💡 Pro Tips

1. **Save Responses**: Right-click response → Save response
2. **Compare**: Open two requests side-by-side
3. **Test Scripts**: Add tests to requests for automation
4. **Environment Variables**: Create env vars for dynamic data
5. **Collection Runner**: Run all requests in sequence

---

## 🔗 Related Documentation

- **DESIGN.md** - Architecture & design decisions
- **API_EXAMPLES.md** - Detailed API examples with curl
- **IMPLEMENTATION_SUMMARY.md** - Project overview
- **TESTING_GUIDE.md** - Complete testing walkthrough

---

## ✨ Features of This Collection

✅ **Pre-configured**: All requests ready to use
✅ **Organized**: 5 folders by functionality
✅ **Complete**: 22 requests covering all scenarios
✅ **Documented**: Every request has clear purpose
✅ **Error Cases**: Includes validation error tests
✅ **Load Testing**: Bulk data creation included
✅ **Expected Results**: Every request documents what to expect

---

## 📌 Next Steps

1. **Import** the collection into Postman
2. **Start** the application (make up && make migrate)
3. **Follow** TESTING_GUIDE.md for step-by-step testing
4. **Verify** all tests pass
5. **Analyze** results and performance

---

## 🎉 You're All Set!

Everything you need to test the API is ready. Just import the collection and start testing!

**Questions?** See POSTMAN_GUIDE.md for comprehensive help.

---

**Happy Testing!** 🚀
