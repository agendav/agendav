Vagrant.configure("2") do |config|

  config.vm.box = "ubuntu/bionic64"

  config.vm.hostname = "agendav.dev"
  config.vm.network "forwarded_port", guest: 80, host: 8080
  config.vm.network "forwarded_port", guest: 81, host: 8081

  config.vm.synced_folder '.', '/vagrant'
  config.vm.synced_folder './web/var', '/vagrant/web/var',
    owner: 'www-data', group: 'www-data'

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
