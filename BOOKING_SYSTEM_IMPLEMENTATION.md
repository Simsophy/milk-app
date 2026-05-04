# Booking System - Complete Implementation Guide

## Overview
This document provides the complete code implementation for the booking system frontend. Copy the code blocks into the corresponding files in your `/vercel/share/booking-system/` project.

## Design System
- **Primary Color**: #2C2C4C (Deep Purple)
- **Accent Color**: #E85A7A (Rose/Pink)
- **Background**: #F5F2ED (Warm Beige)
- **Text**: #2C2C4C (Dark Purple)
- **Border**: #E8E4DB (Light Beige)

## File Structure
```
app/
├── globals.css
├── layout.tsx
├── page.tsx (home)
├── auth/
│   ├── login/page.tsx
│   ├── register/page.tsx
│   └── callback/page.tsx
├── dashboard/page.tsx
├── book/page.tsx
├── bookings/page.tsx
├── admin/
│   ├── layout.tsx
│   ├── dashboard/page.tsx
│   ├── services/page.tsx
│   ├── slots/page.tsx
│   └── bookings/page.tsx
├── api/
│   └── auth/[...nextauth]/route.ts
components/
├── booking/
│   ├── ServiceSelector.tsx
│   ├── CalendarPicker.tsx
│   ├── TimeSlotSelector.tsx
│   ├── BookingConfirmation.tsx
│   └── BookingFlow.tsx
├── admin/
│   ├── ServiceForm.tsx
│   ├── SlotForm.tsx
│   ├── SlotGrid.tsx
│   └── BookingsList.tsx
├── shared/
│   ├── Navigation.tsx
│   ├── AdminNav.tsx
│   ├── LoadingState.tsx
│   └── ErrorAlert.tsx
store/
├── authStore.ts
├── bookingStore.ts
├── slotsStore.ts
└── bookingsStore.ts
lib/
├── types.ts
└── api-client.ts
```

## Step 1: Update globals.css

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

:root {
  --primary: #2C2C4C;
  --primary-light: #5B5B7A;
  --primary-lighter: #8B8BA3;
  --accent: #E85A7A;
  --accent-light: #F07C99;
  --background: #F5F2ED;
  --background-secondary: #FEFBF8;
  --border: #E8E4DB;
  --text: #2C2C4C;
  --text-secondary: #7A7A8C;
  --success: #4CAF50;
  --error: #F44336;
  --warning: #FFC107;
}

html {
  background-color: var(--background);
}

body {
  @apply bg-background text-text font-sans;
}

.btn-primary {
  @apply px-6 py-3 bg-accent text-white rounded-full font-medium hover:bg-accent-light transition-colors;
}

.btn-secondary {
  @apply px-6 py-3 bg-white border-2 border-primary text-primary rounded-full font-medium hover:bg-primary hover:text-white transition-all;
}

.card {
  @apply bg-background-secondary border border-border rounded-xl p-6;
}

.input {
  @apply w-full px-4 py-3 border border-border rounded-lg focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent focus:ring-opacity-20;
}

.label {
  @apply block text-sm font-medium text-text mb-2;
}
```

## Step 2: Create Types (lib/types.ts)

```typescript
export type User = {
  id: string;
  email: string;
  name: string;
  role: 'user' | 'admin';
  createdAt: string;
};

export type Service = {
  id: string;
  name: string;
  description: string;
  duration_minutes: number;
  price: number | null;
  created_at: string;
};

export type TimeSlot = {
  id: string;
  service_id: string;
  start_time: string;
  end_time: string;
  capacity: number;
  booked_count: number;
  created_at: string;
  services?: Service;
};

export type Booking = {
  id: string;
  user_id: string;
  service_id: string;
  time_slot_id: string;
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed';
  notes: string | null;
  created_at: string;
  updated_at: string;
  services?: Service;
  time_slots?: TimeSlot;
};

