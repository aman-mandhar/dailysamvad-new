# Format support

JPEG and PNG sources are eligible for GD derivatives. WebP is emitted when `imagewebp` exists; AVIF is emitted only when `imageavif` exists and the feature flag permits it. GIF/animated GIF and SVG are preserved as originals and skipped by the raster processor.
