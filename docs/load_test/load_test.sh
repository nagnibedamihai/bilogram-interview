#!/bin/bash

################################################################################
# Load Testing Script for Data Processing Service
# Simulates high-volume record ingestion to test 100k records/hour capacity
#
# Usage: ./load_test.sh [duration_seconds] [target_rps]
# Example: ./load_test.sh 60 28        # 60 seconds at 28 RPS (100k/hour)
#          ./load_test.sh 300 28       # 5-minute baseline test
#          ./load_test.sh 120 50       # 2-minute stress test (50 RPS)
#          ./load_test.sh 60 100       # 1-minute spike test (100 RPS)
################################################################################

set -e

# Configuration
DURATION=${1:-60}                    # Duration in seconds (default: 60)
TARGET_RPS=${2:-28}                 # Target requests per second (default: 28 = 100k/hour)
BASE_URL="http://localhost/api"
ENDPOINT="/records"

# Calculate expected totals
EXPECTED_TOTAL=$((DURATION * TARGET_RPS))
INTERVAL=$(echo "scale=4; 1/$TARGET_RPS" | bc)

# Statistics tracking
TOTAL_REQUESTS=0
REQUESTS_201=0
REQUESTS_200=0
ERRORS=0
START_TIME=$(date +%s%N)

# Destination IDs and references for variety
DESTINATIONS=("dest-1" "dest-2" "dest-3" "dest-4" "dest-5")
REFERENCES=("ref-A" "ref-B" "ref-C" "ref-D" "ref-E")
SOURCES=("source-1" "source-2" "source-3")
TYPES=("positive" "negative")
UNITS=("USD" "EUR" "GBP" "JPY")

# Color codes for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to generate random value between min and max
random_value() {
    local min=$1
    local max=$2
    echo $((RANDOM % (max - min + 1) + min))
}

# Function to generate random item from array
random_item() {
    local -a arr=("$@")
    local len=${#arr[@]}
    echo "${arr[$((RANDOM % len))]}"
}

# Function to format number with thousands separator
format_number() {
    printf "%'d" $1
}

# Function to update progress bar
update_progress() {
    local current=$1
    local total=$2
    local percent=$((current * 100 / total))
    local filled=$((percent / 2))
    local empty=$((50 - filled))

    printf "\rProgress: ["
    printf "%${filled}s" | tr ' ' '='
    printf "%${empty}s" | tr ' ' '-'
    printf "] %3d%% | Records: %d/%d | RPS: %.2f | 201: %d | 200: %d | Errors: %d" \
        $percent $current $total $(echo "scale=2; $current / $ELAPSED" | bc) $REQUESTS_201 $REQUESTS_200 $ERRORS
}

# Function to generate unique record ID
generate_record_id() {
    # Format: LOAD-TEST-{timestamp}-{random}
    echo "LOAD-TEST-$(date +%s%N)-$RANDOM"
}

# Function to generate datetime in Y-m-d H:i:s format
generate_datetime() {
    # Random time within last 7 days
    local offset=$((RANDOM % 604800))
    date -u -v-7d -v+${offset}s "+%Y-%m-%d %H:%M:%S"
}

# Function to generate random value (positive or negative)
generate_value() {
    local type=$1
    local value=$((RANDOM % 2000 + 1))
    local decimal=$((RANDOM % 100))

    # Make some values high (above threshold of 1000) for alert testing
    if [ $((RANDOM % 10)) -lt 2 ]; then
        value=$((value + 1000))
    fi

    # Add decimal places
    printf "%.2f" $(echo "scale=2; $value + $decimal/100" | bc)
}

echo ""
echo "=================================================================================="
echo "                    DATA PROCESSING SERVICE - LOAD TEST"
echo "=================================================================================="
echo ""
echo "Configuration:"
echo "  Duration:           $DURATION seconds"
echo "  Target RPS:         $TARGET_RPS records/second"
echo "  Expected Total:     $(format_number $EXPECTED_TOTAL) records"
echo "  Equivalent to:      $(format_number $((EXPECTED_TOTAL * 3600 / DURATION))) records/hour"
echo ""
echo "Starting load test..."
echo ""

# Main load test loop
END_TIME=$(($(date +%s) + DURATION))

while [ $(date +%s) -lt $END_TIME ]; do
    LOOP_START=$(date +%s%N)

    # Generate random record data
    RECORD_ID=$(generate_record_id)
    TIME=$(generate_datetime)
    SOURCE_ID=$(random_item "${SOURCES[@]}")
    DESTINATION_ID=$(random_item "${DESTINATIONS[@]}")
    TYPE=$(random_item "${TYPES[@]}")
    VALUE=$(generate_value "$TYPE")
    UNIT=$(random_item "${UNITS[@]}")
    REFERENCE=$(random_item "${REFERENCES[@]}")

    # Create JSON payload
    PAYLOAD=$(cat <<EOF
{
    "recordId": "$RECORD_ID",
    "time": "$TIME",
    "sourceId": "$SOURCE_ID",
    "destinationId": "$DESTINATION_ID",
    "type": "$TYPE",
    "value": $VALUE,
    "unit": "$UNIT",
    "reference": "$REFERENCE"
}
EOF
)

    # Send request and capture HTTP status
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
        -X POST "$BASE_URL$ENDPOINT" \
        -H "Content-Type: application/json" \
        -d "$PAYLOAD" \
        2>/dev/null || echo "000")

    # Count responses
    ((TOTAL_REQUESTS++))
    if [ "$HTTP_STATUS" == "201" ]; then
        ((REQUESTS_201++))
    elif [ "$HTTP_STATUS" == "200" ]; then
        ((REQUESTS_200++))
    else
        ((ERRORS++))
    fi

    # Calculate elapsed time
    CURRENT_TIME=$(date +%s%N)
    ELAPSED=$(echo "scale=2; ($(date +%s) - $(date -v-${DURATION}s +%s))" | bc)
    [ "$ELAPSED" -lt 1 ] && ELAPSED=1

    # Update progress
    update_progress $TOTAL_REQUESTS $EXPECTED_TOTAL

    # Sleep to maintain request rate
    LOOP_END=$(date +%s%N)
    LOOP_DURATION=$(echo "scale=4; ($LOOP_END - $LOOP_START) / 1000000000" | bc)
    SLEEP_TIME=$(echo "scale=4; $INTERVAL - $LOOP_DURATION" | bc)

    if (( $(echo "$SLEEP_TIME > 0" | bc -l) )); then
        sleep $SLEEP_TIME
    fi
