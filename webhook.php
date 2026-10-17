<?php
require_once __DIR__ . '/bootstrap.php';
$update = json_decode(file_get_contents('php://input'), true) ?: [];
$pdo = db();
$s = settings($pdo);
$token = trim($s['BOT_TOKEN'] ?? '');
$proxy = trim($s['TG_PROXY'] ?? '') ?: null;
if ($token && isset($update['callback_query'])) {
    $cb = $update['callback_query'];
    telegramApi($token, 'answerCallbackQuery', ['callback_query_id'=>$cb['id'], 'text'=>'بررسی شد.'], $proxy);
    if (substr(($cb['data'] ?? ''), 0, 5) === 'DONE_') {
        $uid = substr($cb['data'], 5);
        $pdo->prepare('UPDATE essentials SET done=1 WHERE uid=?')->execute([$uid]);
    }
}
// پاسخ به /start یا /id — آیدی عددی کاربر را نشان می‌دهد تا مستقیم در تنظیمات وارد کند
if ($token && isset($update['message']['text'])) {
    $chatId = $update['message']['chat']['id'] ?? '';
    $text = trim((string)$update['message']['text']);
    if ($chatId !== '' && (strncmp($text, '/start', 6) === 0 || strncmp($text, '/id', 3) === 0)) {
        $name = trim(($update['message']['from']['first_name'] ?? '') . ' ' . ($update['message']['from']['last_name'] ?? ''));
        $reply = "✅ <b>ربات با موفقیت به شما وصل شد</b>\n"
               . ($name ? 'سلام ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "!\n" : '')
               . "\n🆔 آیدی عددی شما:\n<code>" . htmlspecialchars((string)$chatId, ENT_QUOTES, 'UTF-8') . "</code>\n\n"
               . "این عدد را در تنظیمات سامانه → «آیدی عددی مدیران» وارد کنید تا اعلان‌ها را دریافت کنید.\n"
               . "(روی عدد بزنید تا کپی شود.)";
        telegramApi($token, 'sendMessage', ['chat_id'=>$chatId, 'text'=>$reply, 'parse_mode'=>'HTML'], $proxy);
    }
}
echo 'ok';
