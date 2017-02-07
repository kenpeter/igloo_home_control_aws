Note
---
everything here is self signed, except rds-combined-ca-bundle.pem


client-cert.pem and client-key.pem
-----------
self-sign
These are used to connect to aws rds mysql instnace. It turns out we don't need them. We only the aws bundle certificate.


server-cert.pem, server-key.pem, ca-key.pem and ca-cert.pem
-------
self-sign
server-cert.pem and server-key.pem are used by laravel server
ca-key.pem and ca-cert.pem, used to do self-sign cert.


http://xmodulo.com/enable-ssl-mysql-server-client.html

https://www.thirdandgrove.com/how-to-encrypt-database-connection-in-laravel-on-heroku-with-cleardb-without-putting-ssl-certifications-or-keys-in-source-control


https://stackoverflow.com/questions/33741546/enviroment-specific-ssl-config-in-laravel-env-file
