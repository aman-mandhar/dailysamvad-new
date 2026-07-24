# Processing pipeline

`ImageOptimizationService` validates existence, MIME, dimensions and pixel budget, decodes with GD, preserves alpha, resizes without upscaling and writes deterministic variants. Failures leave the source untouched.
