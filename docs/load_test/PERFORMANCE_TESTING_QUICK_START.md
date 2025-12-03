# Performance Testing - Quick Start (5 Minutes)

## TL;DR - Get Started Now

```bash
cd docs/load_test

# Terminal 1: Start load test (5 minutes)
./load_test.sh 300 28

# Terminal 2: Watch logs in real-time (in parallel)
make artisan cmd="pail"
```

Expected result: **✓ PASS** - 100,800 records/hour equivalent

---

## 5-Minute Quick Start

### Step 1: Verify System is Ready (30 seconds)
```bash
make status
```

Expected output - all services "Up":
```
  Name                 Command      State      Ports
─────────────────────────────────────────────────────
data-processing-app   ...up...      Up...      0.0.0.0:80->8000/tcp
data-processing-db    ...postgres   Up...      0.0.0.0:5432->5432/tcp
data-processing-redis ...redis...   Up...      0.0.0.0:6379->6379/tcp
```

If not up, run:
```bash
make up
make migrate
```

### Step 2: Start Load Test in Terminal 1 (3 seconds)
```bash
./load_test.sh 300 28
```

You'll see:
```
Configuration:
  Duration:           300 seconds
  Target RPS:         28 records/second
  Expected Total:     8,400 records
  Equivalent to:      100,800 records/hour

Starting load test...
Progress: [=>                            ] 5% | Records: 420/8400 | RPS: 27.96 | 201: 418 | 200: 2 | Errors: 0
```

### Step 3: Monitor Logs in Terminal 2 (3 seconds)
```bash
make artisan cmd="pail"
```

You'll see real-time logs:
```
[12:34:56] local.INFO: Record processed
  record_id: LOAD-TEST-1701605696-12345
  destination_id: dest-2
  value: 150.50

[12:34:57] local.INFO: Record processed
  record_id: LOAD-TEST-1701605697-54321
  destination_id: dest-1
  value: 850.75

[12:35:00] local.WARNING: Alert triggered
  value: 1500.50
  threshold: 1000.00
```

### Step 4: Wait for Completion (5 minutes)
- Watch Terminal 1 progress bar reach 100%
- Terminal 2 continues showing logs

### Step 5: Review Results (1 minute)
Terminal 1 will display:
```
================================================================================
LOAD TEST SUMMARY
================================================================================

Test Duration:        300.00 seconds
Total Requests:       8,400
  ✓ New Records (201): 8,350
  ✓ Duplicates (200):  50
  ✗ Errors (4xx/5xx):  0

Performance:
  Average RPS:        27.92 records/second
  Success Rate:       99.4%

Projected Throughput:
  Per Hour:           100,512 records
  Status:             ✓ PASS - Meets 100k/hour requirement (>27 RPS)

================================================================================
```

### Step 6: Verify Database (30 seconds)
```bash
make db-shell
SELECT COUNT(*) FROM records;
```

Expected output:
```
 count
-------
  8400
(1 row)
```

---

## 15-Minute Comprehensive Setup

### Phase 1: Pre-Test Setup (3 minutes)

**Terminal 1**:
```bash
# Verify everything is running
make status

# Check database is empty (optional, but good practice)
make db-shell -c "SELECT COUNT(*) FROM records;" 2>/dev/null | tail -1
```

**Terminal 2**:
```bash
# Prepare Pail (it will wait for test to start)
make artisan cmd="pail"
```

**Terminal 3**:
```bash
# Prepare database monitor
watch -n 2 "make db-shell -c 'SELECT COUNT(*) FROM records;' 2>/dev/null | tail -1"
```

### Phase 2: Execute Load Test (5+ minutes)

**Terminal 1**:
```bash
./load_test.sh 300 28
```

Observe:
- Progress bar advances smoothly
- RPS hovers around 28
- 201 count grows (new records)
- 200 count increases slowly (duplicates)
- Errors remain at 0

### Phase 3: Monitor Activity (5 minutes)

**Terminal 2** - Pail logs show:
- Record processing logs
- Occasional alert triggers (values > 1000)
- Job dispatching

**Terminal 3** - Database count grows:
```
 count
-------
  1680
  2240
  2800
  3360
  3920
  4480
  5040
  ...
  8400
```

### Phase 4: Analysis (2 minutes)

Wait for test to complete and verify:

1. **RPS Achievement**: 27-28 RPS ✓
2. **Success Rate**: 99%+ ✓
3. **Error Count**: 0 ✓
4. **Hourly Projection**: ~100k ✓
5. **Database Records**: Match total count ✓

---

## Standard Test Scenarios

### Scenario 1: Baseline (Recommended First Test)
```bash
./load_test.sh 60 28
```
- **Duration**: 1 minute
- **Total Records**: 1,680
- **Expected RPS**: 27-28
- **Expected Status**: ✓ PASS

### Scenario 2: Sustained (Verify Stability)
```bash
./load_test.sh 300 28
```
- **Duration**: 5 minutes
- **Total Records**: 8,400
- **Expected RPS**: 27-28 (consistent)
- **Expected Status**: ✓ PASS

### Scenario 3: Stress (Beyond Spec)
```bash
./load_test.sh 120 50
```
- **Duration**: 2 minutes
- **Load**: 1.8x normal (50 RPS vs 28)
- **Expected Status**: ✓ PASS (may see 1-2% errors)

### Scenario 4: Spike (Maximum Load)
```bash
./load_test.sh 60 100
```
- **Duration**: 1 minute
- **Load**: 3.6x normal (100 RPS vs 28)
- **Expected Status**: ✓ HANDLES (degraded but functional)

