# AI News-Man

## Goal

News-Man is an AI-powered drafting assistant for Daily Samvad.

It will collect source content, understand it and create a structured news draft for editor review.

## Supported Inputs

Version 1:

- Plain text
- Website URL
- Uploaded image
- Uploaded PDF
- Uploaded video
- YouTube URL

Later versions:

- X
- Facebook
- Instagram
- Reporter mobile uploads
- Voice notes
- Live field reporting

## Output

- Title
- Alternative titles
- Excerpt
- Full news content
- Category suggestions
- Tags
- SEO title
- Meta description
- Focus keyword
- Featured image
- Image alt text
- Image caption
- Audio
- Source attribution
- Draft status

## Workflow

Source input
→ source detection
→ content collection
→ content understanding
→ news generation
→ SEO generation
→ media generation
→ NewsDraft
→ editor review
→ publish

## Core Rules

- AI never publishes directly.
- AI output must be saved as draft.
- Every draft must preserve source attribution.
- Editors may edit every generated field.
- Long-running tasks must use queues.
- Providers must be replaceable through interfaces.
- API credentials must remain in environment configuration.
- Prompt templates should be manageable and versioned.

## Suggested Components

### DTOs

- NewsRequest
- SourceInput
- CollectedSource
- NewsDraft
- AiResponse

### Services

- SourceDetector
- WebsiteCollector
- TextCollector
- ImageUnderstandingService
- PdfUnderstandingService
- VideoUnderstandingService
- AudioTranscriptionService
- NewsGenerator
- SeoGenerator
- ImageGenerator
- AudioGenerator

### Jobs

- CollectSourceJob
- AnalyzeImageJob
- TranscribeVideoJob
- GenerateNewsJob
- GenerateSeoJob
- GenerateFeaturedImageJob
- GenerateAudioJob
- FinalizeDraftJob

## Initial Languages

- Hindi
- Punjabi
- English

## Editorial Safety

- Human approval is mandatory.
- Avoid presenting AI-generated images as real event photography.
- Preserve original source URLs.
- Track generation errors and provider usage.
- Support verification status.