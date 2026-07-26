<?php
// Mapped fields variables
$docTitle = $instance['title'] ?? 'Προεπισκόπηση Εγγράφου';
$docNumber = $instance['document_number'] ?? '';
$docStatus = $instance['status'] ?? '';
$backUrl = '/documents/drafts';

require __DIR__ . '/../shared/document_viewer.php';
?>
