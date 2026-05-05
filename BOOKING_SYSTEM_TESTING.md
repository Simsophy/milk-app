# Booking System - Testing Guide

## Pre-Launch Testing Checklist

### 1. Anti-Double-Booking Testing (CRITICAL)

#### Test Case 1: Single User, Rapid Clicks
1. Open booking page in 1 browser
2. Select service, date, time slot
3. Rapidly click "Book" button 3 times in quick succession
4. Expected: Only 1 booking created, others rejected with "Slot full" error

**SQL to verify:**
```sql
SELECT COUNT(*) FROM bookings WHERE time_slot_id = 'xxx' AND user_id = 'yyy';
-- Should return 1
```

#### Test Case 2: Concurrent Users (Simulated)
1. Open booking page in 2 separate browser windows
2. Both select same service/date/time slot
3. Click Book in both simultaneously (within 1 second)
4. Expected: 1 succeeds, 1 shows "Slot booked by another user" error

**How to test:**
- Use browser DevTools to throttle network (slow 3G)
- Increase race condition likelihood
- Verify only 1 booking per slot

#### Test Case 3: Over-Capacity Protection
1. Create time slot with capacity=2
2. Create 2 bookings successfully
3. Attempt 3rd booking same slot
4. Expected: "Slot full" error at step 3

#### Test Case 4: Slot Disappears During Booking
1. Open booking flow, select all steps
2. In another window, admin cancels the time slot
3. Try to confirm booking
4. Expected: Error "Time slot no longer available"

### 2. User Flow Testing

#### Registration & Login
- [ ] Email/password registration works
- [ ] Email confirmation (if enabled)
- [ ] Login/logout functionality
- [ ] Session persists on refresh
- [ ] Role detection (admin vs user)

#### Booking Flow
- [ ] Service selection loads all services
- [ ] Calendar picker shows correct dates
- [ ] Time slot selector loads available slots
- [ ] Slot availability updates in real-time
- [ ] Booking confirmation shows correct details
- [ ] Cancel booking removes slot from availability
- [ ] Can't book already booked slots

#### Dashboard
- [ ] User sees only their bookings
- [ ] Bookings show service details
- [ ] Can cancel future bookings
- [ ] Can't cancel past bookings
- [ ] Real-time updates when other users book

### 3. Admin Panel Testing

#### Services Management
- [ ] Admin can create services
- [ ] Services visible to users immediately
- [ ] Admin can edit service details
- [ ] Admin can delete services
- [ ] Deleting service cascades to bookings (handled properly)

#### Time Slot Management
- [ ] Admin can create slots
- [ ] Can set capacity per slot
- [ ] Can view all upcoming slots
- [ ] Can disable/delete slots
- [ ] Recurring slot generation works

#### Bookings Management
- [ ] Admin sees all user bookings
- [ ] Can filter by service/date/status
- [ ] Can cancel bookings
- [ ] Can view user details
- [ ] Cancellation decrements booked_count

### 4. API Testing

#### Using curl or Postman:

**Create Booking:**
```bash
curl -X POST http://localhost:3000/api/bookings \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "time_slot_id": "uuid",
    "service_id": "uuid",
    "notes": "Test booking"
  }'
```

**Expected Success Response (201):**
```json
{
  "success": true,
  "data": {
    "id": "booking-uuid",
    "user_id": "user-uuid",
    "service_id": "service-uuid",
    "time_slot_id": "slot-uuid",
    "status": "confirmed",
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

**Expected Race Condition Response (409):**
```json
{
  "success": false,
  "message": "Slot was just booked by another user. Please try another slot."
}
```

### 5. Real-time Updates Testing

#### Supabase Realtime

**Test Slot Updates:**
1. User 1 opens booking page
2. User 2 books the last available slot
3. Expected: User 1's slot list updates within 2 seconds
4. Verify slot no longer appears as available

**To verify in browser console:**
```javascript
// Check Supabase realtime connection
const channel = supabase
  .channel('bookings')
  .on('*', payload => {
    console.log('[v0] Realtime update:', payload)
  })
  .subscribe()

