-- Populate the requester on correction-request notifications that existed before
-- notifications.sender_user_id was introduced. The newest request is used when a
-- submission has been through more than one correction cycle.
UPDATE notifications n
INNER JOIN form_submissions s
    ON n.link_url = CONCAT('/admin/submissions/', s.uuid)
INNER JOIN forms f ON f.id = s.form_id
INNER JOIN (
    SELECT submission_id, MAX(id) AS correction_request_id
    FROM submission_correction_requests
    GROUP BY submission_id
) latest_request ON latest_request.submission_id = s.id
INNER JOIN submission_correction_requests cr
    ON cr.id = latest_request.correction_request_id
LEFT JOIN users requester ON requester.id = cr.requested_by
SET n.sender_user_id = cr.requested_by,
    n.title = CONCAT('Νέο αίτημα διόρθωσης από ', COALESCE(requester.full_name, requester.username, 'χρήστη')),
    n.message = CONCAT(
        'Από: ', COALESCE(requester.full_name, requester.username, 'Χρήστης'),
        '. Αιτιολογία: ', COALESCE(cr.reason, 'Δεν δόθηκε αιτιολογία.'),
        ' Υποβολή #', s.id, ' στη φόρμα «', f.title, '».'
    )
WHERE n.type = 'correction_request'
  AND n.sender_user_id IS NULL;