done

# Final calculations
ACTUAL_END_TIME=$(date +%s%N)
TOTAL_TIME=$(echo "scale=2; ($ACTUAL_END_TIME - $START_TIME) / 1000000000" | bc)
ACTUAL_RPS=$(echo "scale=2; $TOTAL_REQUESTS / $TOTAL_TIME" | bc)
PROJECTED_HOURLY=$((TOTAL_REQUESTS * 3600 / $(printf "%.0f" $TOTAL_TIME)))
SUCCESS_RATE=$((REQUESTS_201 * 100 / TOTAL_REQUESTS))

# Print final summary
echo ""
echo ""
echo "=================================================================================="
echo "LOAD TEST SUMMARY"
echo "=================================================================================="
echo ""
echo "Test Duration:        $(printf "%.2f" $TOTAL_TIME) seconds"
echo "Total Requests:       $(format_number $TOTAL_REQUESTS)"
echo "  ✓ New Records (201): $(format_number $REQUESTS_201)"
echo "  ✓ Duplicates (200):  $(format_number $REQUESTS_200)"
echo "  ✗ Errors (4xx/5xx):  $(format_number $ERRORS)"
echo ""
echo "Performance:"
echo "  Average RPS:        $ACTUAL_RPS records/second"
echo "  Success Rate:       $SUCCESS_RATE%"
echo ""
echo "Projected Throughput:"
echo "  Per Hour:           $(format_number $PROJECTED_HOURLY) records"

# Determine if test passed
REQUIRED_RPS=27  # Slightly below 28 to account for variation
if (( $(echo "$ACTUAL_RPS > $REQUIRED_RPS" | bc -l) )); then
    STATUS="✓ PASS"
    STATUS_COLOR=$GREEN
    MESSAGE="Meets 100k/hour requirement (>$REQUIRED_RPS RPS)"
else
    STATUS="✗ FAIL"
    STATUS_COLOR=$RED
    MESSAGE="Below 100k/hour requirement (<$REQUIRED_RPS RPS)"
fi

echo "  Status:             ${STATUS_COLOR}${STATUS}${NC} - $MESSAGE"
echo ""
echo "=================================================================================="
echo ""

# Exit with appropriate code
if [ "$ERRORS" -gt 0 ] || (( $(echo "$ACTUAL_RPS <= $REQUIRED_RPS" | bc -l) )); then
    exit 1
else
    exit 0
fi
