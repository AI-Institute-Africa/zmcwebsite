# Zimbabwe Media Commission (ZMC) Website

## Overview

This is a government institutional website for the Zimbabwe Media Commission, a constitutional body established under Section 249 of the Constitution of Zimbabwe. The website promotes and protects freedom of expression and media freedom, providing services for media practitioner accreditation, media house registration, complaints handling, and public information dissemination.

The application follows a full-stack architecture with a React frontend and Express backend, using PostgreSQL for data persistence.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Frontend Architecture
- **Framework**: React 18 with TypeScript
- **Routing**: Wouter (lightweight router)
- **State Management**: TanStack React Query for server state
- **Styling**: Tailwind CSS with custom CSS variables for Zimbabwe national colors (green, gold, red, black)
- **UI Components**: shadcn/ui component library built on Radix UI primitives
- **Typography**: Cormorant Garamond (headings) and Inter (body text)
- **Icons**: Lucide React icons

### Backend Architecture
- **Framework**: Express.js with TypeScript
- **Server**: Node.js HTTP server
- **API Pattern**: RESTful endpoints prefixed with `/api`
- **Build Tool**: esbuild for production bundling, Vite for development

### Data Storage
- **ORM**: Drizzle ORM with PostgreSQL dialect
- **Schema Location**: `shared/schema.ts` (shared between frontend and backend)
- **Migrations**: Drizzle Kit with output to `./migrations`
- **Development Storage**: In-memory storage class (`MemStorage`) for development/testing

### Build and Development
- **Development**: Vite dev server with HMR, proxying to Express backend
- **Production Build**: Vite builds client to `dist/public`, esbuild bundles server to `dist/index.cjs`
- **Database Push**: `npm run db:push` for schema migrations

### Design System
The website uses Zimbabwe national colors as the primary palette:
- Primary Green: #1B5E20 (main brand)
- Gold/Accent: #D4AF37 (CTAs, highlights)
- Red: #C62828 (alerts)
- Black: #212121 (text)

CSS custom properties defined in `client/src/index.css` control theming throughout the application.

## External Dependencies

### Database
- PostgreSQL (configured via `DATABASE_URL` environment variable)
- Drizzle ORM for database operations
- connect-pg-simple for session storage

### UI Framework
- Radix UI primitives (accordion, dialog, dropdown, tabs, etc.)
- shadcn/ui component patterns
- Embla Carousel for carousels
- React Day Picker for calendar components

### Form Handling
- React Hook Form with Zod validation
- drizzle-zod for schema-to-validation integration

### API and Data
- TanStack React Query for data fetching and caching
- Express for REST API endpoints

### Development Tools
- Vite with React plugin
- Replit-specific plugins (error overlay, cartographer, dev banner)
- TypeScript for type safety