<?php

namespace App\Enums;

enum PostStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';
}
