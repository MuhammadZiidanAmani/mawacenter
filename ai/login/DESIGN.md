---
name: Institutional Prestige
colors:
  surface: '#f8f9ff'
  surface-dim: '#cbdbf5'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e5eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d3e4fe'
  on-surface: '#0b1c30'
  on-surface-variant: '#404940'
  inverse-surface: '#213145'
  inverse-on-surface: '#eaf1ff'
  outline: '#717970'
  outline-variant: '#c0c9be'
  surface-tint: '#2d6a3e'
  primary: '#003818'
  on-primary: '#ffffff'
  primary-container: '#0f5128'
  on-primary-container: '#83c38f'
  inverse-primary: '#95d5a0'
  secondary: '#785a00'
  on-secondary: '#ffffff'
  secondary-container: '#fcce64'
  on-secondary-container: '#745700'
  tertiary: '#2d3130'
  on-tertiary: '#ffffff'
  tertiary-container: '#434747'
  on-tertiary-container: '#b2b5b4'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#b0f2ba'
  primary-fixed-dim: '#95d5a0'
  on-primary-fixed: '#00210b'
  on-primary-fixed-variant: '#105229'
  secondary-fixed: '#ffdf9b'
  secondary-fixed-dim: '#edc157'
  on-secondary-fixed: '#251a00'
  on-secondary-fixed-variant: '#5b4300'
  tertiary-fixed: '#e0e3e2'
  tertiary-fixed-dim: '#c4c7c6'
  on-tertiary-fixed: '#181c1c'
  on-tertiary-fixed-variant: '#434847'
  background: '#f8f9ff'
  on-background: '#0b1c30'
  surface-variant: '#d3e4fe'
typography:
  display-lg:
    fontFamily: Manrope
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Manrope
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  headline-sm:
    fontFamily: Manrope
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-caps:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.05em
  headline-lg-mobile:
    fontFamily: Manrope
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  container-padding: 20px
  stack-gap: 16px
  input-gap: 8px
  section-margin: 32px
---

## Brand & Style

The design system is rooted in a **Corporate / Modern** aesthetic, specifically tailored for institutional financial management. It evokes a sense of trust, stability, and academic rigor through its use of traditional "scholastic" colors—forest green and gold—paired with modern, clean interface patterns.

The visual narrative focuses on high-legibility, structured data, and a professional atmosphere. The target audience includes administrators and stakeholders who require a reliable, focused environment for complex data entry and financial oversight. The UI should feel authoritative yet accessible, utilizing generous white space and a clear hierarchy to reduce cognitive load during critical tasks.

## Colors

The palette is dominated by a deep, "Academic Green" as the primary anchor for navigation and brand identity. This is complemented by "Institutional Gold" used sparingly for accents, highlights, and secondary branding elements to suggest value and prestige.

The background system utilizes a very subtle cool-gray (`#F4F7F6`) to differentiate between the global canvas and content containers, which are rendered in pure white. High-contrast dark slates are used for primary text to ensure maximum readability, while muted grays handle secondary labels and metadata. Success states should utilize a lighter variation of the primary green to maintain brand harmony.

## Typography

This design system uses a dual-sans-serif approach to balance modern professionalism with technical clarity. **Manrope** is reserved for headlines and display elements, providing a geometric yet warm character that feels updated. **Inter** serves as the workhorse for body copy, data tables, and labels, selected for its exceptional legibility and neutral tone in high-density environments.

Large headings use tighter letter-spacing for a more "designed" look, while small caps labels are given increased tracking to improve scanning speed on complex forms.

## Layout & Spacing

For the mobile login transition, the system shifts to a **Fluid Grid** with fixed horizontal safe areas. The layout relies on a vertical stack rhythm to guide the user from the brand identity at the top to the action-oriented inputs in the center.

- **Margins:** 20px horizontal margins are standard for mobile.
- **Rhythm:** An 8px grid governs the spacing between elements. 
- **Grouping:** Related fields (Username/Password) are grouped with 8px spacing, while functional groups (Login/Forgot Password) are separated by 24px.
- **Center Alignment:** On mobile login, brand and input containers are centered vertically to create a focused, "gateway" experience.

## Elevation & Depth

The design system employs **Tonal Layers** rather than heavy shadows to indicate hierarchy. 
- **Level 0 (Canvas):** The light gray background (`#F4F7F6`).
- **Level 1 (Cards/Containers):** Pure white surfaces with a thin, 1px neutral border (`#E2E8F0`).
- **Interactive Depth:** Subtle ambient shadows (Blur 8px, Y-2, Opacity 4%) are used only on primary action buttons and floating cards to provide a "lifted" tactile feel without breaking the clean, flat aesthetic. 

Navigation elements use solid color fills (Primary Green) to define the most important functional areas, creating depth through contrast rather than shadows.

## Shapes

The shape language is **Rounded**, utilizing a 0.5rem (8px) corner radius as the standard for buttons, input fields, and container cards. This softens the formal nature of the financial palette, making the application feel more modern and user-friendly.

Small interactive elements like chips or badges may use `rounded-xl` to appear pill-shaped, distinguishing them from the more structural rectangular components.

## Components

### Buttons
Primary buttons use the Forest Green fill with white text. Secondary buttons utilize a white fill with a thin gray border or a transparent background with green text. All buttons have a height of 48px on mobile for optimal tap targets.

### Input Fields
Inputs are defined by a light gray border that transitions to the Primary Green on focus. Labels are placed above the field in `body-md` bold, utilizing the dark slate color for high visibility.

### Login Card
For mobile, the login form is housed in a white card with a subtle border. The top of the card features the brand logo and the "MA'WA CENTER" title in the primary green and gold colors.

### Feedback Elements
Form validation uses a soft red for errors, while successful login transitions should incorporate a Primary Green loading state.