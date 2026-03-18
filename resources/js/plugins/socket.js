import io from 'socket.io-client';

const socketProtocol = window.location.protocol;
const socketHostname = window.location.hostname;
const socketPort = import.meta.env.VITE_SOCKET_PORT || '8890';
const socketUrl = import.meta.env.VITE_SOCKET_URL || `${socketProtocol}//${socketHostname}:${socketPort}`;

const socket = io(socketUrl, {
    autoConnect: false,
    reconnectionDelay: 1000,
    reconnection: true,
    reconnectionAttempts: Infinity,
    transports: ['websocket'],
    upgrade: false,
    rejectUnauthorized: false,
});

export default socket
