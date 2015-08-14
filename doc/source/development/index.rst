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

Working with scripts and stylesheets
------------------------------------

AgenDAV uses some widely known tools to help on development, such as
`Grunt <http://gruntjs.com/>`_, `Less <http://lesscss.org/>`_ and `Bower <http://bower.io/>`_.

Working with grunt
******************

Perhaps you already have them installed on your local machine, but to make
things simpler you already have them installed on the virtual machine.

If you are going to work on AgenDAV stylesheets or templates, you could benefit
from running the following command inside the virtual machine (i.e. run first ``vagrant ssh``)::

    $ cd /vagrant
    $ grunt watch

If you keep that session open, a Grunt task will look for modified ``.less`` and ``.dust`` files and
will compile them for you.

How to download or update AgenDAV frontend dependencies
*******************************************************

`Bower <http://bower.io/>`_ will do it for you::

    $ cd /vagrant
    $ bower install

You will also have to use ``grunt`` to copy all dependencies to AgenDAV ``public/`` directory. Run
the following command::

   $ grunt

Changing AgenDAV stylesheets
****************************

Stylesheets are built using the Less pre-processor. They are written as ``.less`` files and
can be found inside the ``web/assets/stylesheets`` directory.

If you have the ``grunt watch`` command running, it will automatically compile any modified
``.less`` files.
