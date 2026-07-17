# Application Architecture

## Core Principle

The application will be a Laravel monolith with Blade and Livewire.

React will only be used for isolated widgets when Blade or Livewire is not suitable.

## Main Layers

### Models

Represent database entities.

Examples:

- User
- Post
- Category
- Tag
- Page
- Media
- Setting

### Controllers

Handle HTTP requests.

Controllers must remain thin.

### Form Requests

Handle validation.

### Policies

Handle authorization.

### Services

Handle complex business logic and external integrations.

Examples:

- WordPress import services
- SEO services
- News-Man provider integrations
- Media processing
- Audio generation

### DTOs

Used for structured data transfer.

Examples:

- SourceInput
- CollectedSource
- NewsRequest
- NewsDraft
- AiResponse

### Jobs

Used for long-running tasks.

Examples:

- Import posts
- Import media
- Transcribe video
- Generate news
- Generate SEO
- Generate featured image
- Generate audio

## Suggested Folder Structure

app/
├── Actions
├── DTOs
├── Enums
├── Filament
├── Jobs
├── Livewire
├── Models
├── Policies
├── Services
│   ├── WordPress
│   ├── NewsMan
│   ├── Seo
│   └── Media
└── Support

resources/
├── views
│   ├── layouts
│   ├── components
│   ├── home
│   ├── posts
│   ├── categories
│   ├── tags
│   ├── authors
│   └── pages

## Frontend Strategy

- Blade for page rendering
- Livewire for search, filters, pagination, dynamic forms and admin workflows
- Tailwind CSS for styling
- Vite for assets
- React only when explicitly approved

## Queue Strategy

Queue drivers will later use Redis.

Long-running tasks must not run directly in the HTTP request.

## Caching Strategy

Future caching areas:

- Homepage sections
- Category pages
- Popular posts
- Settings
- Navigation menus
- Breaking news
- Search results where appropriate