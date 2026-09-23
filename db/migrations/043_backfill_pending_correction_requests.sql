-- Bring pending requests created before the explicit correction-request status
-- into the new reviewer queue without requiring the user to submit again.
UPDATE submission_correction_requests cr
JOIN form_submissions s ON s.id = cr.submission_id
SET cr.original_status = s.status
WHERE cr.status = 'pending'
  AND s.status IN ('submitted', 'under_review', 'approved', 'rejected');

UPDATE form_submissions s
JOIN submission_correction_requests cr ON cr.submission_id = s.id
SET s.status = 'correction_requested',
    s.reviewed_by = NULL,
    s.reviewed_at = NULL
WHERE cr.status = 'pending'
  AND s.status IN ('submitted', 'under_review', 'approved', 'rejected');
