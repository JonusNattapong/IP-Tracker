<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Tracker Admin Panel</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 2em; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; padding: 20px; background: #f8f9fa; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { margin: 0 0 10px 0; color: #333; font-size: 2em; }
        .stat-card p { margin: 0; color: #666; }
        .visitors-list { padding: 20px; }
        .visitor-card { border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px; padding: 15px; background: white; }
        .visitor-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .visitor-id { font-weight: bold; color: #667eea; font-size: 1.1em; }
        .visitor-time { color: #666; font-size: 0.9em; }
        .visitor-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; }
        .detail-item { background: #f8f9fa; padding: 8px; border-radius: 4px; }
        .detail-label { font-weight: bold; color: #333; }
        .detail-value { color: #666; word-break: break-all; }
        .fingerprints { margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        .fingerprint-item { background: #fff3cd; padding: 10px; border-radius: 4px; margin-bottom: 8px; font-family: monospace; font-size: 0.9em; }
        .tabs { display: flex; background: #f8f9fa; border-bottom: 1px solid #dee2e6; }
        .tab { padding: 12px 20px; cursor: pointer; border: none; background: none; border-bottom: 3px solid transparent; }
        .tab.active { background: white; border-bottom-color: #667eea; color: #667eea; font-weight: bold; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .refresh-btn { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-left: 10px; }
        .refresh-btn:hover { background: #218838; }
        .ip-info { background: #e7f3ff; padding: 10px; border-radius: 4px; margin-top: 10px; }
        .location-info { background: #f0f8ff; padding: 10px; border-radius: 4px; margin-top: 10px; }
        .device-info { background: #fff8e1; padding: 10px; border-radius: 4px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 IP Tracker Admin Panel</h1>
            <p>Monitor and analyze visitor data with advanced fingerprinting</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3 id="total-visitors">-</h3>
                <p>Total Visitors</p>
            </div>
            <div class="stat-card">
                <h3 id="unique-devices">-</h3>
                <p>Unique Devices</p>
            </div>
            <div class="stat-card">
                <h3 id="today-visits">-</h3>
                <p>Visits Today</p>
            </div>
            <div class="stat-card">
                <h3 id="avg-confidence">-</h3>
                <p>Avg Fingerprint Confidence</p>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('visitors')">Recent Visitors</button>
            <button class="tab" onclick="showTab('analytics')">Analytics</button>
            <button class="tab" onclick="showTab('raw-data')">Raw Data</button>
            <button class="refresh-btn" onclick="loadData()">🔄 Refresh</button>
        </div>

        <div id="visitors" class="tab-content active visitors-list">
            <div id="visitors-container">
                <p>Loading visitor data...</p>
            </div>
        </div>

        <div id="analytics" class="tab-content">
            <div style="padding: 20px;">
                <h2>Analytics Dashboard</h2>
                <p>Advanced analytics features coming soon...</p>
            </div>
        </div>

        <div id="raw-data" class="tab-content">
            <div style="padding: 20px;">
                <h2>Raw Data Export</h2>
                <p>Raw JSON data for all visitors...</p>
                <pre id="raw-data-content">Loading...</pre>
            </div>
        </div>
    </div>

    <script>
        let allVisitors = [];
        let allFingerprints = [];

        async function loadData() {
            try {
                // Load stats
                const statsResponse = await fetch('src/save.php?action=get_stats');
                const stats = await statsResponse.json();
                updateStats(stats);

                // Load visitors
                loadVisitors();
            } catch (error) {
                console.error('Error loading data:', error);
                // Fallback to placeholder data
                updateStats({
                    total_visitors: 0,
                    unique_devices: 0,
                    today_visits: 0,
                    avg_confidence: 0
                });
                loadVisitors();
            }
        }

        function updateStats(stats) {
            document.getElementById('total-visitors').textContent = stats.total_visitors || 0;
            document.getElementById('unique-devices').textContent = stats.unique_devices || 0;
            document.getElementById('today-visits').textContent = stats.today_visits || 0;
            document.getElementById('avg-confidence').textContent = Math.round(stats.avg_confidence || 0) + '%';
        }

        function loadVisitors() {
            const container = document.getElementById('visitors-container');

            // Check if data directory exists and has files
            fetch('src/save.php?action=list_visitors')
                .then(response => response.json())
                .then(data => {
                    if (data.visitors && data.visitors.length > 0) {
                        container.innerHTML = data.visitors.map(visitor => createVisitorCard(visitor)).join('');
                    } else {
                        container.innerHTML = '<p>No visitor data found yet. Data will appear here when visitors access the tracking page.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading visitors:', error);
                    container.innerHTML = '<p>Error loading visitor data. Check server logs.</p>';
                });
        }

        function createVisitorCard(visitor) {
            const localIPs = visitor.local_ips || [];
            const publicIPs = visitor.public_ips || [];
            const location = visitor.location || {};

            return `
                <div class="visitor-card">
                    <div class="visitor-header">
                        <span class="visitor-id">Visitor: ${visitor.visitor_id}</span>
                        <span class="visitor-time">${visitor.timestamp}</span>
                    </div>

                    <div class="visitor-details">
                        <div class="detail-item">
                            <div class="detail-label">User Agent:</div>
                            <div class="detail-value">${visitor.user_agent}</div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Platform:</div>
                            <div class="detail-value">${visitor.platform}</div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Screen Resolution:</div>
                            <div class="detail-value">${visitor.screen_resolution}</div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Language:</div>
                            <div class="detail-value">${visitor.language}</div>
                        </div>

                        ${localIPs.length > 0 ? `
                            <div class="detail-item ip-info">
                                <div class="detail-label">Local IPs:</div>
                                <div class="detail-value">${localIPs.join(', ')}</div>
                            </div>
                        ` : ''}

                        ${publicIPs.length > 0 ? `
                            <div class="detail-item ip-info">
                                <div class="detail-label">Public IPs:</div>
                                <div class="detail-value">${publicIPs.join(', ')}</div>
                            </div>
                        ` : ''}

                        ${location.latitude ? `
                            <div class="detail-item location-info">
                                <div class="detail-label">Location:</div>
                                <div class="detail-value">${location.latitude}, ${location.longitude} (Accuracy: ${location.accuracy}m)</div>
                            </div>
                        ` : ''}

                        <div class="detail-item device-info">
                            <div class="detail-label">Hardware:</div>
                            <div class="detail-value">
                                CPU Cores: ${visitor.hardware_concurrency || 'Unknown'},
                                Memory: ${visitor.device_memory || 'Unknown'}GB,
                                Touch: ${visitor.touch_support ? 'Yes' : 'No'}
                            </div>
                        </div>

                        ${visitor.connection ? `
                            <div class="detail-item">
                                <div class="detail-label">Connection:</div>
                                <div class="detail-value">${visitor.connection.effectiveType} (${visitor.connection.downlink} Mbps, ${visitor.connection.rtt}ms)</div>
                            </div>
                        ` : ''}
                    </div>

                    <div class="fingerprints">
                        <div class="detail-label">Fingerprint Confidence: ${Math.round((visitor.fingerprint_confidence || 0) * 100)}%</div>
                    </div>
                </div>
            `;
        }

        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));

            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');

            if (tabName === 'raw-data') {
                loadRawData();
            }
        }

        function loadRawData() {
            const content = document.getElementById('raw-data-content');
            content.textContent = 'Raw data export functionality would be implemented here.\n\nThis would include:\n- Complete visitor JSON data\n- Fingerprint components\n- GeoIP information\n- Historical tracking data';
        }

        // Load data on page load
        window.onload = loadData;

        // Auto refresh every 30 seconds
        setInterval(loadData, 30000);
    </script>
</body>
</html>
