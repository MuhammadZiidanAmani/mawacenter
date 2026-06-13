---
name: RekapTagihan
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
  on-surface-variant: '#444653'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#757684'
  outline-variant: '#c4c5d5'
  surface-tint: '#3755c3'
  primary: '#00288e'
  on-primary: '#ffffff'
  primary-container: '#1e40af'
  on-primary-container: '#a8b8ff'
  inverse-primary: '#b8c4ff'
  secondary: '#505f76'
  on-secondary: '#ffffff'
  secondary-container: '#d0e1fb'
  on-secondary-container: '#54647a'
  tertiary: '#611e00'
  on-tertiary: '#ffffff'
  tertiary-container: '#872d00'
  on-tertiary-container: '#ffa583'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dde1ff'
  primary-fixed-dim: '#b8c4ff'
  on-primary-fixed: '#001453'
  on-primary-fixed-variant: '#173bab'
  secondary-fixed: '#d3e4fe'
  secondary-fixed-dim: '#b7c8e1'
  on-secondary-fixed: '#0b1c30'
  on-secondary-fixed-variant: '#38485d'
  tertiary-fixed: '#ffdbce'
  tertiary-fixed-dim: '#ffb59a'
  on-tertiary-fixed: '#380d00'
  on-tertiary-fixed-variant: '#802a00'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  title-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
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
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.02em
  data-mono:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.01em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 4px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  container-margin: 16px
  gutter: 12px
---

## Brand & Style
The brand personality is professional, transparent, and authoritative, designed to instill confidence in school administrators and parents alike. The design system prioritizes clarity and efficiency, ensuring that complex financial data is digestible at a glance.

The chosen style is **Corporate / Modern** with a lean toward **Minimalism**. By utilizing heavy whitespace and a restricted color palette, the system reduces cognitive load. The aesthetic relies on high-quality typography and a card-based architecture to organize information into logical clusters, making it exceptionally well-suited for mobile-first financial tracking.

## Colors
The palette is anchored by a deep "Trustworthy Blue" to signal stability and institutional reliability. 

- **Primary (#1e40af):** Used for key branding, primary actions, and active states.
- **Surface & Backgrounds:** The system uses a clean white (#ffffff) for primary surfaces with a very light neutral gray (#f8fafc) for page backgrounds to provide subtle contrast for cards.
- **Status Colors:** 
  - **Success (Green):** Indicates "Lunas" (Paid).
  - **Warning (Amber):** Indicates "Menunggak" (Overdue/Late).
  - **Error (Red):** Indicates "Tunggakan" (Arrears/Unpaid).
- **Secondary/Text:** A slate gray is used for secondary information to maintain a clear visual hierarchy against the primary blue.

## Typography
This design system utilizes **Inter** for all roles. Inter’s tall x-height and excellent legibility make it ideal for data-heavy financial applications where numbers must be distinct.

- **Headlines:** Use semi-bold to bold weights with tight letter spacing for a modern, structured look.
- **Body:** Standardized at 14px and 16px to ensure readability on mobile devices.
- **Data Display:** For currency and numerical tallies, use the `data-mono` variant or medium/semi-bold weights to ensure figures stand out from descriptive text.
- **Mobile Scaling:** Headlines larger than 24px should scale down by 15% on mobile devices to prevent awkward line breaks in student names or long financial totals.

## Layout & Spacing
The layout follows a **Fluid Grid** model with a focus on vertical rhythm. 

- **Mobile:** A 4-column grid with 16px side margins. Cards typically span the full width of the container.
- **Desktop/Tablet:** A 12-column grid. Financial summaries and reports are organized in a bento-style layout, where elements span 3, 4, or 6 columns depending on priority.
- **Spacing Rhythm:** An 8px linear scale is used for component layout, while a 4px scale is reserved for tight internal component spacing (e.g., icon to text label).

## Elevation & Depth
To maintain a professional and clean aesthetic, depth is communicated through **Tonal Layers** and **Ambient Shadows**.

- **Level 0 (Background):** Light neutral (#f8fafc).
- **Level 1 (Cards):** Pure white (#ffffff) with a 1px subtle border (#e2e8f0) or an extremely soft, diffused shadow (Blur: 8px, Y: 2px, Opacity: 4%).
- **Level 2 (Modals/Popovers):** Standard shadows with higher displacement to indicate interaction focus.

Avoid heavy dark borders; instead, use slight value changes in the background to distinguish between the navigation and the content area.

## Shapes
The design system adopts a **Rounded** shape language to appear approachable yet modern. 

- **Buttons & Inputs:** 0.5rem (8px) corner radius.
- **Cards:** 1rem (16px) corner radius for a soft, containerized look on mobile.
- **Status Badges:** Fully rounded (pill-shaped) to distinguish them from interactive buttons.

## Components
- **Financial Summary Cards:** Feature a large `headline-md` for the balance/total, a `label-md` for the category, and a subtle trend indicator or secondary action icon.
- **Status Badges:** Small, pill-shaped indicators. Use low-saturation background tints (e.g., light green background with dark green text) to ensure readability without overpowering the layout.
- **Data Lists (Mobile):** Each row is a flat card with a 1px bottom border. Include a primary descriptor (Student Name), a secondary descriptor (Class/ID), and a right-aligned bold figure (Amount).
- **Action Buttons:** 
    - **Primary:** Solid "Trustworthy Blue" with white text.
    - **Secondary/Ghost:** Transparent with blue outline for secondary reports.
    - **WhatsApp Action:** Specialized button using a brand-compliant green icon but maintaining the system's typography and shape language.
- **Input Fields:** Use a subtle border-bottom or a light gray filled style with a clear 8px radius. Active states must use a 2px "Trustworthy Blue" focus ring.