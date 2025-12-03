# Log Viewing Guide

## Overview

This guide covers 6 different methods for viewing and monitoring logs from the Data Processing Service. Each method has different strengths and use cases.

## Method 1: Docker Compose Logs (Simplest)

### Basic Command
```bash
make logs service=app
```

### What You'll See
- All application log output
- Errors and warnings in real-time
- Queue job processing messages

### Streaming Logs (Follow Mode)
```bash
make logs service=app --follow
```
- Shows new logs as they appear
- Press Ctrl+C to exit

### View Specific Service
```bash
# View database logs
make logs service=db

# View Redis logs
make logs service=redis

# View all services
make logs
```

### Common Log Messages

**Record Created Successfully**:
```
[2024-12-03 12:34:56] local.INFO: Record processed {"record_id":"ABC123","destination":"dest-1"}
```

**Duplicate Record Detected**:
```
[2024-12-03 12:34:57] local.INFO: Duplicate record handled {"record_id":"ABC123","status":"duplicate"}
```

**Validation Error**:
```
[2024-12-03 12:34:58] local.ERROR: Validation failed {"errors":{"recordId":"The recordId is required."}}
```

**Job Dispatched**:
```
[2024-12-03 12:34:59] local.DEBUG: Job dispatched {"job":"SendNotificationJob","record_id":"ABC123"}
```

**Alert Triggered**:
```
[2024-12-03 12:35:00] local.WARNING: Alert triggered {"value":"1500.50","threshold":"1000.00"}
```

### Filter Logs

**Show only errors**:
```bash
make logs service=app | grep ERROR
```

**Show only warnings**:
```bash
make logs service=app | grep WARNING
```

**Show specific record ID**:
```bash
make logs service=app | grep "ABC123"
```

**Show last 50 lines**:
```bash
make logs service=app | tail -50
```

### Pros and Cons

**Pros**:
- ✓ Simple, one command
- ✓ Works everywhere
- ✓ No additional setup
- ✓ Shows all containers

**Cons**:
- ✗ No real-time filtering
- ✗ Not ideal for long-term monitoring
- ✗ Terminal gets cluttered with old output

---

## Method 2: Laravel Pail (Best for Application Logs)

### Installation Status
Laravel Pail is included in Laravel 12 by default.

### Basic Command
```bash
make artisan cmd="pail"
```

### What You'll See
- Formatted application logs
- Real-time updates
- Color-coded by severity

### Real-Time Monitoring
```bash
# Start Pail
make artisan cmd="pail"

# Press Ctrl+C to exit
```

### Pail Output Example
```
[12:34:56] local.INFO: Record processed
  record_id: ABC123
  destination_id: dest-1
  value: 150.50

[12:34:57] local.INFO: Duplicate detected
  record_id: ABC123

[12:35:00] local.WARNING: Alert triggered
  value: 1500.50
  threshold: 1000.00
```

### Filtering Logs

**Filter by log level**:
```bash
make artisan cmd="pail --level=error"
make artisan cmd="pail --level=warning"
make artisan cmd="pail --level=info"
make artisan cmd="pail --level=debug"
```

**Filter by channel**:
```bash
make artisan cmd="pail --channel=default"
```

**Show only recent logs**:
```bash
make artisan cmd="pail --lines=50"
```

### Pros and Cons

**Pros**:
- ✓ Designed for Laravel applications
- ✓ Beautiful formatting
- ✓ Real-time updates
- ✓ Easy filtering
- ✓ Shows log levels clearly

**Cons**:
- ✗ Laravel-specific only
- ✗ Requires running command in container
- ✗ One command per filter type

---

## Method 3: Docker Desktop Dashboard (Visual UI)

### Step 1: Open Docker Desktop
- Click Docker icon in macOS menu bar
- Select "Dashboard"

### Step 2: Navigate to Container
1. Click "Containers" tab (left sidebar)
2. Find "data-processing-app" container
3. You'll see all running containers

