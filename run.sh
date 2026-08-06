#!/bin/zsh

log() {
    msg=$1
    echo "[script] $msg"
}

echo "machine artifactory.cloud.capitalone.com" > ./artifactory/apt-auth.conf
echo "login $ARTIFACTORY_USERNAME" >> ./artifactory/apt-auth.conf
echo "password $ARTIFACTORY_IDENTITY_TOKEN" >> ./artifactory/apt-auth.conf


containerName=wpdev
dockerName=wpdev1
match=$(docker ps -aqf "name=$dockerName")
echo "Parameter TAG is $match"
[[ -n $match ]] &&  docker rm -f $match

# Rebuild the image
docker build --platform=linux/amd64 --secret id=apt-auth,src=./artifactory/apt-auth.conf --build-arg PROXY=$http_proxy -t ${containerName}:latest .

if [ $? -ne 0 ]
then
    exit
fi

log "===== Start"

log "Starting Docker"
docker run -d --name ${dockerName} \
    --platform=linux/amd64 \
    -v "$(pwd)/wp-content:/srv/www/wordpress/wp-content" \
    -v "$(pwd)/spark-mentor-mentee-hub:/srv/www/wordpress/wp-content/plugins/spark-mentor-mentee-hub" \
    -v mysql_data:/var/lib/mysql \
    -p 9090:9090 \
    ${containerName}:latest

log "Complete"

