var express = require("express");
var body_parser = require("body-parser");
var app = express();
var fs = require('fs');
var path = require('path');

require('dotenv').config({
    path: path.resolve(__dirname, '../.env'),
});

var server_type = process.env.SOCKET_SERVER_TYPE || "none";
var socket_host = process.env.SOCKET_HOST || "0.0.0.0";
var socket_port = Number(process.env.SOCKET_PORT || 8890);
const config = require("./config/main");
var options = config.ssl_certificate(fs, server_type);

var http = require('http');
var https = require('https');

//Dual domain
// const SNIContexts = {
//     'accounting.wisecleaner.ph': {
//         key: fs.readFileSync("/etc/letsencrypt/live/accounting.wisecleaner.ph/privkey.pem"),
//         cert: fs.readFileSync("/etc/letsencrypt/live/accounting.wisecleaner.ph/fullchain.pem")
//     },
//     'purchasing.wisecleaner.ph': {
//         key: fs.readFileSync("/etc/letsencrypt/live/purchasing.wisecleaner.ph/privkey.pem"),
//         cert: fs.readFileSync("/etc/letsencrypt/live/purchasing.wisecleaner.ph/fullchain.pem")
//     }
// }
//=======================

http_server = http.Server(app);
var use_https = options && options.key && options.cert;
var io_server = use_https ? https.Server(options, app) : http_server;

//Dual domain
// var defaultOptions = {
//     key: fs.readFileSync('/etc/letsencrypt/live/accounting.wisecleaner.ph/privkey.pem'),
//     cert: fs.readFileSync('/etc/letsencrypt/live/accounting.wisecleaner.ph/fullchain.pem')
// };

// var https_server = https.createServer(defaultOptions, app);
// https_server.addContext('accounting.wisecleaner.ph', SNIContexts['accounting.wisecleaner.ph']);
// https_server.addContext('purchasing.wisecleaner.ph', SNIContexts['purchasing.wisecleaner.ph']);
//=======================

var io = require('socket.io')(io_server, { pingInterval: 500 , cors: {
    origin: '*',
}});

const registerRedisListener = require("./redis/main");
const registerSocketListener = require("./socket/main");

async function bootstrap() {
    console.log('[socket] bootstrap start', {
        socket_host: socket_host,
        socket_port: socket_port,
        server_type: server_type,
        redis_url: process.env.REDIS_URL || null,
        redis_host: process.env.REDIS_HOST || null,
        redis_port: process.env.REDIS_PORT || null,
    });

    registerSocketListener(io);
    await registerRedisListener(io);

    io_server.listen(socket_port, socket_host, function () {
        console.log(`[socket] listening on ${use_https ? 'https' : 'http'}://${socket_host}:${socket_port}`);
    });
}

io_server.on('error', function (error) {
    console.error('Socket server error:', error.message);
});

bootstrap().catch(function (error) {
    console.error('Socket bootstrap error:', error.message);
    process.exit(1);
});
