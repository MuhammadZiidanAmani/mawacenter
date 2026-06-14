---
name: Academic Premium
colors:
  surface: '#f7f9fb'
  surface-dim: '#d8dadc'
  surface-bright: '#f7f9fb'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f6'
  surface-container: '#eceef0'
  surface-container-high: '#e6e8ea'
  surface-container-highest: '#e0e3e5'
  on-surface: '#191c1e'
  on-surface-variant: '#3f4944'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#6f7973'
  outline-variant: '#bec9c2'
  surface-tint: '#1b6b51'
  primary: '#004532'
  on-primary: '#ffffff'
  primary-container: '#065f46'
  on-primary-container: '#8bd6b7'
  inverse-primary: '#8bd6b6'
  secondary: '#855300'
  on-secondary: '#ffffff'
  secondary-container: '#fea619'
  on-secondary-container: '#684000'
  tertiary: '#3b3a52'
  on-tertiary: '#ffffff'
  tertiary-container: '#52516a'
  on-tertiary-container: '#c8c4e3'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#a6f2d1'
  primary-fixed-dim: '#8bd6b6'
  on-primary-fixed: '#002116'
  on-primary-fixed-variant: '#00513b'
  secondary-fixed: '#ffddb8'
  secondary-fixed-dim: '#ffb95f'
  on-secondary-fixed: '#2a1700'
  on-secondary-fixed-variant: '#653e00'
  tertiary-fixed: '#e3dffe'
  tertiary-fixed-dim: '#c7c3e2'
  on-tertiary-fixed: '#1a1930'
  on-tertiary-fixed-variant: '#46445d'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
  success-emerald: '#10B981'
  warning-amber: '#FBBF24'
  error-rose: '#E11D48'
  surface-card: '#FFFFFF'
  text-muted: '#64748B'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 30px
  title-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 26px
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 11px
    fontWeight: '500'
    lineHeight: 14px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base-unit: 4px
  margin-mobile: 20px
  gutter-mobile: 16px
  stack-sm: 8px
  stack-md: 16px
  stack-lg: 24px
  section-gap: 32px
---

## Brand & Style
This design system is built for the modern educational landscape, prioritizing clarity, administrative efficiency, and a sense of institutional trust. The brand personality is authoritative yet welcoming—a "Premium SaaS" aesthetic that balances the gravity of school management with the intuitive ease of consumer software.

The visual style follows a **Modern Corporate** direction with elements of **Minimalism**. It utilizes expansive whitespace to reduce cognitive load for administrators managing complex data, paired with high-quality typography and soft, tactile surfaces. The overall feel is one of calm precision, ensuring that critical information is always the focal point while maintaining a sophisticated, high-end appearance.

## Colors
The palette is anchored by a **Deep Forest Green**, chosen to evoke stability, growth, and the heritage of academic excellence. This primary tone is used for navigation, primary actions, and branding elements to establish a trustworthy foundation.

To ensure the interface feels vibrant and modern, a **Warm Gold** is used as a strategic accent. This secondary color is reserved for high-priority calls to action, notifications, and "active" states, providing a clear visual hierarchy against the darker green. 

The background employs a clean, off-white neutral (`#F8FAFC`) to prevent eye strain, while the deepest neutral (`#0D0C22`) provides high-contrast legibility for body text and headers. Success and error states use refined, slightly desaturated versions of green and red to maintain the premium professional tone.

## Typography
**Plus Jakarta Sans** is the sole typeface for this design system, chosen for its contemporary geometric construction and high legibility at small sizes—crucial for mobile dashboards. 

Headlines utilize a tighter letter-spacing and heavier weights to feel impactful and structured. For mobile, display sizes are scaled down to ensure that even long student names or administrative titles do not wrap awkwardly. Body text is set with generous line-height to improve readability during long sessions of data entry or review. All labels and secondary captions use a slightly increased letter-spacing to distinguish them from interactive body text.

## Layout & Spacing
The layout follows a **Fluid Grid** model optimized for mobile-first interactions. A standard 20px side margin ensures content does not feel cramped against the edges of the device.

Spacing follows a strict 4px base unit, with a preference for "Generous Whitespace." Grouped items (like card headers and descriptions) use 8px gaps, while distinct sections of the dashboard are separated by 32px to create clear mental breaks between different types of data. This "open" layout philosophy prevents the dashboard from feeling overwhelming, even when displaying dense administrative information.

## Elevation & Depth
Depth is conveyed through **Tonal Layers** and **Ambient Shadows**. This design system avoids harsh borders in favor of soft shadows that lift primary containers off the background.

- **Level 0 (Base):** The neutral background (#F8FAFC).
- **Level 1 (Cards):** White surfaces with a very soft, high-diffusion shadow (8% opacity, 12px blur, 4px offset). This level is used for student profiles, class summaries, and news feed items.
- **Level 2 (Overlays/Modals):** Surfaces that float above the primary UI, using a slightly more pronounced shadow to indicate temporary interaction.
- **Tonal Contrast:** Subtle borders (1px, #E2E8F0) are used only when two white surfaces meet, ensuring structural clarity without adding visual noise.

## Shapes
In alignment with the "Soft" aesthetic requested, the design system utilizes a **Rounded** shape language. 

Standard UI elements like input fields and small buttons use a 0.5rem (8px) radius. Larger containers, cards, and primary action buttons utilize a **1rem (16px)** radius (defined as `rounded-lg`) to create a friendly, premium appearance. The consistent use of organic, rounded corners softens the professional nature of the dashboard, making the software feel approachable for teachers and staff.

## Components

### Buttons
Primary buttons are solid Deep Forest Green with white text, utilizing a 16px corner radius and a height of 48px to be thumb-friendly. Secondary buttons use a subtle ghost style or light green tint. The "CTA" buttons use the Gold accent to draw immediate attention.

### Cards
Cards are the primary container for information. They feature a white background, 16px corner radius, and subtle ambient shadows. Headers within cards should have a clear 1px bottom divider if they contain complex actions.

### Input Fields
Fields are designed with a light gray stroke and a white fill. Upon focus, the stroke transitions to the primary Forest Green with a 2px weight. Labels are always positioned above the input in a `label-lg` style.

### Chips & Badges
Used for status (e.g., "Present," "Absent," "Pending"). These are pill-shaped with low-saturation background colors and high-saturation text to ensure they are legible but not distracting from primary data.

### Navigation
A bottom navigation bar with clear, geometric icons and `label-md` text. The active state is indicated by a Forest Green icon and a small dot indicator below the label.

### Lists
Lists use generous vertical padding (16px) and subtle dividers. Each list item should have a clear "touch target" area, often highlighted with a chevron to indicate drill-down capabilities.