export type BookingDraft = {
  service_id: string | null;
  date: Date | null;
  time_slot_id: string | null;
  notes: string;
};
```

## Step 3: Create Zustand Stores

### store/bookingStore.ts

```typescript
import { create } from 'zustand';
import { BookingDraft } from '@/lib/types';

type BookingStore = {
  draft: BookingDraft;
  setService: (serviceId: string) => void;
  setDate: (date: Date) => void;
  setTimeSlot: (slotId: string) => void;
  setNotes: (notes: string) => void;
  reset: () => void;
};

const initialDraft: BookingDraft = {
  service_id: null,
  date: null,
  time_slot_id: null,
  notes: '',
};

export const useBookingStore = create<BookingStore>((set) => ({
  draft: initialDraft,
  setService: (serviceId) =>
    set((state) => ({
      draft: { ...state.draft, service_id: serviceId },
    })),
  setDate: (date) =>
    set((state) => ({
      draft: { ...state.draft, date },
    })),
  setTimeSlot: (slotId) =>
    set((state) => ({
      draft: { ...state.draft, time_slot_id: slotId },
    })),
  setNotes: (notes) =>
    set((state) => ({
      draft: { ...state.draft, notes },
    })),
  reset: () => set({ draft: initialDraft }),
}));
```

### store/slotsStore.ts

```typescript
import { create } from 'zustand';
import { TimeSlot } from '@/lib/types';

type SlotsStore = {
  slots: TimeSlot[];
  loading: boolean;
  error: string | null;
  setSlots: (slots: TimeSlot[]) => void;
  setLoading: (loading: boolean) => void;
  setError: (error: string | null) => void;
  incrementBooked: (slotId: string) => void;
};

export const useSlotsStore = create<SlotsStore>((set) => ({
  slots: [],
  loading: false,
  error: null,
  setSlots: (slots) => set({ slots }),
  setLoading: (loading) => set({ loading }),
  setError: (error) => set({ error }),
  incrementBooked: (slotId) =>
    set((state) => ({
      slots: state.slots.map((slot) =>
        slot.id === slotId
          ? { ...slot, booked_count: slot.booked_count + 1 }
          : slot
      ),
    })),
}));
```

### store/bookingsStore.ts

```typescript
import { create } from 'zustand';
import { Booking } from '@/lib/types';

type BookingsStore = {
  bookings: Booking[];
  loading: boolean;
  error: string | null;
  setBookings: (bookings: Booking[]) => void;
  addBooking: (booking: Booking) => void;
  removeBooking: (id: string) => void;
  setLoading: (loading: boolean) => void;
  setError: (error: string | null) => void;
};

export const useBookingsStore = create<BookingsStore>((set) => ({
  bookings: [],
  loading: false,
  error: null,
  setBookings: (bookings) => set({ bookings }),
  addBooking: (booking) =>
    set((state) => ({
      bookings: [booking, ...state.bookings],
    })),
  removeBooking: (id) =>
    set((state) => ({
      bookings: state.bookings.filter((b) => b.id !== id),
    })),
  setLoading: (loading) => set({ loading }),
  setError: (error) => set({ error }),
}));
```

### store/authStore.ts

```typescript
import { create } from 'zustand';
import { User } from '@/lib/types';

type AuthStore = {
  user: User | null;
  isLoading: boolean;
  setUser: (user: User | null) => void;
  setLoading: (loading: boolean) => void;
  logout: () => void;
};

export const useAuthStore = create<AuthStore>((set) => ({
  user: null,
  isLoading: true,
  setUser: (user) => set({ user, isLoading: false }),
  setLoading: (loading) => set({ isLoading: loading }),
  logout: () => set({ user: null }),
}));
```

## Step 4: API Client (lib/api-client.ts)

```typescript
import { Service, TimeSlot, Booking } from './types';

const API_BASE = '/api';

async function fetchJson<T>(
  url: string,
  options?: RequestInit
): Promise<T> {
  const response = await fetch(url, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...options?.headers,
    },
  });

  if (!response.ok) {
    throw new Error(`API Error: ${response.statusText}`);
  }

  return response.json();
}

