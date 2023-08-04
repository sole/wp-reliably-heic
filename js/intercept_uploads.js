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

		// Just how many times do we have to hide this, I wonder? it's a joke by now!
		document.querySelector('#media-upload-error').hidden = true;
		
		files.filter((file) => {
			let rightType = file.type === 'image/heic';
			if(!rightType) {
				console.log('Not an heic, filtering out', file);
			}
			return rightType;
		}).forEach(async (file) => {
			let nativeFile = file.getNative(); // This is the actual File instance in the browser
			let arrayBuffer = await nativeFile.arrayBuffer();
			let jpgBlob = await HEIC2JPG.getJPGBlob(arrayBuffer);
			let originalName = file.name;
			let newName = originalName.replace(/HEIC$/i, 'JPG');
			let jpgFile = new File([jpgBlob], newName);
			console.info(newName, ' = ', jpgBlob.size, 'bytes', roughlyMegaBytesSize(jpgBlob.size));
			
			// Calling uploader.addFile() will trigger the FilesAdded again,
			// but it's OK because we skip non HEIC files.
			// So you shouldn't enter an infinite loop.
			uploader.addFile(jpgFile);					
			
			// This removes the file from the pluploader instance (and from its queue)
			uploader.removeFile(file);

			// And this is for removing it from the visible list of uploads in the UI
			let item = document.querySelector( '#media-item-' + file.id );
			if(item) {
				item.parentElement.removeChild(item);
			}

		});

		// I THINK if you return false nothing else in the list of event handlers gets executed?? which means WP stops updating its ui so you don't get any progress updates. Not good, but you also don't get the ghost HEIC file which will never go away
		// return true;
	}

	function roughlyMegaBytesSize(n) {
		let div = n / (1024*1024);
		if(div>1) {
			let megas = Math.round(div);
			return `~${megas}Mb`;
		} else {
			return n;
		}
	}

})();