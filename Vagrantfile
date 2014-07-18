# -*- mode: ruby -*-
# vi: set ft=ruby :

VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.vm.box = "Jadoos-CentOS-2" 
  config.vm.box_url = "http://jadoos-developer-tools.s3.amazonaws.com/Jadoos-CentOS-2.box"
  
  config.vm.network :forwarded_port, guest: 80, host: 8080

  config.vm.provider :virtualbox do |vb|
   vb.customize ["modifyvm", :id, "--ioapic", "on" ]
  end
end