Vagrant.configure("2") do |config|
  config.vm.define "db" do |db|
    db.vm.network "private_network", ip: "172.17.5.10"
    db.vm.provision "shell", inline: <<-SHELL
      /vagrant/.setup_db_20191210.sh
    SHELL
  end
  config.vm.define "app" do |app|
    app.vm.provider "virtualbox" do |vb|
      vb.memory = "2048"
    end
    app.vm.network "forwarded_port", guest: 80, host: 12280
    app.vm.network "private_network", ip: "172.17.5.20"
    app.vm.provision "shell", inline: <<-SHELL
      echo "172.17.5.10 db" >> /etc/hosts
      export DEBIAN_FRONTEND=noninteractive
      ################################
      # bindfs for vagrant only
      ################################
      mkdir /app
      mkdir /vagrant.www-data
      apt-get update
      apt-get install bindfs
      echo "/vagrant /vagrant.www-data fuse.bindfs map=vagrant/www-data:@vagrant/@www-data,perms=0600:u+X 0 0" >> /etc/fstab
      mount -a
      ln -s /vagrant.www-data/* /vagrant.www-data/.[a-zA-Z]* /app/
      rm /app/artisan
      cp /vagrant.www-data/artisan /app/
      chown -R www-data:www-data /app /var/www
      ################################
      # bindfs for vagrant only
      ################################
      /app/.setup_tz_20191210.sh
      /app/.setup_app_20191210.sh
    SHELL
  end
  config.vm.box_check_update = false
  config.vm.box = "ubuntu/bionic64"
end