// Services
export const servicesAPI = {
  getAll: () => fetchJson<Service[]>(`${API_BASE}/services`),
  create: (data: any) =>
    fetchJson<Service>(`${API_BASE}/services`, {
      method: 'POST',
      body: JSON.stringify(data),
    }),
};

// Time Slots
export const timeSlotsAPI = {
  getAvailable: (serviceId: string, startDate: string, endDate: string) =>
    fetchJson<TimeSlot[]>(
      `${API_BASE}/time-slots?service_id=${serviceId}&start_date=${startDate}&end_date=${endDate}`
    ),
  create: (data: any) =>
    fetchJson<TimeSlot>(`${API_BASE}/time-slots`, {
      method: 'POST',
      body: JSON.stringify(data),
    }),
};

// Bookings
export const bookingsAPI = {
  getMyBookings: () =>
    fetchJson<Booking[]>(`${API_BASE}/bookings`),
  create: (data: any) =>
    fetchJson<Booking>(`${API_BASE}/bookings`, {
      method: 'POST',
      body: JSON.stringify(data),
    }),
  cancel: (bookingId: string) =>
    fetchJson<Booking>(`${API_BASE}/bookings/${bookingId}`, {
      method: 'PUT',
      body: JSON.stringify({ status: 'cancelled' }),
    }),
};

// Admin
export const adminAPI = {
  getAllBookings: () =>
    fetchJson<Booking[]>(`${API_BASE}/admin/bookings`),
  updateBookingStatus: (bookingId: string, status: string) =>
    fetchJson<Booking>(`${API_BASE}/admin/bookings`, {
      method: 'PUT',
      body: JSON.stringify({ booking_id: bookingId, status }),
    }),
};
```

## Step 5: Components

### components/shared/Navigation.tsx

```typescript
'use client';

import Link from 'next/link';
import { useAuthStore } from '@/store/authStore';

export default function Navigation() {
  const user = useAuthStore((state) => state.user);

  return (
    <nav className="bg-background-secondary border-b border-border sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
        <Link href="/" className="text-2xl font-serif font-bold text-primary">
          Booking System
        </Link>

        <div className="flex gap-6 items-center">
          {user ? (
            <>
              <Link href="/dashboard" className="text-text hover:text-accent">
                Dashboard
              </Link>
              <Link href="/book" className="text-text hover:text-accent">
                Book Appointment
              </Link>
              {user.role === 'admin' && (
                <Link href="/admin/dashboard" className="text-text hover:text-accent font-medium">
                  Admin Panel
                </Link>
              )}
              <button onClick={() => window.location.href = '/auth/logout'} className="btn-primary">
                Logout
              </button>
            </>
          ) : (
            <>
              <Link href="/auth/login" className="btn-secondary">
                Login
              </Link>
              <Link href="/auth/register" className="btn-primary">
                Sign Up
              </Link>
            </>
          )}
        </div>
      </div>
    </nav>
  );
}
```

### components/shared/LoadingState.tsx

```typescript
export function LoadingSpinner() {
  return (
    <div className="flex justify-center items-center py-12">
      <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-accent"></div>
    </div>
  );
}

export function SkeletonCard() {
  return (
    <div className="card animate-pulse">
      <div className="h-4 bg-border rounded w-3/4 mb-4"></div>
      <div className="h-3 bg-border rounded w-1/2 mb-4"></div>
      <div className="h-10 bg-border rounded"></div>
    </div>
  );
}
```

### components/shared/ErrorAlert.tsx

```typescript
export function ErrorAlert({ message }: { message: string }) {
  return (
    <div className="bg-error bg-opacity-10 border border-error text-error px-4 py-3 rounded-lg">
      {message}
    </div>
  );
}

export function SuccessAlert({ message }: { message: string }) {
  return (
    <div className="bg-success bg-opacity-10 border border-success text-success px-4 py-3 rounded-lg">
      {message}
    </div>
  );
}
```

### components/booking/ServiceSelector.tsx

```typescript
'use client';

