module.exports = async (io) => {
    const redis = require('redis');
    const redisHost = process.env.REDIS_HOST || '127.0.0.1';
    const redisPort = Number(process.env.REDIS_PORT || 6379);
    const redisUsername = process.env.REDIS_USERNAME || undefined;
    const redisPassword = process.env.REDIS_PASSWORD && process.env.REDIS_PASSWORD !== 'null'
        ? process.env.REDIS_PASSWORD
        : undefined;
    const redisDb = Number(process.env.REDIS_DB || 0);
    const redisUrl = process.env.REDIS_URL || `redis://${redisHost}:${redisPort}/${redisDb}`;
    const appName = (process.env.APP_NAME || 'laravel')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
    const redisPrefix = process.env.REDIS_PREFIX || `${appName}_database_`;
    const socketChannel = 'sms_status';
    const redisChannels = [
        socketChannel,
        `${redisPrefix}${socketChannel}`,
    ];

    console.log('[redis] creating client', {
        host: redisHost,
        port: redisPort,
        db: redisDb,
        hasPassword: Boolean(redisPassword),
        hasUsername: Boolean(redisUsername),
        url: process.env.REDIS_URL ? 'REDIS_URL' : redisUrl,
        channels: redisChannels,
    });

    const redisClient = redis.createClient({
        url: redisUrl,
        username: redisUsername,
        password: redisPassword,
    });

    redisClient.on('error', function (err) {
        console.error('Redis error:', err.message);
    });

    redisClient.on('connect', function () {
        console.log('[redis] connected');
    });

    console.log('[redis] connecting');
    await redisClient.connect();
    console.log('[redis] subscribing to channels', redisChannels);

    for (const channel of redisChannels) {
        await redisClient.subscribe(channel, function (message) {
            try {
                console.log(`[redis] received ${channel}`, message);
                const messageData = JSON.parse(message);
                io.sockets.emit(socketChannel, messageData);
            } catch (error) {
                console.error('Redis message parse error:', error.message);
            }
        });
    }
};
