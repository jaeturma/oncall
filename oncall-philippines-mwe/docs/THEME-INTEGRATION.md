# Theme Integration Guide

## Goal

Reuse the supplied existing theme while keeping Oncall's backend clean.

## Theme folder

Copy/extract the theme into:

`theme-source/`

Do not point production routes directly to files in this folder.

## Integration workflow

1. Inventory the theme:
   - HTML/Blade files
   - CSS
   - Tailwind config
   - JS
   - fonts
   - icons
   - images
   - reusable cards/components
   - dashboard layout
   - public layout

2. Map theme layouts into:
   - `resources/views/layouts/public.blade.php`
   - `resources/views/layouts/app.blade.php`
   - `resources/views/layouts/admin.blade.php`

3. Convert reusable UI pieces into Blade components:
   - search form
   - provider card
   - verification badge
   - status badge
   - stat card
   - table
   - modal
   - alert
   - sidebar
   - top navigation

4. Move approved static assets into:
   - `resources/`
   - `public/`

5. Preserve accessibility and responsive behavior.

6. Replace all theme demo content with database-backed Laravel data.

## Critical rule

**Theme controls presentation only.**

Do not move these into theme scripts:
- authentication
- permissions
- verification
- safety enforcement
- commission
- wallet calculations
- withdrawal approvals
- job state rules

## Landing page requirement

Must remain simple:

- What help do you need?
- Where do you need help?
- Find Help

Optional popular-service shortcuts may be shown.

## Guest provider card

May show:
- generic/anonymized provider label
- available state
- rating
- completed services
- verification badges
- municipality/province
- approximate distance only if reliable

Must hide:
- name
- phone
- email
- social media
- exact address
- direct contact

## Authenticated verified provider card

May show provider identity based on policy but direct contact should remain gated until confirmed booking.
