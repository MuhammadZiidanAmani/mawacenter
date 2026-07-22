---
name: Ma'wa Institutional
colors:
  surface: '#fcf9f8'
  surface-dim: '#dcd9d9'
  surface-bright: '#fcf9f8'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f6f3f2'
  surface-container: '#f0eded'
  surface-container-high: '#eae7e7'
  surface-container-highest: '#e5e2e1'
  on-surface: '#1b1c1c'
  on-surface-variant: '#404942'
  inverse-surface: '#303030'
  inverse-on-surface: '#f3f0ef'
  outline: '#707972'
  outline-variant: '#bfc9c0'
  surface-tint: '#246a4a'
  primary: '#004229'
  on-primary: '#ffffff'
  primary-container: '#0f5b3c'
  on-primary-container: '#8bd1a9'
  inverse-primary: '#8fd5ae'
  secondary: '#5d5f5f'
  on-secondary: '#ffffff'
  secondary-container: '#dcdddd'
  on-secondary-container: '#5f6161'
  tertiary: '#373939'
  on-tertiary: '#ffffff'
  tertiary-container: '#4e5050'
  on-tertiary-container: '#c2c2c2'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#abf2c9'
  primary-fixed-dim: '#8fd5ae'
  on-primary-fixed: '#002112'
  on-primary-fixed-variant: '#005234'
  secondary-fixed: '#e2e2e2'
  secondary-fixed-dim: '#c6c6c7'
  on-secondary-fixed: '#1a1c1c'
  on-secondary-fixed-variant: '#454747'
  tertiary-fixed: '#e2e2e2'
  tertiary-fixed-dim: '#c6c6c6'
  on-tertiary-fixed: '#1a1c1c'
  on-tertiary-fixed-variant: '#454747'
  background: '#fcf9f8'
  on-background: '#1b1c1c'
  surface-variant: '#e5e2e1'
typography:
  headline-xl:
    fontFamily: Merriweather
    fontSize: 40px
    fontWeight: '700'
    lineHeight: 52px
  headline-lg:
    fontFamily: Merriweather
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
  headline-lg-mobile:
    fontFamily: Merriweather
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
  headline-md:
    fontFamily: Merriweather
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
  headline-sm:
    fontFamily: Merriweather
    fontSize: 20px
    fontWeight: '700'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.04em
spacing:
  unit: 4px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 64px
  max-width: 1200px
---

## Brand & Style
The design system is engineered for a formal, institutional presence, specifically tailored for an Islamic educational foundation. The brand personality is authoritative yet welcoming, grounded in academic tradition and administrative clarity. The target audience includes scholars, students, and donors who value precision and professionalism.

The design style is a blend of **Institutional Minimalism** and **Print-First Design**. It avoids the transient trends of digital interfaces—such as shadows, gradients, and blurs—in favor of high-contrast legibility and structural discipline. Every element is designed to translate seamlessly from a high-resolution screen to a printed document, ensuring the foundation's communications remain consistent across all physical and digital touchpoints.

## Colors
The palette is restricted to four functional roles to ensure clarity and formality:

*   **Primary (#0F5B3C):** A deep, scholarly green representing the Islamic identity and institutional growth. Used for branding, primary headings, and critical calls to action.
*   **Secondary (#F5F5F5):** A cool light gray used for section backgrounds and container fills to subtly differentiate content areas without adding visual weight.
*   **Border (#D9D9D9):** A neutral gray reserved for structural lines, dividers, and input strokes.
*   **Text (#222222):** An off-black chosen for optimal reading contrast on both screens and paper, avoiding the harshness of pure black.

## Typography
This design system employs a classic typographic pairing: **Merriweather** for headlines to evoke academic authority and literary tradition, and **Inter** for body text and labels to ensure modern utility and legibility.

- **Headlines:** Always set in Merriweather. For printed reports, ensure high-level headers use the Primary color.
- **Body Text:** Set in Inter with generous line-height (1.5x minimum) to support long-form educational reading.
- **Hierarchy:** Use bold weights for labels and sub-headers to create a clear scan path without relying on color or size alone.

## Layout & Spacing
The layout follows a **Fixed Grid** philosophy to mirror the structured nature of printed manuscripts and official documents.

- **Desktop:** A 12-column grid with a 1200px max-width, centered on the screen. Large margins (64px) are used to create a sense of "prestige" and focus.
- **Mobile:** A 4-column fluid grid with 16px margins.
- **Vertical Rhythm:** Built on a 4px baseline grid. All padding and margins between elements should be multiples of 8px (8, 16, 24, 32, 48, 64) to maintain a rigorous, disciplined structure.
- **Whitespace:** Prioritize "active" whitespace. Do not feel the need to fill containers; let the content breathe to enhance comprehension.

## Elevation & Depth
In alignment with the institutional and printable nature of the design system, there are **no shadows or blurs**. 

Depth is achieved through **Tonal Layers and Bold Outlines**:
- **Layer 0 (Base):** White (#FFFFFF) for the primary canvas.
- **Layer 1 (Containers):** Light Gray (#F5F5F5) backgrounds for cards, sidebars, or call-out boxes.
- **Definition:** High-definition borders of 1pt thickness using the Border color (#D9D9D9) define the boundaries between elements. 
- **Separation:** Use horizontal rules (1pt) instead of shadows to separate sections in long-form text or lists.

## Shapes
The design system utilizes **Sharp (0px)** corners for all UI elements. This decision reinforces the formal, rigid, and traditional character of the educational foundation. 

Square corners should be applied to:
- Primary and secondary buttons.
- Input fields and text areas.
- Content cards and informational containers.
- Form controls.

## Components
Consistent styling across components ensures a unified institutional voice:

- **Buttons:** Sharp corners. Primary buttons are solid Primary (#0F5B3C) with White text. Secondary buttons use a 1pt Border (#D9D9D9) with Text (#222222). No hover shadows; use a subtle color shift (10% darker) for interaction.
- **Inputs:** 1pt border (#D9D9D9). Labels are Inter Bold, 14px, placed above the field. No rounded corners.
- **Cards:** White background with a 1pt border. For emphasized content, use the Secondary (#F5F5F5) background with no border.
- **Lists:** Traditional bulleted or numbered lists. Use the Primary color for numbers to add institutional character.
- **Lists (Administrative):** Row-based lists with a 1pt bottom border for each item. High vertical padding (16px) for readability.
- **Logo:** The only iconographic element permitted. It should be placed in the top-left (for LTR) or top-right (for RTL) with significant clear space.