# Reliably HEIC

## Limitations

### Front-end side

It can only intercept uploads to media-new.php.

## System requirements

### ImageMagick extension present and activated

Uncomment `extension=imagick.so` in `php.ini`. You probably also need to restart the server.
(If using MAMP to develop locally, php.ini is in `/Applications/MAMP/bin/php/{the version of php you're using}/conf/php.ini`).

## Credits

This plugin uses the JavaScript port of [libheif](https://github.com/strukturag/libheif/tree/master) for the client-side conversion of files. `libheif` is distributed under the terms of the GNU Lesser General Public License.