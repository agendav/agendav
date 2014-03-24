Vagrant.configure("2") do |config|

  config.vm.box = "precise64"
  config.vm.box_url = "http://files.vagrantup.com/precise64.box"

  config.vm.network :forwarded_port, guest: 80, host: 9090
  config.vm.network :private_network, ip: "192.168.100.10"
  config.vm.hostname = "agendav.dev"

  config.vm.provider "virtualbox" do |v|
      v.memory = 1024
  end

  config.vm.provision :ansible do |ansible|
      ansible.playbook = "ansible/playbook.yml"
      ansible.verbose = false
      ansible.host_key_checking = false
  end
end

# vim: ft=ruby