---

## Monitoring Checklist

During load test, verify:

- [ ] Progress bar moves smoothly from 0% to 100%
- [ ] RPS stays between 27-28
- [ ] 201 count grows steadily
- [ ] 200 count stays low (< 1-2%)
- [ ] Errors remain at 0
- [ ] Pail logs show record processing
- [ ] Database count increases every few seconds
- [ ] No CPU maxed out (< 90%)
- [ ] No memory leaks (stable usage)

---

## Expected Results

### Baseline Test (60 seconds at 28 RPS)

| Metric | Expected | Status |
|--------|----------|--------|
| Total Records | ~1,680 | ✓ |
| RPS | 27-28 | ✓ |
| Success Rate | 99-100% | ✓ |
| Hourly Projection | ~100,800 | ✓ |
| Errors | 0 | ✓ |

### 5-Minute Test (300 seconds at 28 RPS)

| Metric | Expected | Status |
|--------|----------|--------|
| Total Records | ~8,400 | ✓ |
| RPS | 27-28 consistent | ✓ |
| Success Rate | 99-100% | ✓ |
| Errors | 0 | ✓ |
| Memory Stable | Yes | ✓ |
| CPU Usage | 30-50% | ✓ |

---

## Troubleshooting

### Issue: 404 Errors

**Cause**: Server not responding

**Fix**:
```bash
make up
make migrate
```

### Issue: Very Low RPS (< 15)

**Cause**: System overloaded

**Fix**:
```bash
# Check Docker resources
docker stats

# Try lighter load
./load_test.sh 60 10
```

### Issue: No Logs Appearing

**Cause**: Containers not running

**Fix**:
```bash
make status
make up
```

### Issue: Database Not Growing

**Cause**: Records not being persisted

**Fix**:
```bash
make logs service=app | grep ERROR
make migrate
```

---

## M4 MacBook Performance Expectations

### Easy Performance (Recommended)
- **Load**: 28 RPS (100k/hour)
- **CPU**: 30-50%
- **Status**: ✓ PASS

### Comfortable Performance
- **Load**: 50 RPS (180k/hour)
- **CPU**: 50-70%
- **Status**: ✓ PASS

### Stressed Performance
- **Load**: 100 RPS (360k/hour)
- **CPU**: 90%+
- **Status**: ✓ Functional but degraded

### Beyond Capacity
- **Load**: 500+ RPS
- **Status**: ❌ Needs scaling

---

## Quick Reference Commands

```bash
# Start load test
./load_test.sh 300 28

# Monitor logs
make artisan cmd="pail"

# Check database
make db-shell
SELECT COUNT(*) FROM records;

# View container status
make status

# Stop containers (if needed)
make down

# Restart everything
make down && make up && make migrate
```

---

## 10 Testing Phases

### Phase 1: Pre-Test Verification (1 min)
```bash
make status
make db-shell -c "SELECT COUNT(*) FROM records;"
```

### Phase 2: Log Monitoring Ready (30 sec)
```bash
make artisan cmd="pail"
```
Wait for "Listening for messages" message

### Phase 3: Start Load Test (5+ min)
```bash
./load_test.sh 300 28
```
Watch progress bar advance

### Phase 4: Monitor Progress (ongoing)
Watch Terminal 1 progress and Terminal 2 logs

### Phase 5: Alert Observation (ongoing)
Look for `Alert triggered` logs when value > 1000

### Phase 6: Test Completion (5 min mark)
Wait for progress to reach 100%

### Phase 7: Results Review (1 min)
Read the summary report from load test

### Phase 8: Log Analysis (1 min)
Press Ctrl+C in Pail terminal
Review the collected logs

### Phase 9: Database Verification (1 min)
```bash
make db-shell
SELECT COUNT(*) FROM records;
SELECT COUNT(DISTINCT record_id) FROM records;
```

### Phase 10: Success Confirmation
All checks passed = ✓ System ready for 100k/hour

---

## When to Use Each Test

### Use Baseline Test (60 sec) When:
- First time running tests
- Quick verification before deployment
- Validating system still works
- Time-constrained testing

### Use 5-Minute Test (300 sec) When:
- Comprehensive performance validation
- Verifying sustained performance
- Before production deployment
- Documenting capabilities

### Use Stress Test (50 RPS) When:
- Testing margin of safety
- Designing for peak loads
- Capacity planning
- System resilience validation

### Use Spike Test (100 RPS) When:
- Testing emergency scenarios
- Understanding breaking points
- Documenting maximum capabilities
- Research and development

---

## Post-Test Steps

1. **Review Numbers**: Check RPS, success rate, hourly projection
2. **Verify Database**: `make db-shell` and check record count
3. **Check Logs**: `make artisan cmd="pail --lines=100"` for any errors
4. **Document**: Save results for comparison with future tests
5. **Analyze**: Identify any anomalies or concerns

---

## Success Criteria

✓ Test passes when:
- RPS ≥ 27 (specification is 28)
- Success Rate ≥ 95%
- Errors = 0 (or < 0.5%)
- Hourly Projection ≥ 100k
- Database records match test count

---

## Next Steps After Success

1. Run the 5-minute test for sustained verification
2. Try stress test to understand margin of safety
3. Document results for team reference
4. Plan production deployment
5. Set up continuous monitoring

---

**Time to Complete**: 5-15 minutes
**Difficulty**: Easy
**Prerequisites**: Docker running, containers up, migrations run

**Start Now**: `./load_test.sh 300 28`

---

**Last Updated**: 2024-12-03
**Valid For**: M4 MacBook and similar configurations
