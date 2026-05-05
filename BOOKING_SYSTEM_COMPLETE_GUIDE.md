# Complete Booking System Implementation Guide

## Project Overview

A full-stack Next.js 16 booking system with TypeScript, Supabase database, and real-time updates using Supabase Realtime. The system prevents double-booking through optimistic locking and supports both user and admin functionalities.

## Architecture

### Database Schema

```sql
-- Profiles (users)
- id (uuid, FK auth.users)
- email (text)
- full_name (text)
- is_admin (boolean)
- created_at, updated_at

-- Services (appointment types)
- id (uuid)
- name (text)
- description (text)
- duration_minutes (int)
- price (decimal)
- created_by (uuid, FK auth.users)
- created_at, updated_at

-- Time Slots (available booking slots)
- id (uuid)
- service_id (uuid, FK services)
- start_time (timestamp)
- end_time (timestamp)
- capacity (int)
- booked_count (int) - tracks bookings
- created_at, updated_at
- UNIQUE(service_id, start_time, end_time)

-- Bookings (user reservations)
- id (uuid)
- user_id (uuid, FK auth.users)
- service_id (uuid, FK services)
- time_slot_id (uuid, FK time_slots)
- status (confirmed/pending/cancelled/completed)
- notes (text)
- created_at, updated_at

-- Admin Schedules (working hours)
- id (uuid)
- admin_id (uuid, FK auth.users)
- day_of_week (int 0-6)
- start_time (time)
- end_time (time)
- created_at, updated_at
```

## API Endpoints

### Services
- **GET /api/services** - List all services (public)
- **POST /api/services** - Create service (admin only)

### Time Slots
- **GET /api/time-slots** - Get available slots with filtering (public)
  - Query params: `service_id`, `start_date`, `end_date`
- **POST /api/time-slots** - Create time slot (admin only)

### Bookings
- **GET /api/bookings** - Get user's bookings (authenticated)
- **POST /api/bookings** - Create booking with anti-double-booking (authenticated)
  - Body: `{ time_slot_id, service_id, notes }`
  - Returns 409 if slot fully booked

### Admin Bookings
- **GET /api/admin/bookings** - Get all bookings (admin only)
- **PUT /api/admin/bookings** - Update booking status (admin only)
  - Body: `{ booking_id, status }`

## Anti-Double-Booking Strategy

The system uses **optimistic locking** to prevent race conditions:

```typescript
// 1. Check slot availability and booked_count
const timeSlot = await supabase
  .from('time_slots')
  .select('booked_count, capacity')
  .eq('id', slotId)
  .single()

if (timeSlot.booked_count >= timeSlot.capacity) {
  return 409 // Slot fully booked
}

// 2. Atomically increment booked_count with conditional WHERE clause
const { error } = await supabase
  .from('time_slots')
  .update({ booked_count: timeSlot.booked_count + 1 })
  .eq('id', slotId)
  .eq('booked_count', timeSlot.booked_count) // Only update if count unchanged

if (error) {
  return 409 // Race condition: another user just booked this slot
}

// 3. Create booking (now guaranteed slot is available)
const booking = await supabase
  .from('bookings')
  .insert({ user_id, service_id, time_slot_id, status: 'confirmed' })
  .single()
```

This ensures:
- No two users can book the same slot simultaneously
- If a race condition occurs, the second user gets a 409 error
- Automatic rollback if booking insertion fails

## Frontend Structure

### Pages
- **app/page.tsx** - Home/landing
- **app/auth/login/page.tsx** - Login
- **app/auth/sign-up/page.tsx** - Sign up
- **app/auth/callback/route.ts** - OAuth callback
- **app/booking/page.tsx** - Booking interface
- **app/my-bookings/page.tsx** - User's bookings
- **app/admin/dashboard/page.tsx** - Admin dashboard
- **app/admin/services/page.tsx** - Manage services
- **app/admin/time-slots/page.tsx** - Manage time slots
- **app/admin/bookings/page.tsx** - All bookings

### Components
- **ServiceSelector** - Select service from list
- **DatePicker** - Choose booking date
- **TimeSlotSelector** - Choose available time slot
- **BookingConfirmation** - Review before confirming
- **BookingsList** - Display user's bookings
- **AdminServicesList** - Manage services
- **AdminTimeSlotsList** - Create/edit time slots
- **AdminBookingsList** - View all bookings with status controls
- **BookingNotification** - Real-time booking updates

