// periodically polls (urgh) until an 'uploader' object shows up in the global scope
(() => {
	let sneaky = setInterval(findUploader, 500);
	let tries = 0;

	function findUploader() {
		let uploader = window.uploader;
		if(uploader !== undefined) {
			clearInterval(sneaky);
			attachToUploader(uploader);
		} else {
			console.log('Waiting for uploader, attempt = ' + tries);
			tries++;
			if(tries > 10) {
				console.log('Giving up on attaching to the uploader. Maybe reload the page and hope for the best?');
				clearInterval(sneaky);
			}
		}
	}

	function attachToUploader(up) {
		up.bind('FilesAdded', onFilesAdded, up, 100);
		// If we show this, then users don't need to look at the console output
		let h1 = document.querySelector('#wpbody .wrap h1');
		if(h1) {
			h1.innerHTML += ' <sup style="background: #ffe; padding: 3px; position: relative; top: -0.5em; font-size: 60%; font-style: italic;">with <b>EXPERIMENTAL</b> HEIC to JPEG support</sup>';
		}
	}

	function onFilesAdded(uploader, files) {
		console.log("files added", uploader);
		console.info(files);
		// uploader.files seems to have the files that have been added
		_.each(files, function(up, file) {
			// TODO why am I using this each thing? this should work in modern browsers...
			// I should probably get rid of this

			// file is just the file key in the files object, not the actual file object
			// so removeFile(file) has no actual effect
			let f = files[file];
			if(f.type === 'image/heic') {
				console.log(f);

				let native = f.getNative(); // This is the actual File instance in the browser
				
				let fr = new FileReader();
				fr.addEventListener('load', (e) => {
					let buffer = e.target.result;
					let h2j = new HEIF2JPG(libheif);
					let jpg = h2j.convert(buffer);

					if(jpg.length === 0) {
						console.error('h2j could not decode anything');
					} else {
						console.info('decoded', jpg.length);
					}

					setTimeout(() => {
						let img = jpg[0];
						let w = img.get_width();
						let h = img.get_height();
						console.log('Decoded: ', w, h);
						HEIFImageToJPEGBlob(img, (blob) => {
							console.log('please let the end be soon');
							let jpgFile = new File([blob], 'from-heic.jpg');
							// Calling uploader.addFile() will trigger files added again
							// but it's OK because we skip non HEIC
							uploader.addFile(jpgFile);
						});
					}, 1);
				});
				fr.readAsArrayBuffer(native);

				uploader.removeFile(files[file]);
			
				return false;
			} else {
				console.log('Not an heic, benevolently ignoring ', f.type);
			}
		});
		// I THINK if you return false nothing else in the list of event handlers gets executed?? which means WP stops updating its ui so you don't get any progress updates. Not good, but you also don't get the ghost HEIC file which will never go away
		//return true;
	}

	function HEIFImageToJPEGBlob(image, cb) {
		console.log('HEIFImageToJPEGBlob');
		let canvas = document.createElement('canvas');
		canvas.width = image.get_width();
		canvas.height = image.get_height();
		ctx = canvas.getContext('2d');
		ctx.fillStyle = '#fff';
		ctx.fillRect(0, 0, canvas.width, canvas.height);
		let imageData = ctx.createImageData(canvas.width, canvas.height);
		// This method should be called decodeAgain!
		image.display(imageData, (decodedImageData) => {
			console.log("arguably finished???");
			ctx.putImageData(decodedImageData, 0, 0);
			canvas.toBlob(cb, { type: 'image/jpeg' }, 0.1);
		});
	}
})();