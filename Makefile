PWD=$(shell pwd)
LOCAL=.gitmodules_cornerstone

all: update

update:
	rm .gitmodules -f || exit 0;
	ln -s $(PWD)/.gitmodules_upstream .gitmodules
	git submodule update --init
	git submodule sync
	git submodule foreach git checkout master
	git submodule foreach git pull origin master
	rm .gitmodules -f
	ln -s $(PWD)/.gitmodules_cornerstone .gitmodules
	
update-local:
	rm .gitmodules -f
	ln -s $(PWD)/.gitmodules_cornerstone .gitmodules
	git submodule sync
	git submodule foreach git push

local:
	rm .gitmodules -f
	cp $(PWD)/.gitmodules_cornerstone .gitmodules
		
