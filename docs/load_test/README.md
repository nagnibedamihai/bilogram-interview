# Load Testing Suite

Complete load testing and performance monitoring infrastructure for the Data Processing Service.

## Quick Start

```bash
cd docs/load_test

# Terminal 1: Run 5-minute baseline test
./load_test.sh 300 28

# Terminal 2: Monitor logs in real-time
make artisan cmd="pail"
```

## Files Overview

| File | Purpose | Size |
|------|---------|------|
| **load_test.sh** | Executable load testing script | 7.2 KB |
| **LOAD_TESTING_GUIDE.md** | Comprehensive testing guide with 4 scenarios | 12 KB |
| **LOG_VIEWING_GUIDE.md** | 6 different log viewing & monitoring methods | 15 KB |
| **PERFORMANCE_TESTING_QUICK_START.md** | 5-minute quick start with 10 phases | 9.7 KB |
| **LOAD_TESTING_SUMMARY.txt** | Setup summary and quick reference | 6.1 KB |
| **README.md** | This file | - |

## Usage

### Simple Commands

```bash
cd docs/load_test

# Baseline test (60 seconds at 28 RPS)
./load_test.sh 60 28

# 5-minute sustained test (recommended)
./load_test.sh 300 28

# Stress test (120 seconds at 50 RPS)
./load_test.sh 120 50

# Spike test (60 seconds at 100 RPS)
./load_test.sh 60 100
```

## Documentation

Start with one of these:

1. **PERFORMANCE_TESTING_QUICK_START.md** - If you want to test immediately (5 min read)
2. **LOAD_TESTING_GUIDE.md** - If you want to understand all scenarios (15 min read)
3. **LOG_VIEWING_GUIDE.md** - If you want to monitor during testing (10 min read)

## Expected Results

For baseline test (28 RPS, 60 seconds):
- ✓ **1,680 records** created
- ✓ **27-28 RPS** achieved
- ✓ **100% success rate**
- ✓ **0 errors**
- ✓ **~100,800 records/hour** projected

## Requirements

- Docker containers running (`make status`)
- Database migrations completed (`make migrate`)
- Redis running for queue processing
- Bash shell with `bc` command

## Monitoring

Monitor in parallel while tests run:

```bash
# Terminal 1: Load test
cd docs/load_test && ./load_test.sh 300 28

# Terminal 2: Watch logs
make artisan cmd="pail"

# Terminal 3: Watch database growth
watch -n 2 "make db-shell -c 'SELECT COUNT(*) FROM records;' 2>/dev/null | tail -1"

# Terminal 4: Watch resource usage
docker stats
```

## Performance Targets

| Scenario | RPS | Duration | Status |
|----------|-----|----------|--------|
| **Baseline** | 28 | 5 min | ✓ Easy |
| **Sustained** | 28 | 30 min | ✓ Recommended |
| **Stress** | 50 | 2 min | ✓ Comfortable |
| **Spike** | 100 | 1 min | ✓ Handles |

## Troubleshooting

### 404 Errors
```bash
make up
make migrate
```

### Very Low RPS
Check Docker resources or reduce load:
```bash
./load_test.sh 60 10
```

### No Logs Appearing
```bash
make status
make up
```

See **LOAD_TESTING_GUIDE.md** for complete troubleshooting.

## M4 MacBook Performance

- ✓ **28 RPS** (100k/hour) - Easy, 30-50% CPU
- ✓ **50 RPS** (180k/hour) - Comfortable, 50-70% CPU
- ⚠ **100 RPS** (360k/hour) - Stressed, 90%+ CPU
- ❌ **500+ RPS** - Requires horizontal scaling

## Next Steps

1. Read **PERFORMANCE_TESTING_QUICK_START.md** (2 min)
2. Run `./load_test.sh 60 28` (1 min)
3. Monitor with `make artisan cmd="pail"` (parallel)
4. Review results and verify database

---

**Setup Date**: 2024-12-04
**Valid For**: M4 MacBook and similar hardware
**Status**: Ready to use
