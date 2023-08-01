// periodically polls (urgh) until an 'uploader' object shows up in the global scope
(() => {
	let sneaky = setInterval(findUploader, 500);

	function findUploader() {
		let uploader = window.uploader;
		if(uploader !== undefined) {
			console.log('found it!');
			clearInterval(sneaky);
			attachToUploader(uploader);
		} else {
			console.log('still nothing')	
		}
	}

	function attachToUploader(up) {
		up.bind('FilesAdded', onFilesAdded, up, 100);
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
				uploader.removeFile(files[file]);
				
				// TODO: figure a way of using the original image data from the file

				// Con async hemos topado
				getImage((blob) => {
					let jpegFile = new File([blob], 'something.jpg');
					// Calling uploader.addFile() will trigger files added again
					// but it's OK because we skip non HEIC
					uploader.addFile(jpegFile);
				});
				return false;
			} else {
				console.log('Not an heic, benevolently ignoring ', f.type);
			}
		});
		// I THINK if you return false nothing else in the list of event handlers gets executed?? which means WP stops updating its ui so you don't get any progress updates. Not good, but you also don't get the ghost HEIC file which will never go away
		//return true;
	}

	function getImage(cb) {
		let canvas = document.createElement('canvas');
		canvas.width = 100;
		canvas.height = 100;
		ctx = canvas.getContext('2d');
		ctx.fillStyle = '#f0f';
		ctx.fillRect(0, 0, 100, 100);
		let type = 'image/jpeg';
		canvas.toBlob(cb, { type });
	}
})();