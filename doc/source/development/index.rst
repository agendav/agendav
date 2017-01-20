Development
===========

Please, read this section if you want to contribute to AgenDAV with code. You can even use this
information to confirm a bug.

Virtual Machine
---------------

Configuring a working environment for AgenDAV can be difficult. There are so
many requirements (web server, PHP, PHP libraries, Composer, a CalDAV server...).

To make the task of setting up an environment easier, AgenDAV ships with a
`Vagrant <https://www.vagrantup.com/>`_ file and an `Ansible
<http://www.ansible.com/home>`_ playbook. If you don't know what that means,
don't worry! They are just two tools used to generate a virtual machine with
everything prepared to work on AgenDAV.

Software you will need:

* `VirtualBox <https://www.virtualbox.org/wiki/Downloads>`_: virtualization software
* `Vagrant <https://docs.vagrantup.com/v2/installation/>`_: VM manager
* `Ansible <http://docs.ansible.com/intro_installation.html>`_: automation platform

Once you have them installed, let Vagrant initialize the virtual machine::

   $ vagrant up

A base image has to be downloaded, and lot of packages have to be installed
inside the machine, so this will take a while. Go grab a drink until the machine
is ready!

The virtual machine can be stopped and started again using Vagrant. The
initialization process will only run the first time you do ``vagrant up``, and
next starts will just require a few seconds.

You can stop the machine with ``vagrant halt``, and running ``vagrant up`` again
will bring it back to life.

Your current working directory is shared with the virtual machine, so you can
develop on your local machine and your changes will be visible inside the
virtual machine.


Accessing the virtual machine
*****************************

The environment created inside the virtual machine will be accessible using the
following URLs and commands:

* AgenDAV: http://localhost:8080/
* Baïkal server: http://localhost:8081/
* SSH: ``vagrant ssh``. Your local machine working directory will be mounted at
  ``/vagrant`` inside the virtual machine

The credentials for this environment are:

* Username: ``demo``
* Password: ``demo``

Note that :ref:`development_environment` will be enabled by default.

Building assets
---------------

If you are going to work on stylesheets, scripts or templates, you will need `npm
<https://www.npmjs.com/>`_. Run the following command to download all required dependencies::

   $ npm install
   $ npm run bower

Stylesheets
***********

Stylesheets are built using the Less pre-processor. They are written as ``.less`` files and
can be found inside the ``assets/less`` directory.

Run the following command to rebuild them::

   $ npm run build:css

Templates
*********

Stylesheets are built using the dustjs templating engine.  They can be found inside the
``assets/less`` directory.

Run the following command to rebuild them::

   $ npm run build:templates

JavaScript
**********

Run the following command to rebuild them::

   $ npm run build:js

