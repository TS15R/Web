<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ส่งข้อมูล IP ไปยัง Discord</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .btn {
            background: #7289da;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5b73c4;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📡 ส่งข้อมูล IP ไปยัง Discord</h1>
        
        <div class="info-box">
            <strong>ข้อมูลที่จะส่ง:</strong><br>
            • IP เครื่อง (Client IP)<br>
            • IP เน็ต (Public IP)<br>
            • ที่อยู่ IP (Location Info)<br>
            • ข้อมูลเบราว์เซอร์และระบบปฏิบัติการ
        </div>

        <button class="btn" onclick="sendToDiscord()">🚀 ส่งข้อมูลไปยัง Discord</button>
        
        <div id="result" class="result"></div>
    </div>

    <script>
    function sendToDiscord() {
        const button = document.querySelector('.btn');
        const resultDiv = document.getElementById('result');
        
        // ปิดการคลิกปุ่มชั่วคราว
        button.disabled = true;
        button.innerHTML = '⏳ กำลังส่งข้อมูล...';
        resultDiv.style.display = 'none';

        // ส่ง request ไปยัง PHP
        fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=send_discord'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.className = 'result success';
                resultDiv.innerHTML = '✅ ส่งข้อมูลสำเร็จ! ตรวจสอบ Discord ของคุณ';
            } else {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '❌ เกิดข้อผิดพลาด: ' + data.message;
            }
            resultDiv.style.display = 'block';
        })
        .catch(error => {
            resultDiv.className = 'result error';
            resultDiv.innerHTML = '❌ เกิดข้อผิดพลาดในการเชื่อมต่อ';
            resultDiv.style.display = 'block';
        })
        .finally(() => {
            // เปิดการคลิกปุ่มอีกครั้ง
            button.disabled = false;
            button.innerHTML = '🚀 ส่งข้อมูลไปยัง Discord';
        });
    }
    </script>

    <?php
    // ตั้งค่า Discord Webhook URL ของคุณที่นี่
    $DISCORD_WEBHOOK_URL = "https://discord.com/api/webhooks/your_webhook_id/your_webhook_token";

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_discord') {
        header('Content-Type: application/json');
        
        try {
            // รับข้อมูล IP และข้อมูลอื่นๆ
            $clientIP = $_SERVER['HTTP_CLIENT_IP'] ?? 
                       $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
                       $_SERVER['REMOTE_ADDR'] ?? 'ไม่สามารถระบุได้';

            // รับ Public IP จาก external service
            $publicIP = getPublicIP();
            
            // รับข้อมูลตำแหน่ง
            $locationInfo = getLocationInfo($publicIP);
            
            // ข้อมูลเบราว์เซอร์และระบบปฏิบัติการ
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'ไม่ทราบ';
            $browserInfo = getBrowserInfo();
            $osInfo = getOSInfo();

            // สร้าง embed message สำหรับ Discord
            $embed = [
                "title" => "📡 ข้อมูล IP และระบบ",
                "color" => 3447003,
                "timestamp" => date('c'),
                "fields" => [
                    [
                        "name" => "🖥️ Client IP",
                        "value" => "`" . $clientIP . "`",
                        "inline" => true
                    ],
                    [
                        "name" => "🌐 Public IP",
                        "value" => "`" . $publicIP . "`",
                        "inline" => true
                    ],
                    [
                        "name" => "📍 ตำแหน่ง",
                        "value" => $locationInfo,
                        "inline" => false
                    ],
                    [
                        "name" => "🔍 ระบบปฏิบัติการ",
                        "value" => $osInfo,
                        "inline" => true
                    ],
                    [
                        "name" => "🌐 เบราว์เซอร์",
                        "value" => $browserInfo,
                        "inline" => true
                    ],
                    [
                        "name" => "🕒 เวลา",
                        "value" => date('Y-m-d H:i:s'),
                        "inline" => true
                    ]
                ],
                "footer" => [
                    "text" => "IP Information System"
                ]
            ];

            // ส่งข้อมูลไปยัง Discord
            $result = sendDiscordWebhook($embed);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'ส่งข้อมูลสำเร็จ']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ไม่สามารถส่งข้อมูลไปยัง Discord ได้']);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ฟังก์ชันรับ Public IP
    function getPublicIP() {
        $services = [
            'https://api.ipify.org',
            'https://ipv4.icanhazip.com',
            'https://checkip.amazonaws.com'
        ];
        
        foreach ($services as $service) {
            try {
                $ip = trim(@file_get_contents($service));
                if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            } catch (Exception $e) {
                continue;
            }
        }
        return 'ไม่สามารถระบุได้';
    }

    // ฟังก์ชันรับข้อมูลตำแหน่ง
    function getLocationInfo($ip) {
        if ($ip === 'ไม่สามารถระบุได้' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return 'ไม่สามารถระบุตำแหน่งได้';
        }
        
        try {
            $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,regionName,city,isp,org,as,query&lang=th");
            if ($response) {
                $data = json_decode($response, true);
                if ($data['status'] === 'success') {
                    $location = [];
                    if (!empty($data['country'])) $location[] = $data['country'];
                    if (!empty($data['city'])) $location[] = $data['city'];
                    if (!empty($data['isp'])) $location[] = "ISP: " . $data['isp'];
                    
                    return implode(", ", $location) ?: 'ไม่สามารถระบุตำแหน่งได้';
                }
            }
        } catch (Exception $e) {
            // ไม่ต้องทำอะไร ถ้าไม่สามารถรับข้อมูลตำแหน่งได้
        }
        
        return 'ไม่สามารถระบุตำแหน่งได้';
    }

    // ฟังก์ชันรับข้อมูลเบราว์เซอร์
    function getBrowserInfo() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $browsers = [
            'Chrome' => 'Chrome',
            'Firefox' => 'Firefox',
            'Safari' => 'Safari',
            'Edge' => 'Edge',
            'Opera' => 'Opera'
        ];
        
        foreach ($browsers as $browser => $name) {
            if (stripos($userAgent, $browser) !== false) {
                return $name;
            }
        }
        
        return 'เบราว์เซอร์อื่น';
    }

    // ฟังก์ชันรับข้อมูลระบบปฏิบัติการ
    function getOSInfo() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $oses = [
            'Windows' => 'Windows',
            'Mac' => 'Mac OS',
            'Linux' => 'Linux',
            'Android' => 'Android',
            'iPhone' => 'iOS',
            'iPad' => 'iOS'
        ];
        
        foreach ($oses as $os => $name) {
            if (stripos($userAgent, $os) !== false) {
                return $name;
            }
        }
        
        return 'ระบบปฏิบัติการอื่น';
    }

    // ฟังก์ชันส่ง Webhook ไปยัง Discord
    function sendDiscordWebhook($embed) {
        global $DISCORD_WEBHOOK_URL;
        
        if ($DISCORD_WEBHOOK_URL === "https://discord.com/api/webhooks/your_webhook_id/your_webhook_token") {
            throw new Exception("กรุณาตั้งค่า Discord Webhook URL ก่อนใช้งาน");
        }
        
        $data = [
            "embeds" => [$embed]
        ];
        
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = @file_get_contents($DISCORD_WEBHOOK_URL, false, $context);
        
        return $result !== false;
    }
    ?>
</body>
</html>
