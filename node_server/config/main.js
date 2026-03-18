function ssl_certificate(fs, server_type)
{
    var options = {};

    if (server_type=="server_live") {
        options = {
            //SERVER 
            key: fs.readFileSync("/etc/letsencrypt/live/accounting.wisecleaner.ph/privkey.pem"),
            cert: fs.readFileSync("/etc/letsencrypt/live/accounting.wisecleaner.ph/fullchain.pem")
        }
    }
    else if (server_type=="server_local") {
        options = {
            //SERVER 
            key: fs.readFileSync("/etc/ssl/private/ssl-cert-snakeoil.key"),
            cert: fs.readFileSync("/etc/ssl/certs/ssl-cert-snakeoil.pem")
        }
    }
    else if (server_type == "xampp") {
        options = {
            //SERVER 
            key: fs.readFileSync("C:/xampp8.0/apache/conf/ssl.key/server.key"),
            cert: fs.readFileSync("C:/xampp8.0/apache/conf/ssl.crt/server.crt")
        }
    }
    else if(server_type == "default") {
        options = {
            // Localhost
            key: fs.readFileSync("ssl/ssl.key/server.key"),
            cert: fs.readFileSync("ssl/ssl.crt/server.crt")
        }
    }
    else {
        options = {};
    }

    return options;
}

module.exports = { ssl_certificate };