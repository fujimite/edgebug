# edgebug
http://edgebug.net/

### development
1. download `caddy` binary
2. place in project root folder
3. install php cgi, including openssl, curl, and mbstring.
Unix:
4. run with `caddy run --config Caddyfile-win`
5. run php server listening on `/run/php/php-fpm.sock`
Windows:
4. generate ca cert in pwsh with `Invoke-WebRequest -Uri "https://curl.se/ca/cacert.pem" -OutFile "C:\php\cacert.pem"`
5. run with `caddy run --config Caddyfile-win`
6. run php server with `php-cgi -b 127.0.0.1:9999 -c php-win.ini`