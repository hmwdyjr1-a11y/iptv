<?php
header('Content-Type: application/json; charset=utf-8');

$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';
$action = $_GET['action'] ?? '';

// التحقق من بيانات الدخول
if ($username !== 'moaqeel' || $password !== '123') {
    echo json_encode(["user_info" => ["auth" => 0], "server_info" => ["status" => "Invalid credentials"]]);
    exit;
}

// إذا طلب الرسيفر معلومات المستخدم وحالة الاشتراك
if ($action == '' || $action == 'login') {
    $response = [
        "user_info" => [
            "username" => "moaqeel",
            "password" => "123",
            "message" => "Connected Successfully",
            "auth" => 1,
            "status" => "Active",
            "exp_date" => "2030-10-04",
            "is_trial" => "0",
            "active_cons" => "1",
            "max_connections" => "5"
        ],
        "server_info" => [
            "url" => "hmwdyjr1-a11y.github.io",
            "port" => "80",
            "https_port" => "443",
            "server_protocol" => "http",
            "rtmp_port" => "8080",
            "timezone" => "UTC",
            "timestamp_now" => time(),
            "time_now" => date("Y-m-d H:i:s")
        ]
    ];
    echo json_encode($response);
    exit;
}

// قراءة ملف الـ M3U من نفس المستودع لتحويله إلى أقسام وقنوات للرسيفر
$m3u_url = "https://raw.githubusercontent.com/hmwdyjr1-a11y/iptv/main/playlist.m3u";
$m3u_content = @file_get_contents($m3u_url);

$categories = [];
$channels = [];
$cat_id_map = [];
$current_cat_id = 1;

if ($m3u_content) {
    $lines = explode("\n", $m3u_content);
    $cat_counter = 1;
    $channel_counter = 1;

    $current_title = "";
    $current_group = "General";
    $current_logo = "";

    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#EXTINF:') === 0) {
            // استخراج اسم القناة، المجموعة، والشعار
            if (preg_fox_or_standard_match('/tvg-logo="(.*?)"/i', $line, $matches)) {
                $current_logo = $matches[1];
            } else {
                $current_logo = "";
            }

            if (preg_match('/group-title="(.*?)"+/i', $line, $matches)) {
                $current_group = $matches[1];
            } else {
                $current_group = "General";
            }

            // اسم القناة يأتي بعد الفاصلة الأخيرة في السطر
            $parts = explode(',', $line);
            $current_title = trim(end($parts));

            // إنشاء رقم معرف فريد للتصنيف (Category ID)
            if (!isset($cat_id_map[$current_group])) {
                $cat_id_map[$current_group] = $cat_counter;
                $categories[] = [
                    "category_id" => (string)$cat_counter,
                    "category_name" => $current_group,
                    "parent_id" => 0
                ];
                $cat_counter++;
            }
        } elseif (!empty($line) && strpos($line, '#') !== 0) {
            // هذا هو رابط البث المباشر للقناة
            $stream_url = $line;
            $cat_id = $cat_id_map[$current_group];

            if ($action == 'get_live_categories') {
                // سيتم إرسال الأقسام لاحقاً
            } elseif ($action == 'get_live_streams') {
                $channels[] = [
                    "num" => $channel_counter,
                    "name" => $current_title ?: "Channel " . $channel_counter,
                    "stream_type" => "live",
                    "stream_id" => $channel_counter,
                    "stream_icon" => $current_logo,
                    "epg_channel_id" => null,
                    "added" => time(),
                    "category_id" => (string)$cat_id,
                    "custom_sid" => "",
                    "tv_archive" => 0,
                    "direct_source" => "",
                    "tv_archive_duration" => 0
                ];
            }
            $channel_counter++;
        }
    }
}

// الرد على طلب الأقسام (Categories)
if ($action == 'get_live_categories') {
    echo json_encode($categories);
    exit;
}

// الرد على طلب القنوات (Live Streams)
if ($action == 'get_live_streams') {
    echo json_encode($channels);
    exit;
}

// رد افتراضي فارغ للأكشنات الأخرى لكي لا يعلق الرسيفر
echo json_encode([]);
?>
