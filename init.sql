CREATE DATABASE wordpress;
CREATE USER 'wordpress'@'localhost' IDENTIFIED WITH mysql_native_password BY 'changeme';
GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,DROP,ALTER
    ON wordpress.*
    TO 'wordpress'@'localhost';
FLUSH PRIVILEGES;