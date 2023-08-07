class HEIC2JPG {

	static async getJPGBlob(arrayBuffer, options = []) {
		let libheif = options.libheif ? options.libheif : window.libheif;
		let maxWidth = options.maxWidth ? options.maxWidth : -1;
		let maxHeight = options.maxHeight ? options.maxHeight : -1;

		let decodedImages = HEIC2JPG.convert(arrayBuffer, libheif);
		if(decodedImages.length === 0) {
			throw new Exception('HEIF2JPG: the buffer cannot be decoded as an image');
		}
		let firstImage = decodedImages[0];
		let canvas = await HEIC2JPG.getCanvasFromImage(firstImage, { maxWidth, maxHeight });
		let blob = await HEIC2JPG.getCanvasAsJPGBlob(canvas, 0.85);
		console.log(blob);
		return blob;
	}

	// arrayBuffer is an Uint8Array i.e. the output of FileReader.readAsArrayBuffer(file)
	static convert(arrayBuffer, libheif) {
		let decoder =  new libheif.HeifDecoder();
		let imageData = decoder.decode(arrayBuffer);
		return imageData;
	}

	static async getCanvasFromImage(libheifImage, options = {}) {
		let canvas = document.createElement('canvas');
		let { maxWidth, maxHeight } = options;
		let imageWidth = libheifImage.get_width();
		let imageHeight = libheifImage.get_height();
		let finalWidth = imageWidth;
		let finalHeight = imageHeight;

		console.log('Read image dimensions', imageWidth, imageHeight);
		
		let heightRatio = maxWidth * 1.0 / imageWidth;
		let widthRatio = maxHeight * 1.0 / imageHeight;
		let ratios = [ widthRatio, heightRatio ];
		
		// Filter out if either max was set as -1 (default)
		ratios = ratios.filter(r => r > 0);
		let finalRatio = Math.min(ratios);
		if(ratios.length > 0 && finalRatio < 1) {
			finalWidth = Math.round(imageWidth * finalRatio);
			finalHeight = Math.round(imageHeight * finalRatio);
			console.log('Resizing incoming!!')
			console.log(finalRatio, 'Final sizes should be ', finalWidth, finalHeight);
		}
		
		canvas.width = imageWidth;
		canvas.height = imageHeight;
		let ctx = canvas.getContext('2d');
		ctx.fillStyle = '#fff';
		ctx.fillRect(0, 0, canvas.width, canvas.height);
		let imageData = ctx.createImageData(canvas.width, canvas.height);
		return new Promise((res, rej) => {
			// TODO: it would be nice to know what errors can occur, and `rej` if so
			libheifImage.display(imageData, async (decodedImageData) => {
				console.log('image is decoded');
				ctx.putImageData(decodedImageData, 0, 0);
				
				if(finalWidth !== imageWidth || finalHeight !== imageHeight) {
					let resizedCanvas = await HEIC2JPG.resizeCanvas(canvas, finalWidth, finalHeight);
					res(resizedCanvas);
				} else {
					res(canvas);
				}
				
			})
		});
	}

	static async getCanvasAsJPGBlob(canvas, quality = 1.0) {
		return new Promise((res) => {
			canvas.toBlob(res, 'image/jpeg', quality);
		});
	}

	static async resizeCanvas(canvas, finalWidth, finalHeight) {
		return new Promise((res) => {
			if(canvas.width === finalWidth && canvas.height === finalHeight) {
				// Nice no-op
				res(canvas);
			} else {
				let resizedCanvas = document.createElement('canvas');
				resizedCanvas.width = finalWidth;
				resizedCanvas.height = finalHeight;
				let ctx = resizedCanvas.getContext('2d');
				ctx.drawImage(canvas, 0, 0, canvas.width, canvas.height, 0, 0, finalWidth, finalHeight);
				res(resizedCanvas);
			}
		});
	}
	
}