// Simulate booking in another window
// Should see console.log in real-time
```

### 6. Edge Cases & Error Handling

#### Test Cases:
- [ ] Logout during booking → redirect to login
- [ ] Delete service with active bookings
- [ ] Admin cancels slot while user is booking
- [ ] Network timeout during booking submission
- [ ] Slot capacity changes mid-booking
- [ ] User role changes (user → admin)
- [ ] Invalid time slot selection
- [ ] Past date selection (should be disabled)
- [ ] Duplicate booking prevention (same user, same slot)

### 7. Performance Testing

#### Benchmarks:
- Load services list: **< 500ms**
- Load available slots: **< 800ms**
- Create booking: **< 1000ms** (includes DB transaction)
- List user bookings: **< 600ms**
- Admin list all bookings: **< 1500ms** (may be large)

**How to test:**
```javascript
// In browser console
console.time('bookings-load');
await fetch('/api/bookings').then(r => r.json());
console.timeEnd('bookings-load');
```

### 8. Security Testing

#### Tests:
- [ ] User can't see other user's bookings (401)
- [ ] User can't modify other user's bookings (403)
- [ ] Non-admin can't access admin endpoints (403)
- [ ] CSRF tokens work for form submissions
- [ ] XSS prevention (sanitize all user inputs)
- [ ] SQL injection prevention (use parameterized queries)
- [ ] Rate limiting prevents booking spam
- [ ] Session token expiration works

### 9. Mobile & Responsive Testing

#### Devices:
- [ ] iPhone 12/13/14 (375px)
- [ ] iPad (768px)
- [ ] Desktop (1920px)

#### Test Cases:
- [ ] Calendar picker usable on mobile
- [ ] Time slot grid responsive
- [ ] Navigation menu works on mobile
- [ ] Form inputs are touch-friendly (min 44px)
- [ ] Images load properly

### 10. Database Integrity Testing

#### SQL Checks:

**Check for orphaned bookings:**
```sql
SELECT COUNT(*) FROM bookings b
WHERE NOT EXISTS (SELECT 1 FROM time_slots WHERE id = b.time_slot_id);
-- Should return 0
```

**Check for over-booked slots:**
```sql
SELECT time_slot_id, COUNT(*) as booking_count, 
       (SELECT capacity FROM time_slots WHERE id = ts.time_slot_id) as capacity
FROM bookings b
JOIN time_slots ts ON ts.id = b.time_slot_id
WHERE status = 'confirmed'
GROUP BY time_slot_id
HAVING COUNT(*) > ts.capacity;
-- Should return 0 rows
```

**Check booked_count accuracy:**
```sql
SELECT ts.id, ts.booked_count,
       COUNT(b.id) as actual_count
FROM time_slots ts
LEFT JOIN bookings b ON b.time_slot_id = ts.id AND b.status = 'confirmed'
GROUP BY ts.id
HAVING ts.booked_count != COUNT(b.id);
-- Should return 0 rows
```

---

## Stress Testing (Load Testing)

### Using Apache Bench or LoadTest:

```bash
# Test booking creation under load
ab -n 100 -c 10 -p booking.json -T application/json \
   http://localhost:3000/api/bookings

# This will:
# - Send 100 requests
# - 10 concurrent connections
# - Useful to identify race conditions
```

### Expected Results:
- Most requests should succeed
- Some should fail with 409 (slot full)
- No requests should fail with 500 error
- Response times should stay < 2s even under load

---

## Deployment Testing

Before going live:

- [ ] Test on staging environment
- [ ] Run full test suite
- [ ] Load test with 100+ concurrent users
- [ ] Verify database backups work
- [ ] Test rollback procedure
- [ ] Verify monitoring/alerts configured
- [ ] Test error logging system
- [ ] Verify email notifications work
- [ ] Test on all major browsers
- [ ] Verify SSL certificate

---

## Monitoring in Production

### Key Metrics:
1. **Booking creation success rate** (target: >99%)
2. **API response times** (target: <1s)
3. **Failed bookings due to conflicts** (target: <2%)
4. **Database query performance** (target: <100ms)
5. **Realtime update latency** (target: <2s)

### Alerts to Set:
- API error rate > 1%
- Booking conflicts > 5%
- Response time > 2s
- Database connection pool exhaustion
- Realtime subscription failures

---

## Common Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| "Slot full" when capacity available | Race condition | Use optimistic locking pattern |
| Realtime updates not showing | WebSocket connection issue | Check Supabase realtime enabled |
| Can book past slots | Date validation missing | Validate date >= today() |
| Duplicate bookings for user | Missing UNIQUE constraint | Add to database |
| Admin can't see bookings | RLS policy issue | Check admin role in profile |
| Slow slot loading | Missing indexes | Add indexes on service_id, start_time |

---

**After passing all tests, you're ready for production deployment!**
