#!/usr/bin/make -f
# 

package=awl
version=$(shell cat VERSION)

all: built-docs 

built-docs: docs/api/phpdoc.ini inc/*.php
	phpdoc -c docs/api/phpdoc.ini || echo "WARNING: the docs could not be built"
	touch built-docs

#
# Insert the current version number into AWLUtilities.php
#
inc/AWLUtilities.php: scripts/build-AWLUtilities.sh VERSION inc/AWLUtilities.php.in
	scripts/build-AWLUtilities.sh <inc/AWLUtilities.php.in >inc/AWLUtilities.php


#
# Build a release .tar.gz file in the directory above us
#
release: built-docs
	-ln -s . $(package)-$(version)
	tar czf ../$(package)-$(version).tar.gz \
	    --no-recursion --dereference $(package)-$(version) \
	    $(shell git ls-files |grep -v '.git'|sed -e s:^:$(package)-$(version)/:) \
	    $(shell find $(package)-$(version)/docs/api/ ! -name "phpdoc.ini" )
	rm $(package)-$(version)
	
clean:
	rm -f built-docs
	-find . -name "*~" -delete
	
clean-all: clean
	-find docs/api/* ! -name "phpdoc.ini" ! -name ".gitignore" -delete

.PHONY:  all clean release
