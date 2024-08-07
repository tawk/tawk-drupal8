#!/bin/sh
set -e;

build_dir=$(dirname $0);
module_dir=$build_dir/bin/tawkto;

if [ -d "$module_dir" ]; then
	echo "Removing existing module folder";
	rm -r $module_dir;
fi

echo "Creating module folder";
mkdir -p $module_dir;

echo "Installing dependency"
composer run build:prod --working-dir=$build_dir/..

echo "Copying files to module folder";
cp -rt $module_dir $build_dir/../config $build_dir/../src $build_dir/../vendor $build_dir/../composer.* $build_dir/../tawk_to.*

echo "Done building module folder";

echo "Building docker image"
if [ -z $1 ]; then
	docker-compose build;
else
	docker-compose --env-file $1 build;
fi
