module.exports = (io) => {
    const globalListener = require("./global");

    console.log('[socket] registering socket listeners');

    io.sockets.on('connection', (socket) => {
        var address = socket.request.connection.remoteAddress;
        console.log('[socket] new connection from ' + address.replace("::", "").replace("ffff:", ""));
    });

    io.on('connection', function (socket) {
        console.log('[socket] connection handler attached for', socket.id);
        globalListener(io,socket);

        socket.on('sms_status', function(data) {
            console.log('[socket] sms_status from client', data)
        });

        socket.on('disconnect', function (reason) {
            console.log('[socket] disconnected', socket.id, reason);
        });

    });
}
