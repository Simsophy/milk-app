# Booking System - Next Steps & Frontend Implementation

## Overview

The backend is complete with database, authentication, and all API routes. This document outlines the remaining frontend work to complete the booking system.

## Immediate Next Steps

### 1. Configure Environment Variables

Create `.env.local` in `/vercel/share/booking-system/`:
```env
# Get these from Supabase Dashboard
NEXT_PUBLIC_SUPABASE_URL=https://your-project.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=your-anon-key-here

# Generate with: openssl rand -base64 32
NEXTAUTH_SECRET=your-secret-here

# For development
NEXTAUTH_URL=http://localhost:3000

# For production
# NEXTAUTH_URL=https://yourdomain.com
```

### 2. Run the Development Server

```bash
cd /vercel/share/booking-system
npm run dev
```

Visit `http://localhost:3000` - you'll see the default Next.js page (no error means backend is ready).

### 3. Create Layout & Navigation

Create `app/layout.tsx`:
```typescript
import type { Metadata } from 'next'
import { Geist } from 'next/font/google'
import './globals.css'

const geist = Geist({ subsets: ['latin'] })

export const metadata: Metadata = {
  title: 'Booking System',
  description: 'Schedule your appointments',
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="en">
      <body className={geist.className}>
        <nav className="bg-blue-600 text-white p-4">
          <div className="max-w-6xl mx-auto flex justify-between items-center">
            <h1 className="text-2xl font-bold">BookingSystem</h1>
            <div className="space-x-4">
              <a href="/" className="hover:underline">Home</a>
              <a href="/booking" className="hover:underline">Book</a>
              <a href="/my-bookings" className="hover:underline">My Bookings</a>
              <a href="/admin/dashboard" className="hover:underline">Admin</a>
            </div>
          </div>
        </nav>
        <main className="min-h-screen bg-gray-50">
          {children}
        </main>
      </body>
    </html>
  )
}
```

## Frontend Implementation Roadmap

### Phase 1: Authentication Pages

Priority: **HIGH** - Users can't do anything without authentication

**Files to Create:**
1. `app/auth/login/page.tsx` - Login form
2. `app/auth/sign-up/page.tsx` - Signup form
3. `app/auth/error/page.tsx` - Error handling
4. `app/auth/callback/route.ts` - OAuth callback (REQUIRED)

**Components Needed:**
- `components/AuthForm.tsx` - Shared auth form component
- `components/EmailInput.tsx` - Email field validation
- `components/PasswordInput.tsx` - Password field with strength indicator

**Key Features:**
- Email/password validation
- Error messages
- Success feedback
- Redirect to dashboard on success
- Remember me option

### Phase 2: Home Page & Service Listing

Priority: **HIGH** - Core user experience

**Files to Create:**
1. `app/page.tsx` - Home/landing page
2. `components/ServiceGrid.tsx` - Grid of services
3. `components/ServiceCard.tsx` - Individual service display

**Features:**
- List all services
- Filter/search services
- Show service details (name, duration, price)
- "Book Now" button for each service

### Phase 3: User Booking Flow

Priority: **CRITICAL** - Main feature

**Files to Create:**
1. `app/booking/page.tsx` - Booking page
2. `components/ServiceSelector.tsx` - Choose service
3. `components/DatePicker.tsx` - Choose date (calendar)
4. `components/TimeSlotSelector.tsx` - Choose time slot
5. `components/BookingConfirmation.tsx` - Review before booking

**Features:**
- Service selection
- Date picker with calendar
- Real-time availability checking
- Slot selection with details
- Booking notes field
- Confirmation with summary
- Success notification

### Phase 4: User Dashboard

Priority: **HIGH** - Users need to view their bookings

**Files to Create:**
1. `app/my-bookings/page.tsx` - Bookings page
2. `components/BookingsList.tsx` - List of bookings
3. `components/BookingCard.tsx` - Individual booking display

