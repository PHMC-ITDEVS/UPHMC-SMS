module.exports = (io, socket) => {
    socket.on('channel_connect', function (id) {
        let channel = id;
        socket.join(channel);
        console.log("Created a token channel", channel);
    });
}