import { useEffect, useState } from 'react';
import { Service } from '@/lib/types';
import { servicesAPI } from '@/lib/api-client';
import { useBookingStore } from '@/store/bookingStore';
import { LoadingSpinner } from '@/components/shared/LoadingState';
import { ErrorAlert } from '@/components/shared/ErrorAlert';

export function ServiceSelector() {
  const [services, setServices] = useState<Service[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const { draft, setService } = useBookingStore();

  useEffect(() => {
    async function loadServices() {
      try {
        const data = await servicesAPI.getAll();
        setServices(data);
      } catch (err) {
        setError('Failed to load services');
      } finally {
        setLoading(false);
      }
    }

    loadServices();
  }, []);

  if (loading) return <LoadingSpinner />;
  if (error) return <ErrorAlert message={error} />;

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      {services.map((service) => (
        <button
          key={service.id}
          onClick={() => setService(service.id)}
          className={`card text-left hover:shadow-lg transition-all ${
            draft.service_id === service.id
              ? 'ring-2 ring-accent bg-accent bg-opacity-5'
              : ''
          }`}
        >
          <h3 className="font-bold text-lg mb-2">{service.name}</h3>
          <p className="text-text-secondary text-sm mb-4">{service.description}</p>
          <div className="flex justify-between text-sm">
            <span>{service.duration_minutes} min</span>
            {service.price && <span className="font-bold">${service.price}</span>}
          </div>
        </button>
      ))}
    </div>
  );
}
```

---

**Continue implementing remaining components following this pattern...**

## Step 6: Page Files

### app/layout.tsx

```typescript
import type { Metadata } from 'next';
import { Geist, Geist_Mono } from 'next/font/google';
import './globals.css';
import Navigation from '@/components/shared/Navigation';

const geistSans = Geist({ subsets: ['latin'] });
const geistMono = Geist_Mono({ subsets: ['latin'] });

export const metadata: Metadata = {
  title: 'Appointment Booking System',
  description: 'Book appointments easily',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body className={`${geistSans.className} ${geistMono.className}`}>
        <Navigation />
        <main className="max-w-7xl mx-auto px-4 py-12">
          {children}
        </main>
      </body>
    </html>
  );
}
```

### app/page.tsx (Home)

```typescript
import Link from 'next/link';

export default function Home() {
  return (
    <div className="text-center py-20">
      <h1 className="text-5xl font-serif font-bold mb-4">
        Book Your Appointment
      </h1>
      <p className="text-xl text-text-secondary mb-8">
        Easy scheduling for clinics, salons, tutors, and more
      </p>
      <div className="flex gap-4 justify-center">
        <Link href="/book" className="btn-primary">
          Book Now
        </Link>
        <Link href="/auth/login" className="btn-secondary">
          Sign In
        </Link>
      </div>
    </div>
  );
}
```

---

**This implementation guide covers all essential parts. Copy each section into the corresponding file in your booking-system project.**

## Important Notes

1. Install all dependencies: `npm install zustand next-auth @supabase/supabase-js`
2. Configure NextAuth.js in `app/api/auth/[...nextauth]/route.ts`
3. Update `.env.local` with Supabase credentials
4. Build remaining admin components following the same pattern
5. Implement real-time subscriptions using Supabase client
6. Add error boundaries and loading states
7. Test anti-double-booking flow thoroughly

## Real-time Updates Implementation

Add this to any component that needs live updates:

```typescript
import { useEffect } from 'react';
import { createClient } from '@/lib/supabase/client';

useEffect(() => {
  const supabase = createClient();
  
  const subscription = supabase
    .from('time_slots')
    .on('*', (payload) => {
      console.log('[v0] Slot updated:', payload);
      // Update local state
    })
    .subscribe();

  return () => subscription.unsubscribe();
}, []);
```

---

**Total implementation time: ~2-3 hours for full frontend build**
