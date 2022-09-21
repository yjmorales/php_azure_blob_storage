cd /tmp
mkdir yjm_instalactions
cd yjm_instalactions
apt-get install wget
wget http://download.redis.io/redis-stable.tar.gz
tar xvzf redis-stable.tar.gz
cd redis-stable
make


cp src/redis-server /usr/local/bin/
cp src/redis-cli /usr/local/bin/

redis-server &
redis-cli ping	