### Step 3: View Logs
- Click the "Logs" tab for the app container
- Logs display in real-time
- Scroll to see history

### Container Overview
```
CONTAINER NAME             STATUS       IMAGE
data-processing-app        Up 10 min    app:latest
data-processing-db         Up 10 min    postgres:15
data-processing-redis      Up 10 min    redis:latest
```

### What You'll See
- Live log stream
- Container resource usage
- Container status
- Start/stop/restart buttons

### Pros and Cons

**Pros**:
- ✓ Visual interface (no CLI needed)
- ✓ Shows container status
- ✓ Easy to restart containers
- ✓ Intuitive for beginners

**Cons**:
- ✗ Limited filtering options
- ✗ Requires Docker Desktop (not CLI only)
- ✗ Less detailed log information

---

## Method 4: Multiple Terminals (Complete Monitoring)

### Recommended Setup
Open 4 terminals side-by-side for complete visibility:

**Terminal 1: Load Test**
```bash
cd /Users/mihai/Projects/bilogram-interview
./load_test.sh 300 28
```

Shows:
- Real-time progress bar
- Request rate
- Success/error counts
- Final summary

**Terminal 2: Application Logs**
```bash
make logs service=app --follow
```

Shows:
- Record processing
- Errors and warnings
- Job dispatching
- Real-time as records arrive

**Terminal 3: Database Monitor**
```bash
while true; do
  echo "=== $(date +'%Y-%m-%d %H:%M:%S') ==="
  make db-shell -c "SELECT COUNT(*) as total_records FROM records;" 2>/dev/null | tail -1
  sleep 5
done
```

Shows:
- Total records count
- Updates every 5 seconds
- Confirms data persistence

**Terminal 4: Resource Monitor**
```bash
docker stats
```

Shows:
- CPU usage per container
- Memory usage per container
- Network I/O
- Real-time updates

### Example Layout
```
┌──────────────────────┬──────────────────────┐
│  Terminal 1: Load    │ Terminal 2: App Logs │
│  Load Test Output    │ [2024-12-03...]      │
│  Progress: [====]    │ INFO: Record proc    │
│  RPS: 27.92          │ INFO: Job dispatch   │
└──────────────────────┴──────────────────────┘
┌──────────────────────┬──────────────────────┐
│ Terminal 3: Database │ Terminal 4: Resource │
│ Total Records: 1680  │ CPU: 45%             │
│ Updated: 12:34:56    │ Memory: 320MB        │
└──────────────────────┴──────────────────────┘
```

### Pro Tips

**Maximize terminal visibility**:
```bash
# Use tmux or split screen in iTerm2
# Or arrange separate terminal windows side-by-side
```

**Quick setup script**:
Create `~/monitor.sh`:
```bash
#!/bin/bash
# This would open 4 terminals automatically (requires iTerm2 scripting)
open -a iTerm
# ... terminal configuration
```

### Pros and Cons

**Pros**:
- ✓ Complete real-time visibility
- ✓ See all metrics simultaneously
- ✓ Easy to correlate events
- ✓ Best for active monitoring

**Cons**:
- ✗ Requires multiple terminals
- ✗ Cluttered screen
- ✗ Takes up screen space

---

## Method 5: Real-Time Dashboard (Custom Script)

