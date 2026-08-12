<?php
require_once '../config.php';
require_once '../helpers.php';

check_role('siswa');

// Bersihkan output buffer
if (ob_get_length()) {
    ob_end_clean();
}

// Set headers to download an .ics file
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="jadwal_minum_ttd_akrab.ics"');

// The event will start on the upcoming Friday at 19:00:00
$next_friday = strtotime('next friday 19:00:00');
// Convert to UTC for iCal (assuming Asia/Jakarta is UTC+7)
$dtstart = gmdate("Ymd\THis\Z", $next_friday);
// 30 minute duration
$dtend = gmdate("Ymd\THis\Z", $next_friday + 1800);
$dtstamp = gmdate("Ymd\THis\Z");

$uid = uniqid() . "@akrab.portodq.com";
$summary = "Waktunya Minum TTD! 💊 (Dari AKRAB)";
$description = "Jangan lupa minum Tablet Tambah Darah (TTD) mingguan kamu agar bebas dari Anemia! Segera catat di Aplikasi AKRAB setelah meminumnya.\\n\\nCek status: https://akrab.portodq.com/siswa/dashboard.php";

$ical = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//AKRAB App//NONSGML v1.0//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
DTSTAMP:$dtstamp
UID:$uid
DTSTART:$dtstart
DTEND:$dtend
RRULE:FREQ=WEEKLY;BYDAY=FR
SUMMARY:$summary
DESCRIPTION:$description
BEGIN:VALARM
ACTION:DISPLAY
DESCRIPTION:Waktunya Minum TTD!
TRIGGER:-PT10M
END:VALARM
END:VEVENT
END:VCALENDAR";

echo $ical;
exit;
