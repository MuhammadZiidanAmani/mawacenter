---
name: Islamic Financial Excellence
colors:
  surface: '#f9f9ff'
  surface-dim: '#cfdaf1'
  surface-bright: '#f9f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f0f3ff'
  surface-container: '#e7eeff'
  surface-container-high: '#dee8ff'
  surface-container-highest: '#d8e3fa'
  on-surface: '#111c2c'
  on-surface-variant: '#404942'
  inverse-surface: '#263142'
  inverse-on-surface: '#ebf1ff'
  outline: '#707971'
  outline-variant: '#bfc9c0'
  surface-tint: '#296a47'
  primary: '#004528'
  on-primary: '#ffffff'
  primary-container: '#1b5e3c'
  on-primary-container: '#93d5aa'
  inverse-primary: '#92d5aa'
  secondary: '#5a631e'
  on-secondary: '#ffffff'
  secondary-container: '#deea95'
  on-secondary-container: '#606a23'
  tertiary: '#363e36'
  on-tertiary: '#ffffff'
  tertiary-container: '#4d554d'
  on-tertiary-container: '#c1c9bf'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#aef2c5'
  primary-fixed-dim: '#92d5aa'
  on-primary-fixed: '#002110'
  on-primary-fixed-variant: '#085231'
  secondary-fixed: '#deea95'
  secondary-fixed-dim: '#c2cd7b'
  on-secondary-fixed: '#191e00'
  on-secondary-fixed-variant: '#424b05'
  tertiary-fixed: '#dde5da'
  tertiary-fixed-dim: '#c1c9be'
  on-tertiary-fixed: '#161d17'
  on-tertiary-fixed-variant: '#414941'
  background: '#f9f9ff'
  on-background: '#111c2c'
  surface-variant: '#d8e3fa'
typography:
  headline-lg:
    fontFamily: Manrope
    fontSize: 30px
    fontWeight: '700'
    lineHeight: 38px
    letterSpacing: -0.02em
  headline-lg-mobile:
    fontFamily: Manrope
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
  headline-md:
    fontFamily: Manrope
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  title-md:
    fontFamily: Inter
    fontSize: 16px
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
    letterSpacing: 0.01em
  label-sm:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 14px
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
  container-padding: 16px
  gutter: 12px
---

## Brand & Style

The design system is engineered for a financial management context that demands trust, clarity, and institutional stability. Inspired by the Ma'wa Center dashboard, the brand personality is **Professional**, **Ethical**, and **Modern**. It bridges traditional corporate values with contemporary digital efficiency.

The visual style is **Corporate / Modern** with a focus on high legibility and a structured information hierarchy. It utilizes a "Clean & Grounded" aesthetic—characterized by ample whitespace, a disciplined color palette, and subtle elevation to guide the user through complex data sets and financial operations. The goal is to evoke a sense of security and precision in every interaction.

## Colors

The color palette is derived from the institutional identity of the Ma'wa Center, emphasizing growth and value.

*   **Primary (Corporate Green):** A deep, authoritative green used for primary actions, navigation headers, and key brand elements. It signifies stability and ethical growth.
*   **Secondary (Soft Gold/Olive):** A sophisticated accent color used for secondary actions, highlighting active states, and decorative elements that require a premium feel.
*   **Tertiary (Surface Green):** A very light tint of the primary color used for background containers, card fills, and subtle separation of content areas.
*   **Neutral:** A range of slate greys (primary neutral at #4A5568) for body text and labels, ensuring high contrast against the light surfaces.
*   **Status Colors:** Following standard conventions, use a vibrant green for "Aktif" (Active) and a muted slate-blue for "Tidak Aktif" (Inactive) states as seen in the reference dashboard.

## Typography

This design system uses a dual-font approach to balance modernity with utility. **Manrope** is used for headlines to provide a confident, contemporary feel. **Inter** is utilized for all functional text, data tables, and body copy due to its exceptional legibility in dense financial interfaces.

Typography is primarily dark neutral to ensure maximum readability. For mobile, headline sizes are scaled down to prevent excessive wrapping while maintaining a clear hierarchy. Information-dense components like data tables should utilize `body-md` or `label-md` to maximize screen real estate.

## Layout & Spacing

The layout philosophy follows a **Fluid Grid** model optimized for mobile-first financial management. 

*   **Grid:** A 4-column grid for mobile devices with 16px side margins.
*   **Rhythm:** An 8px base unit (with a 4px sub-step) controls all vertical and horizontal spacing.
*   **Density:** Financial data requires a medium density. Use 16px (md) for primary container padding and 12px (gutter) for space between card-based elements.
*   **Mobile Reflow:** In mobile view, sidebars from the desktop dashboard are replaced by a bottom navigation bar or a hamburger menu drawer. Tables reflow into stacked cards or horizontally scrollable containers to preserve data integrity.

## Elevation & Depth

To maintain a clean and professional look, the design system employs **Tonal Layers** and **Low-Contrast Outlines** rather than heavy shadows.

1.  **Level 0 (Background):** The base canvas uses a slightly off-white or the tertiary `E8F0E5` for specific sections.
2.  **Level 1 (Cards/Containers):** Pure white surfaces with a 1px border (#E2E8F0) to define boundaries.
3.  **Level 2 (Active/Floating):** Used for primary buttons or active dropdowns. These use a very soft, diffused shadow (0px 4px 12px rgba(0,0,0,0.05)) to suggest interactability without cluttering the UI.
4.  **Dividers:** Hairline strokes (1px) in a light neutral are used extensively to separate list items and table rows, mimicking the structured feel of the reference dashboard.

## Shapes

The shape language is **Rounded**, conveying a modern and approachable feel while remaining firmly within professional standards.

*   **Primary Containers:** Use `rounded-lg` (16px) for main dashboard cards and modals.
*   **Buttons & Inputs:** Use `rounded-md` (8px) for a balanced, structural appearance.
*   **Status Tags:** Small chips (e.g., "Aktif") use a higher roundedness or pill-shape to distinguish them from functional buttons.

## Components

### Buttons
*   **Primary:** Solid Primary Green background with White text. Bold, 8px corner radius.
*   **Secondary/Outline:** 1px Primary Green border with Green text.
*   **Action (Icon-only):** Small 32x32px square buttons with subtle borders (as seen in the "Aksi" column of the reference).

### Input Fields
*   Outlined style with a 1px neutral border. When focused, the border transitions to Primary Green with a soft outer glow. Labels are positioned above the field in `label-md`.

### Status Chips
*   **Aktif:** Light green background (#E6F4EA) with dark green text (#1B5E3C).
*   **Tidak Aktif:** Light grey-blue background (#EDF2F7) with slate text (#4A5568).
*   Shape: Pill-shaped for immediate recognition.

### Data Cards
*   Financial records should be displayed in cards with a white background, 1px border, and 16px internal padding. Key identifiers (like Year or Amount) should use `title-md`.

### Navigation
*   **Mobile Bottom Bar:** Persistent navigation containing 4-5 key sections: Dashboard, Reports, Master Data, and Settings. Active icons use the Primary Green color.