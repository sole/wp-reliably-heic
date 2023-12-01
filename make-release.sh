#!/bin/bash
set -e

# $# is the number of arguments the script got. We need one
if [ $# -ne 1 ]; then
	echo "Please specify the target svn directory. E.g. $0 TARGET_SVN_DIR"
	echo 'boom! goodbye!'
	exit 1
fi

if [ $# -eq 1 ]; then
	dst=$1
	echo "Destination directory = $dst"
	
	# normalises $dst to end with /
	if [[ $dst != */ ]]; then
		dst="$dst/"
	fi
	
	dst_assets_dir="$dst"assets
	dst_trunk_dir="$dst"trunk
	dst_assets_internal_dir="$dst_trunk_dir"/assets/internal
	
	mkdir -p $dst_assets_internal_dir

	base=$(dirname "$0")
	src_assets_dir=$base/assets
	src_assets_internal_dir="$src_assets_dir"/internal
	src_assets_plugins_dir="$src_assets_dir"/plugins-dir

	cp $src_assets_plugins_dir/*.png $dst_assets_dir
	cp $src_assets_internal_dir/test.heic $dst_assets_internal_dir
	cp -R $base/css $base/js $dst_trunk_dir
	cp $base/LICENSE.md $base/readme.txt $base/RHEIC_HEIC2JPG.php $base/RHEIC_Plugin.php $dst_trunk_dir

	echo "Files have been copied to the right folders."
	echo -e "To add and send to the repository...\n"
	echo "cd $dst"
	echo "svn add assets/*"
	echo "svn add trunk/*"
	echo "# And with your WordPress.org login details (it'll ask for your password):"
	echo "svn ci -m 'Your commit message' --username USERNAME"
	echo -e "\nGood luck! 💓\n"
fi