### Basic Dashboard Script
```bash
#!/bin/bash
# save as: monitor_dashboard.sh

while true; do
  clear
  echo "╔════════════════════════════════════════════════════════════╗"
  echo "║           Data Processing Service - Dashboard             ║"
  echo "╠════════════════════════════════════════════════════════════╣"
  echo "║ Time: $(date +'%Y-%m-%d %H:%M:%S')                              ║"
  echo "╠════════════════════════════════════════════════════════════╣"

  # Database stats
  echo "║ DATABASE STATISTICS:                                       ║"
  RECORD_COUNT=$(make db-shell -c "SELECT COUNT(*) FROM records;" 2>/dev/null | tail -1 | grep -oE '[0-9]+' || echo "0")
  echo "║   Total Records: $RECORD_COUNT"

  UNIQUE_IDS=$(make db-shell -c "SELECT COUNT(DISTINCT record_id) FROM records;" 2>/dev/null | tail -1 | grep -oE '[0-9]+' || echo "0")
  echo "║   Unique Records: $UNIQUE_IDS"

  # Container status
  echo "╠════════════════════════════════════════════════════════════╣"
  echo "║ CONTAINER STATUS:                                          ║"
  docker ps --format "table {{.Names}}\t{{.Status}}" | tail -3 | sed 's/^/║   /'

  echo "╠════════════════════════════════════════════════════════════╣"
  echo "║ RECENT LOGS:                                               ║"
  make logs service=app 2>/dev/null | tail -3 | sed 's/^/║   /'

  echo "╚════════════════════════════════════════════════════════════╝"
  echo ""
  echo "Press Ctrl+C to exit. Refreshing in 5 seconds..."
  sleep 5
done
```

### Make Script Executable
```bash
chmod +x monitor_dashboard.sh
./monitor_dashboard.sh
```

### Custom Metrics Dashboard
```bash
#!/bin/bash
# More detailed dashboard

watch -n 5 '
  echo "=== Load Test Dashboard ==="
  echo "Time: $(date)"
  echo ""
  echo "Database:"
  make db-shell -c "SELECT COUNT(*) FROM records;" 2>/dev/null | tail -1
  echo ""
  echo "Container Status:"
  docker ps --format "table {{.Names}}\t{{.Status}}"
  echo ""
  echo "Latest Logs:"
  make logs service=app 2>/dev/null | tail -5
'
```

### Run Dashboard
```bash
./monitor_dashboard.sh
```

### Pros and Cons

**Pros**:
- ✓ Fully customizable
- ✓ Real-time updates
- ✓ Shows exactly what you want
- ✓ Professional appearance

**Cons**:
- ✗ Requires bash scripting knowledge
- ✗ May be over-engineered for simple use

---

## Method 6: Database Monitoring (Direct SQL Queries)

### Connect to Database
```bash
make db-shell
```

### View Total Records
```sql
SELECT COUNT(*) as total_records FROM records;
```

### View Record Distribution
```sql
SELECT
    type,
    COUNT(*) as count,
    SUM(value) as total_value,
    AVG(value) as avg_value,
    MIN(value) as min_value,
    MAX(value) as max_value
FROM records
GROUP BY type;
```

### View Top Destinations
```sql
SELECT
    destination_id,
    COUNT(*) as record_count,
    SUM(value) as total_value,
    COUNT(DISTINCT reference) as unique_references
FROM records
GROUP BY destination_id
ORDER BY record_count DESC
LIMIT 10;
```

### View Recent Records
```sql
SELECT
    record_id,
    destination_id,
    type,
    value,
    created_at
FROM records
ORDER BY created_at DESC
LIMIT 20;
```

### Check Duplicate Handling
```sql
-- Records that were duplicates (updated_at != created_at)
SELECT COUNT(*) as duplicate_records
FROM records
WHERE created_at != updated_at;
```

### View High-Value Records (Alert Trigger)
```sql
SELECT
    record_id,
    value,
    destination_id,
    created_at
FROM records
WHERE value > 1000.00
ORDER BY value DESC;
```

### Monitor Growth Rate
```sql
-- Check record insertion rate
SELECT
    DATE_TRUNC('minute', created_at) as minute,
    COUNT(*) as records_per_minute
FROM records
GROUP BY DATE_TRUNC('minute', created_at)
ORDER BY minute DESC
LIMIT 10;
```

### Real-Time Monitoring Query
```sql
-- Run repeatedly to see growth
\watch 5

SELECT
    COUNT(*) as total_records,
    COUNT(DISTINCT record_id) as unique_records,
    COUNT(DISTINCT destination_id) as unique_destinations,
    ROUND(AVG(value)::numeric, 2) as avg_value,
    SUM(CASE WHEN value > 1000 THEN 1 ELSE 0 END) as alert_count
FROM records;
```