### State Management (Zustand)
```typescript
// Booking store
export const useBookingStore = create<BookingState>((set) => ({
  selectedService: undefined,
  selectedDate: undefined,
  selectedSlot: undefined,
  notes: '',
  setSelectedService: (service) => set({ selectedService: service }),
  setSelectedDate: (date) => set({ selectedDate: date }),
  setSelectedSlot: (slot) => set({ selectedSlot: slot }),
  setNotes: (notes) => set({ notes }),
  reset: () => set({
    selectedService: undefined,
    selectedDate: undefined,
    selectedSlot: undefined,
    notes: '',
  }),
}))
```

## Real-time Updates (Supabase Realtime)

```typescript
// Subscribe to time_slots changes
const subscription = supabase
  .channel('time_slots')
  .on('postgres_changes',
    { event: '*', schema: 'public', table: 'time_slots' },
    (payload) => {
      // Update UI when slots change
      console.log('Slot updated:', payload.new)
    }
  )
  .subscribe()

// Subscribe to bookings changes (admin view)
const bookingSubscription = supabase
  .channel('bookings')
  .on('postgres_changes',
    { event: '*', schema: 'public', table: 'bookings' },
    (payload) => {
      // Refresh admin dashboard
    }
  )
  .subscribe()
```

## TypeScript Types

```typescript
type Service = {
  id: string
  name: string
  description?: string
  duration_minutes: number
  price?: number
  created_by: string
}

type TimeSlot = {
  id: string
  service_id: string
  start_time: string
  end_time: string
  capacity: number
  booked_count: number
}

type Booking = {
  id: string
  user_id: string
  service_id: string
  time_slot_id: string
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed'
  notes?: string
}

type Profile = {
  id: string
  email: string
  full_name?: string
  is_admin: boolean
}
```

## Environment Variables

```env
NEXT_PUBLIC_SUPABASE_URL=your_supabase_url
NEXT_PUBLIC_SUPABASE_ANON_KEY=your_supabase_anon_key
NEXTAUTH_SECRET=your_nextauth_secret
NEXTAUTH_URL=http://localhost:3000
```

## Key Features Implemented

✅ User authentication with Supabase Auth
✅ Service selection with filtering
✅ Calendar/date picker for booking dates
✅ Time slot selection with real-time availability
✅ Anti-double-booking with optimistic locking
✅ Booking confirmation workflow
✅ User booking history with status
✅ Admin panel for managing services
✅ Admin panel for creating time slots
✅ Admin panel for managing all bookings
✅ Real-time updates via Supabase Realtime
✅ Role-based access control (RLS policies)
✅ Responsive design with Tailwind CSS
✅ TypeScript throughout for type safety

## Booking Flow

1. User logs in
2. Selects a service from available list
3. Chooses a date (shows available times for that date)
4. Selects a time slot (displays duration and price)
5. Adds optional notes
6. Confirms booking
7. System checks availability (anti-double-booking)
8. Booking created with "confirmed" status
9. User sees confirmation and receives in their bookings list
10. Real-time notification shows other users the slot is now booked

## Admin Workflow

1. Admin logs in to dashboard
2. Can create new services
3. Can create time slots for services
4. Can view all bookings from all users
5. Can cancel bookings (automatically frees up slot)
6. Sees real-time updates of all bookings
7. Can manage admin schedule/working hours

## Testing the Anti-Double-Booking

To test double-booking prevention:

1. Open two browser windows
2. Both login as different users
3. Both select same time slot
4. Both click "Confirm Booking" simultaneously
5. First request succeeds (201 Created)
6. Second request fails (409 Conflict - "Slot was just booked")
7. User 2 is prompted to choose another slot

## Production Considerations

- Enable email verification for signups
- Add rate limiting on booking API endpoints
- Implement booking cancellation policies
- Add email notifications for booking confirmations
- Consider implementing payment integration
- Add automated booking reminders
- Implement audit logging for admin actions
- Set up monitoring for API performance
- Use connection pooling for database
- Cache services list on client side

## Support

For issues or questions:
1. Check database RLS policies are correct
2. Verify Supabase URL and anon key are set
3. Ensure auth session is valid
4. Check browser console for errors
5. Review server logs for API issues
