# Booking System - Quick Start Guide

## Setup Instructions

### 1. Environment Configuration

Copy and update `.env.local`:
```env
NEXT_PUBLIC_SUPABASE_URL=https://YOUR_PROJECT.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=YOUR_ANON_KEY
NEXTAUTH_SECRET=generate-with: openssl rand -base64 32
NEXTAUTH_URL=http://localhost:3000
```

Get your Supabase credentials from the project settings.

### 2. Database Setup

The database schema has been applied via migrations. Tables created:
- `profiles` - User profiles linked to auth.users
- `services` - Appointment service types
- `time_slots` - Available booking time slots
- `bookings` - User reservations
- `admin_schedules` - Admin working hours

All tables have RLS (Row Level Security) policies enabled.

### 3. Install Dependencies

```bash
cd /vercel/share/booking-system
npm install
```

### 4. Run Development Server

```bash
npm run dev
```

Visit `http://localhost:3000`

## User Testing Workflow

### Step 1: Sign Up
1. Click "Sign Up" link
2. Enter email and password
3. Verify email (check inbox)
4. Login with credentials

### Step 2: Browse Services
1. Go to home page
2. See list of available services
3. Each shows: name, duration, price (if set)

### Step 3: Make a Booking
1. Click "Book Now" on a service
2. Select a date from calendar
3. Available time slots appear automatically
4. Click on a slot to select
5. Add optional notes
6. Click "Confirm Booking"
7. See success message
8. Booking appears in "My Bookings"

### Step 4: View Bookings
1. Go to "My Bookings" page
2. See all your confirmed bookings
3. Shows service name, date, time, status
4. Can cancel bookings (marks as cancelled)

### Step 5: Real-Time Updates
1. Open two browser tabs
2. In Tab 1: View available slots
3. In Tab 2: Make a booking for same service/date
4. Tab 1 automatically updates - slot now shows "Booked"
5. No page refresh needed

## Admin Testing Workflow

### Step 1: Admin Setup
1. Contact support or use database directly to set `is_admin = true` on a user
2. Or manually update in Supabase: `UPDATE profiles SET is_admin = true WHERE id = 'USER_ID'`
3. Re-login as admin user

### Step 2: Create Service
1. Go to Admin Dashboard
2. Click "Services"
3. Click "Add New Service"
4. Fill in:
   - Service name (e.g., "Haircut", "Consultation")
   - Description
   - Duration (minutes)
   - Price (optional)
5. Click "Create"

### Step 3: Create Time Slots
1. Go to Admin Dashboard
2. Click "Time Slots"
3. Select service
4. Choose date and time range
5. Set capacity (how many simultaneous bookings)
6. Click "Create Slot"
7. Repeat to create multiple slots

### Step 4: View All Bookings
1. Go to Admin Dashboard
2. Click "All Bookings"
3. See all user bookings across all services
4. Can filter by service, date, status
5. Can cancel any booking (frees up slot)
6. Can mark as "completed"

### Step 5: Manage Schedule
1. Go to Admin Dashboard
2. Click "Schedule"
3. Set working hours for each day
4. This helps users see when admin is available

## Testing Anti-Double-Booking

### Race Condition Test
1. Open same booking page in two browsers (different users)
2. Both select same time slot
3. Both click "Confirm" simultaneously
4. First request succeeds
5. Second request fails with: "Slot was just booked by another user"
6. Second user must choose different slot

### Load Test
```bash
# Using Apache Bench or curl
ab -n 10 -c 5 http://localhost:3000/api/bookings
```

This simulates 10 concurrent booking requests for same slot. Only 1 succeeds, rest fail with 409.

## Common Operations

### Create Test Data

Admin creates sample services and slots:

```bash
# Via curl to POST /api/services
curl -X POST http://localhost:3000/api/services \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Consultation",
    "description": "30-minute consultation",
    "duration_minutes": 30,
    "price": 50
  }'

# Create time slot via POST /api/time-slots
curl -X POST http://localhost:3000/api/time-slots \
  -H "Content-Type: application/json" \
  -d '{
    "service_id": "uuid-here",
    "start_time": "2026-05-10T09:00:00Z",
    "end_time": "2026-05-10T09:30:00Z",
    "capacity": 3
  }'
```

### Check Slot Availability

```bash
curl "http://localhost:3000/api/time-slots?service_id=uuid&start_date=2026-05-10"
```

Returns only slots where `booked_count < capacity`

### View Bookings as Admin

```bash
curl http://localhost:3000/api/admin/bookings
```

Returns all bookings with user and service details.

### Cancel Booking

```bash
curl -X PUT http://localhost:3000/api/admin/bookings \
  -H "Content-Type: application/json" \
  -d '{
    "booking_id": "uuid",
    "status": "cancelled"
  }'
```

Automatically decrements `booked_count` on time slot.

## Debugging

### Enable Real-Time Logs
```typescript
// In client code
const subscription = supabase
  .channel('bookings')
  .on('postgres_changes',
    { event: '*', schema: 'public', table: 'bookings' },
    (payload) => console.log('[v0] Booking update:', payload)
  )
  .subscribe()
```

### Check Database
1. Go to Supabase Dashboard
2. SQL Editor
3. Run queries:
```sql
-- View all services
SELECT * FROM services;

-- View all time slots with availability
SELECT id, service_id, start_time, capacity, booked_count 
FROM time_slots 
WHERE booked_count < capacity;

-- View all bookings
SELECT b.id, b.user_id, s.name, b.status, ts.start_time
FROM bookings b
JOIN services s ON b.service_id = s.id
JOIN time_slots ts ON b.time_slot_id = ts.id
ORDER BY ts.start_time DESC;
```

### Common Issues

**Issue**: Users can't see services
- Check `services` table has data
- Verify RLS policy allows SELECT for all users
- Check browser console for API errors

**Issue**: Can't create bookings
- Verify user is authenticated
- Check time slot exists and has available capacity
- Check error response (409 if slot full, 401 if not auth)

**Issue**: Admin can't see all bookings
- Verify user has `is_admin = true` in profiles
- Check RLS policy on bookings allows admin SELECT

**Issue**: Real-time updates not working
- Enable "Realtime" in Supabase project settings
- Check browser WebSocket connection
- Verify `NEXT_PUBLIC_SUPABASE_URL` is correct

## Next Steps

1. Implement payment processing
2. Add email notifications
3. Create booking reminders
4. Add calendar view (week/month)
5. Implement waitlist for full slots
6. Add rating/review system
7. Multi-service booking (packages)
8. Recurring bookings
9. Booking cancellation policies
10. Integration with calendar apps (Google Calendar, Outlook)

## Support Files

- **BOOKING_SYSTEM_COMPLETE_GUIDE.md** - Full technical documentation
- **API Routes** - `/app/api/services`, `/app/api/time-slots`, `/app/api/bookings`, `/app/api/admin/*`
- **Database** - Schema with RLS policies applied
- **Auth** - Supabase Auth configured with email/password