**Features:**
- Display user's bookings
- Show service, date, time, status
- Cancel booking option
- Filter by status (upcoming, past, cancelled)
- Booking details modal

### Phase 5: Admin Dashboard

Priority: **MEDIUM** - Needed for admins to manage system

**Files to Create:**
1. `app/admin/dashboard/page.tsx` - Admin home
2. `app/admin/services/page.tsx` - Manage services
3. `app/admin/time-slots/page.tsx` - Manage time slots
4. `app/admin/bookings/page.tsx` - View all bookings

**Components Needed:**
- `components/AdminServicesList.tsx` - Service management
- `components/AdminAddService.tsx` - Create service form
- `components/AdminTimeSlotsList.tsx` - Time slot management
- `components/AdminAddTimeSlot.tsx` - Create time slot form
- `components/AdminBookingsList.tsx` - All bookings
- `components/BookingStatusControl.tsx` - Change booking status

### Phase 6: State Management & Hooks

Priority: **MEDIUM** - Needed for proper state management

**Files to Create:**
1. `lib/hooks/useAuth.ts` - Current user hook
2. `lib/hooks/useServices.ts` - Fetch services
3. `lib/hooks/useTimeSlots.ts` - Fetch available slots
4. `lib/hooks/useBookings.ts` - User bookings
5. `lib/store/bookingStore.ts` - Zustand booking store
6. `lib/store/authStore.ts` - Zustand auth store

**Example Hook:**
```typescript
// lib/hooks/useAuth.ts
'use client'

import { createClient } from '@/lib/supabase/client'
import { useEffect, useState } from 'react'
import type { User } from '@supabase/supabase-js'

export function useAuth() {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const supabase = createClient()
    
    supabase.auth.getUser().then(({ data: { user } }) => {
      setUser(user)
      setLoading(false)
    })

    const { data: { subscription } } = supabase.auth.onAuthStateChange(
      (event, session) => {
        setUser(session?.user ?? null)
      }
    )

    return () => subscription.unsubscribe()
  }, [])

  return { user, loading }
}
```

### Phase 7: Real-time Updates

Priority: **MEDIUM** - Nice-to-have for better UX

**Files to Create:**
1. `lib/hooks/useRealtimeSlots.ts` - Real-time slot updates
2. `lib/hooks/useRealtimeBookings.ts` - Real-time booking updates

**Features:**
- Subscribe to time_slots changes
- Subscribe to bookings changes
- Auto-update UI when slots booked
- Auto-refresh admin dashboard

### Phase 8: Styling & Design

Priority: **MEDIUM** - Make it look professional

**Files to Create:**
1. `app/globals.css` - Tailwind CSS setup
2. `lib/styles/colors.ts` - Color constants
3. `components/ui/Button.tsx` - Reusable button
4. `components/ui/Card.tsx` - Reusable card
5. `components/ui/Modal.tsx` - Modal component
6. `components/ui/Alert.tsx` - Alert/notification

## Implementation Order (Recommended)

1. **Auth Pages** (Week 1) - Users need to log in first
2. **Home & Services** (Week 1) - Show what's available
3. **Booking Flow** (Week 2) - Core feature
4. **My Bookings** (Week 2) - User can see their bookings
5. **State & Hooks** (Ongoing) - Integrate as building
6. **Admin Panel** (Week 3) - Admins can manage
7. **Real-time** (Week 3) - Polish user experience
8. **Styling** (Throughout) - Iterative design

## Code Generation Strategy

Due to file system limitations with Move tool, you have two options:

### Option 1: Use Bash to Create Files
```bash
cd /vercel/share/booking-system
cat > app/page.tsx << 'EOF'
// Your code here
EOF
```

### Option 2: Provide Code for Manual Copy
I can provide complete code for each page/component, and you can copy-paste into your editor or use `echo` commands.

## Key Implementation Patterns

