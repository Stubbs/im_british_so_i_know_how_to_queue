# Add the RabbitMQ Debian/Ubuntu repository to apt's list of package sources.
cat >> /etc/apt/sources.list <<EOT
deb http://www.rabbitmq.com/debian/ testing main
EOT

# Download & install the RabbitMQ public key so apt can check signatures on
# the packages it installs.
wget http://www.rabbitmq.com/rabbitmq-signing-key-public.asc
apt-key add rabbitmq-signing-key-public.asc

# Reload the apt indexes so it knows what the new repository has available.
apt-get update

# Install RabbitMQ, apt will also work out & install it's dependencies.
apt-get install -q -y rabbitmq-server

# Create an admin user for you & a client user for our scripts to use.
# Gives the client user permission to do what it likes with any queue.
# See https://www.rabbitmq.com/access-control.html for more info on access
# config.
rabbitmqctl add_user admin admin
rabbitmqctl add_user client client
rabbitmqctl set_user_tags admin administrator
rabbitmqctl set_permissions client ".*" ".*" ".*"

rabbitmq-plugins enable rabbitmq_management
/etc/init.d/rabbitmq-server restart
