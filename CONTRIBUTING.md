# Contributing guidelines for AgenDAV

Great to have you here. Here are a few ways you can help make this project better!

## Mailing list

You are encouraged to use the following mailing list to ask anything before contributing code:

http://groups.google.com/group/agendav-dev

Perhaps something is already implemented or some code is going to be completely wiped, so a patch for it is not worth
the effort. Feel free to subscribe, it is a really low traffic list. Don't be afraid to ask!

## Contributing code

There are some facts that will help you when contributing code to AgenDAV:

* AgenDAV ships with a [Vagrant environment](http://docs.agendav.org/en/develop/development/#virtual-machine)
  that is absolutely recommended when developing
* AgenDAV repository uses [nvie's git flow](http://nvie.com/posts/a-successful-git-branching-model/)

### Pull requests

* Please, ask on `agendav-dev` list (see above) before sending a new PR
* Pull requests should only affect the `develop` branch. PRs that affect the `master` branch will be discarded. 
  If you think your PR could also be useful for latest stable (`master` branch) just comment it on the PR
  description
* AgenDAV code follows [PSR-1](http://www.php-fig.org/psr/psr-1/) and [PSR-2](http://www.php-fig.org/psr/psr-2/)
* Make your pull requests as small as possible. One pull request, one feature
* Make sure to add tests for your feature, and update the documentation if needed

## Issues

When reporting a bug make sure you specify the following data:

* Your AgenDAV version
* What CalDAV server you are using
* A brief description of the issue
* Step by step guide of what you did. Screenshots are welcome
* What you were expecting to happen and what actualy happened
* Any logs that could help

It would be great if you could also try to reproduce the bug on latest `HEAD`.

## Translation

AgenDAV uses [Transifex](https://www.transifex.com/adobo/agendav/) for translations.

Have a look at [Transifex documentation](http://docs.transifex.com/) for more information about
adding a new language or upadting an existing translation.

## Documentation

Documentation is automatically generated and placed on http://docs.agendav.org/ . Updating the
documentation requires some [Sphinx](http://sphinx-doc.org/) knowledge.

Have a look at the `doc/` directory.

## New Releases

- Create test build runing `npm install` & `npm run-script build`
- Run code quality tools
- Compare the »development« branch to »main«
  - Add a list of noteworthy features and bugfixes to CHANGELOG.md
  - Describe breaking changes in CHANGELOG.md
  - Describe changes in `doc/source/releasenotes.rst` as well
- Change the version, using semantic versioning, in these files
  ([example commit](https://github.com/agendav/agendav/commit/aa2c0f920207c17372b80ae45f1f4e77133d305e)):
  - `bower.json`
  - `doc/source/conf.py`
  - `package.json`
  - `web/src/Version.php`
- Merge »development« branch to »main«
- Tag the »main« branch with the new version
- Push branch and tag
- Update the documentation & website
- Add release download file to release page ([example file](https://github.com/agendav/agendav/releases/tag/2.2.0))
  - Clone the git repository using
    `git clone -b <version> https://github.com/agendav/agendav.git agendav-<version>`
  - Run `npm install && npm run-script dist`
    - Creates build files in `web/public/dist/css/`, `web/public/dist/js/`
      and `web/vendor/`
    - Removes `.git`, `ansible`, `bower_components`, `node_modules`
  - Zip directory `tar -czf ../agendav-<version>.tar.gz ../agendav-<version>`
- Sip a tea