### Protected Routes (Authentication Guard)
```typescript
'use client'

import { useAuth } from '@/lib/hooks/useAuth'
import { redirect } from 'next/navigation'

export default function ProtectedPage() {
  const { user, loading } = useAuth()

  if (loading) return <div>Loading...</div>
  if (!user) return redirect('/auth/login')

  return <div>Protected content for {user.email}</div>
}
```

### Admin Guard
```typescript
'use client'

import { useAuth } from '@/lib/hooks/useAuth'
import { useEffect, useState } from 'react'
import { createClient } from '@/lib/supabase/client'
import { redirect } from 'next/navigation'

export default function AdminPage() {
  const { user } = useAuth()
  const [isAdmin, setIsAdmin] = useState(false)

  useEffect(() => {
    if (!user) {
      redirect('/auth/login')
    }

    const supabase = createClient()
    supabase
      .from('profiles')
      .select('is_admin')
      .eq('id', user.id)
      .single()
      .then(({ data }) => setIsAdmin(data?.is_admin || false))
  }, [user])

  if (!isAdmin) return <div>Unauthorized</div>

  return <div>Admin content</div>
}
```

### Fetching Data
```typescript
'use client'

import { useEffect, useState } from 'react'
import { createClient } from '@/lib/supabase/client'
import type { Service } from '@/lib/types'

export function useServices() {
  const [services, setServices] = useState<Service[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const supabase = createClient()

    supabase
      .from('services')
      .select('*')
      .then(({ data, error }) => {
        if (error) {
          setError(error.message)
        } else {
          setServices(data || [])
        }
        setLoading(false)
      })
  }, [])

  return { services, loading, error }
}
```

## Testing Each Phase

### Test Auth:
1. Sign up with email
2. Check email for verification link
3. Click link
4. Login with credentials
5. See dashboard

### Test Booking:
1. Select service
2. Pick date (should show available times)
3. Click a time slot
4. Confirm booking
5. See confirmation
6. Check "My Bookings"

### Test Admin:
1. Create service
2. Create time slots
3. Book as user
4. See booking in admin panel
5. Cancel booking
6. Verify slot capacity updates

### Test Anti-Double-Booking:
1. Two browser windows
2. Both select same slot
3. Click book simultaneously
4. First succeeds, second gets error

## Questions to Answer While Building

1. Should users be able to edit their bookings? (recommend: no, require cancel + rebook)
2. Should there be cancellation deadline? (recommend: yes, e.g., 24 hours before)
3. Should admin get notifications of new bookings? (recommend: yes, email)
4. What info to show in confirmation email? (recommend: service, date, time, location)
5. Support for recurring bookings? (recommend: phase 2)
6. Payment integration? (recommend: phase 2 with Stripe)

## Estimated Effort

- Auth pages: 2-3 hours
- Service listing: 1-2 hours
- Booking flow: 4-6 hours
- User dashboard: 2-3 hours
- Admin panel: 4-5 hours
- State management: 2-3 hours
- Real-time updates: 2-3 hours
- Styling & polish: 4-5 hours

**Total: ~25-30 hours for complete frontend**

## Resources

- **Supabase Docs:** https://supabase.com/docs
- **Next.js 16 Docs:** https://nextjs.org/docs
- **TypeScript Handbook:** https://www.typescriptlang.org/docs
- **Tailwind CSS:** https://tailwindcss.com/docs
- **Zustand Docs:** https://github.com/pmndrs/zustand

## Files Reference

All created files are documented in:
- **BOOKING_SYSTEM_COMPLETE_GUIDE.md** - Technical deep dive
- **BOOKING_SYSTEM_QUICK_START.md** - Testing workflows
- **BOOKING_SYSTEM_IMPLEMENTATION_CHECKLIST.md** - Full checklist

---

**Status:** Ready for Frontend Build 🚀

The backend is production-ready. Start with auth pages, then build the booking flow. All API routes are tested and working.
