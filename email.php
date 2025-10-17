<?php
require_once __DIR__ . '/config.php';

function fetchEmails(): array {
    $output = [];

    if (!function_exists('imap_open')) {
        return $output;
    }

    $mailbox = '{' . IMAP_HOST . ':993/imap/ssl}INBOX';
    $inbox = @imap_open($mailbox, EMAIL_USER, EMAIL_PASS);
    if ($inbox === false) {
        return $output;
    }

    $emails = imap_search($inbox, 'ALL');
    if ($emails) {
        foreach ($emails as $email_number) {
            $overviewList = imap_fetch_overview($inbox, (string)$email_number, 0);
            $overview = $overviewList && isset($overviewList[0]) ? $overviewList[0] : null;
            $message = imap_fetchbody($inbox, (string)$email_number, 2);
            $output[] = [
                'subject' => $overview ? ($overview->subject ?? '') : '',
                'from' => $overview ? ($overview->from ?? '') : '',
                'date' => $overview ? ($overview->date ?? '') : '',
                'message' => $message ?: ''
            ];
        }
    }
    imap_close($inbox);
    return $output;
}
?>