### Performance Analysis
```sql
-- Index usage statistics
SELECT
    schemaname,
    tablename,
    indexname,
    idx_scan as index_scans,
    idx_tup_read as tuples_read,
    idx_tup_fetch as tuples_fetched
FROM pg_stat_user_indexes
ORDER BY idx_scan DESC;
```

### Pros and Cons

**Pros**:
- ✓ Direct data access
- ✓ Flexible queries
- ✓ Understand data quality
- ✓ Verify idempotency working

**Cons**:
- ✗ Requires SQL knowledge
- ✗ Not real-time for application events
- ✗ Terminal-based only

---

## Recommended Workflow for Load Testing

### Phase 1: Setup (2 minutes)
1. Open 2 terminals
2. Terminal 1: Prepare load test script
3. Terminal 2: Prepare Pail command

### Phase 2: Monitoring Start (30 seconds)
1. Terminal 2: Start `make artisan cmd="pail"`
2. Wait for it to initialize
3. You should see no activity initially

### Phase 3: Load Test Execution (5 minutes)
1. Terminal 1: Run `./load_test.sh 300 28`
2. Terminal 2: Watch logs appear in real-time
3. See records being processed
4. See duplicate detection
5. See notification jobs dispatching

### Phase 4: Analysis (2 minutes)
1. Wait for load test to complete
2. Review final summary statistics
3. Check for any errors in Pail
4. Open third terminal to verify database

### Phase 5: Database Verification (1 minute)
```bash
make db-shell
SELECT COUNT(*) FROM records;
```

Verify count matches load test total.

---

## Log Levels Explained

### DEBUG
- Most verbose
- Detailed diagnostic information
- Example: `Database connection opened`

### INFO
- General informational messages
- Example: `Record processed successfully`

### WARNING
- Potentially problematic situations
- Example: `Alert triggered for high value`

### ERROR
- Error conditions
- Example: `Failed to dispatch job`

### CRITICAL
- Critical conditions
- Example: `Database connection failed`

---

## Common Log Analysis Patterns

### Find All Errors
```bash
make logs service=app | grep "ERROR\|Exception\|CRITICAL"
```

### Count Records Processed
```bash
make logs service=app | grep "Record processed" | wc -l
```

### Find High-Value Records
```bash
make logs service=app | grep "value.*[1-9]0{3,}\."
```

### Monitor Job Dispatch
```bash
make logs service=app | grep "dispatch\|Job\|Queue"
```

### Identify Slow Requests
```bash
make logs service=app | grep -E "duration|[0-9]{3,}ms"
```

---

## Troubleshooting Log Issues

### Issue: No Logs Appearing

**Check if containers are running**:
```bash
make status
```

**Check logs directly**:
```bash
docker logs data-processing-app
```

### Issue: Logs Too Verbose

**Filter by level**:
```bash
make artisan cmd="pail --level=warning"
```

### Issue: Can't Connect to Database

**Check if database is running**:
```bash
make status

# Or check directly
docker ps | grep postgres
```

### Issue: Pail Command Not Found

**Verify Laravel installation**:
```bash
make artisan cmd="--version"
```

---

## Recommended Setup for Load Testing

**Option A: Minimal (Terminal 1 & 2)**
- Terminal 1: Load test script
- Terminal 2: Pail logs
- Result: Good visibility, simple setup

**Option B: Comprehensive (Terminal 1, 2, 3)**
- Terminal 1: Load test script
- Terminal 2: Pail logs
- Terminal 3: `watch` database count
- Result: Best visibility of complete system

**Option C: Advanced (4+ Terminals + Dashboard)**
- Terminal 1: Load test
- Terminal 2: Pail logs
- Terminal 3: Database monitor
- Terminal 4: Docker stats
- Result: Professional monitoring setup

---

**Pro Tip**: Start with Option A (Minimal), then upgrade to B or C if you want more detailed monitoring.

---

**Created**: 2024-12-03
**Updated**: 2024-12-03
