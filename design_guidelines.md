# Zimbabwe Media Commission (ZMC) Website Design Guidelines

## Design Approach
**Reference-Based**: Preserve the complete existing design system provided in the HTML file. This is a government/institutional website for the Zimbabwe Media Commission with established branding and design patterns.

## Color System (Zimbabwe National Colors)
- **Primary Green**: #1B5E20 (main brand color)
- **Gold/Accent**: #D4AF37 (highlighting, CTAs)
- **Red**: #C62828 (alerts, notifications)
- **Black**: #212121 (text, emphasis)
- **Neutrals**: Comprehensive gray scale from 50-900
- **Semantic colors**: Success (green), error (red), info (blue), warning (orange)

## Typography
- **Headings**: Cormorant Garamond (500, 600, 700 weights) - serif for authority
- **Body**: Inter (400, 500, 600, 700 weights) - sans-serif for readability
- **Scale**: 
  - H1: clamp(2.5rem, 5vw, 3.5rem)
  - H2: clamp(2rem, 4vw, 2.75rem)
  - H3: clamp(1.5rem, 3vw, 2rem)

## Layout & Spacing
- **Container max-width**: 1200px with 2rem padding
- **Section padding**: 5rem vertical (mobile: 3rem)
- **Grid systems**: Use CSS Grid for services (4 columns), news (3 columns), responsive to mobile single column
- **Spacing scale**: Use precise values from existing CSS (0.5rem, 0.75rem, 1rem, 1.5rem, 2rem, etc.)

## Component Library

### Header System
- **Top bar**: Green gradient background with contact info, language toggle (EN/Shona), notification bell
- **Main nav**: White background, logo left, navigation center, action buttons right
- **Buttons**: Green gradient primary, gold gradient for portal access

### Hero Section
- **No hero image**: Text-focused with gradient background
- **CTAs**: Prominent "Apply for Accreditation" and "Register Media House" buttons
- **Stats**: Display key metrics in grid below hero

### Services Cards
- **Icon boxes**: 60px × 60px rounded squares with light green background
- **Lucide icons**: 28px size within icon boxes
- **Layout**: 4-column grid (responsive)
- **Hover effects**: Subtle lift and shadow increase

### News/Announcements
- **Card style**: White background, rounded corners, shadow on hover
- **Images**: Article thumbnails with gradient overlays
- **Metadata**: Date, category badges, read time
- **Filter tabs**: Category-based navigation

### Modals
- **Overlay**: Semi-transparent dark background
- **Container**: White, centered, rounded, max-width 600px
- **Forms**: Full-width inputs, green submit buttons
- **Close**: X button top-right

## Interactive Elements

### Buttons
- **Primary**: Green gradient (135deg) with shadow, lift on hover
- **Portal**: Gold gradient, prominent for logged-in access
- **Ghost**: Transparent with border for secondary actions

### Icons
- **Library**: Lucide Icons via CDN
- **Sizes**: xs(14px), sm(16px), md(20px), lg(24px), xl(32px), 2xl(40px), 3xl(48px)
- **Icon circles**: 70-80px diameter for service icons

### Notifications
- **Bell**: Top-right header with red badge counter
- **Dropdown**: 360px width, white background, scrollable list
- **Pulse animation**: On badge for new notifications

## Shadows & Effects
- **sm**: 0 2px 8px rgba(0,0,0,0.06)
- **md**: 0 4px 16px rgba(0,0,0,0.08)
- **lg**: 0 8px 32px rgba(0,0,0,0.1)
- **xl**: 0 16px 48px rgba(0,0,0,0.12)

## Forms
- **Input style**: Full-width, white background, border, rounded corners, focus state with green accent
- **Labels**: Above inputs, medium weight
- **File uploads**: Custom styled with icon and instructions
- **Select dropdowns**: Styled to match text inputs

## Footer
- **Background**: Dark green gradient
- **Color**: White text
- **Layout**: 4-column grid with links, contact, newsletter signup
- **Social icons**: Gold hover state

## Accessibility
- **Focus states**: Visible outline on all interactive elements
- **Color contrast**: WCAG AA compliant
- **Keyboard navigation**: Full support for forms and modals
- **Screen readers**: Semantic HTML with ARIA labels

## Images
**No large hero image** - The website uses gradient backgrounds and focuses on content/functionality rather than decorative imagery. Small images appear in:
- News article thumbnails
- Staff/team photos in about section
- Office location photos in contact section