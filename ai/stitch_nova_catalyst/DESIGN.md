---
name: Lumina Academic System
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
  secondary: '#9d4300'
  on-secondary: '#ffffff'
  secondary-container: '#fd761a'
  on-secondary-container: '#5c2400'
  tertiary: '#00462e'
  on-tertiary: '#ffffff'
  tertiary-container: '#006041'
  on-tertiary-container: '#50e0a4'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#a6f2d1'
  primary-fixed-dim: '#8bd6b6'
  on-primary-fixed: '#002116'
  on-primary-fixed-variant: '#00513b'
  secondary-fixed: '#ffdbca'
  secondary-fixed-dim: '#ffb690'
  on-secondary-fixed: '#341100'
  on-secondary-fixed-variant: '#783200'
  tertiary-fixed: '#6ffbbe'
  tertiary-fixed-dim: '#4edea3'
  on-tertiary-fixed: '#002113'
  on-tertiary-fixed-variant: '#005236'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 30px
    fontWeight: '700'
    lineHeight: 38px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  title-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
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
  label-md:
    fontFamily: Hanken Grotesk
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
  label-sm:
    fontFamily: Hanken Grotesk
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
  base: 4px
  container-padding: 20px
  stack-gap: 16px
  inline-gap: 12px
  card-padding: 16px
  section-margin: 32px
---

## Brand & Style

The design system is centered on the concept of "Academic Vitality"—combining the traditional stability of educational institutions with the dynamic energy of a modern mobile experience. It is designed for students, parents, and faculty who require high-density information presented with clarity and warmth.

The aesthetic follows a **Corporate Modern** style with a **Tactile** twist. It utilizes soft, multi-layered card surfaces, subtle gradients, and rounded geometric forms to create an interface that feels safe, professional, and optimistic. The interface avoids cold, industrial lines in favor of organic edges and a high-contrast, accessible color palette.

**Key Brand Attributes:**
- **Reliable:** Clear hierarchy and grounded neutrals instill confidence.
- **Approachable:** Soft corners and friendly typography reduce user anxiety.
- **Organized:** Grid-based card layouts manage complex data effortlessly.

## Colors

The color palette is anchored by a deep **Academic Green** (Primary), symbolizing growth and authority. This is balanced by **Orange Peel** (Secondary) to highlight urgent actions and achievements, creating a vibrant, school-spirited energy.

- **Primary & Success:** Shades of emerald and forest green are used for navigation, primary buttons, and positive status indicators.
- **Surface & Background:** The default mode is light, using a cool-toned neutral (`#F8FAFC`) to maintain a clean, airy feel.
- **Accents:** Tertiary mint greens are used for decorative elements and soft backgrounds to provide variety without overwhelming the user.

## Typography

The design system utilizes **Plus Jakarta Sans** for its friendly, modern, and highly legible rounded characteristics, which perfectly suit a contemporary school app. 

For data-heavy labels and metadata, **Hanken Grotesk** is used to provide a crisp, slightly more technical contrast. This distinction helps users quickly scan IDs, dates, and status tags. Large headlines should use a tight letter spacing to appear cohesive, while labels benefit from slight tracking to ensure readability at small sizes.

## Layout & Spacing

This design system uses a **Fluid Mobile-First Grid** with a logic based on 4px increments.

- **Margins:** Standard horizontal safe-area padding is set to 20px to prevent content from feeling cramped against the screen edges.
- **Vertical Rhythm:** A consistent 16px (stack-gap) is used between related card elements, while larger 32px margins separate major sections (e.g., "Schedule" from "Announcements").
- **Card Strategy:** Information is grouped into high-quality cards. On tablet, these cards reflow into a two-column masonry layout, while on mobile, they stack vertically.

## Elevation & Depth

To create a professional and organized feel, depth is conveyed through **Tonal Layers** and **Ambient Shadows**.

1.  **Canvas:** The lowest layer, using the neutral background color.
2.  **Surface:** White cards with a very soft, highly diffused shadow (Blur: 20px, Y: 4px, Opacity: 4% Black).
3.  **Active/Floating:** Elements like FABs or active modal sheets use a double shadow with a slight primary color tint in the shadow to indicate vitality.
4.  **Inlay:** Search bars and input fields use a subtle 1px border (`#E2E8F0`) or a slight inner shadow to appear recessed into the surface.

## Shapes

The shape language is consistently **Rounded**, promoting a sense of safety and modern accessibility.

- **Small Components:** Checkboxes and small tags use `rounded-md` (8px).
- **Standard Components:** Buttons and Input fields use `rounded-lg` (12px).
- **Containers:** Large information cards and bottom sheets use `rounded-xl` (24px) for a soft, premium appearance.
- **Avatars:** Always circular or use `rounded-lg` to maintain consistency with the card language.

## Components

### Buttons
- **Primary:** Solid Primary Green with white text. High-contrast, rounded-lg.
- **Secondary:** Surface-colored with a Primary Green border and text.
- **Tertiary/Ghost:** No border, Primary Green text, used for less critical actions like "View All."

### Cards
- Cards are the primary container. They must feature a white background, 16px internal padding, and a 24px corner radius. Include a 2px vertical accent bar on the left side of the card to indicate category (e.g., Green for Academic, Orange for Administrative).

### Inputs & Fields
- **Search:** Fully rounded (pill-shaped) with a subtle background tint and a leading icon.
- **Form Fields:** White background, 1px neutral border, with floating labels using the `label-sm` typography style.

### Chips & Badges
- Used for status (e.g., "Present," "Late," "Excused"). Chips should use a low-opacity background of the status color (e.g., 10% Green) with high-opacity text (100% Green) for maximum readability.

### Progress Indicators
- Use thick, rounded-cap progress bars. For grades or completion, use a gradient transition from Primary Green to Tertiary Green.