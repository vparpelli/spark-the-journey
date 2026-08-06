#!/bin/zsh

# Clear SF auth transients before dumping so tokens don't end up in git
docker exec wpdev1 bash -c "mysql -u wordpress -pchangeme wordpress -e \"DELETE FROM wp_options WHERE option_name LIKE '_transient%spark_sf%';\"" 2>/dev/null

docker exec wpdev1 bash -c "mysqldump --no-tablespaces --single-transaction -u wordpress -pchangeme wordpress" > seed.sql
