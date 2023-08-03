class HEIF2JPG {
	constructor(libheif) {
		this.decoder = new libheif.HeifDecoder();
	}

	static async getJPGBlob(arrayBuffer, options = []) {
		let libheif = options.libheif ? options.libheif : window.libheif;
		let h2j = new HEIF2JPG(libheif);
		
		let decodedImages = h2j.convert(arrayBuffer);
		if(decodedImages.length === 0) {
			throw new Exception('h2j: the buffer cannot be decoded as an image');
		}
		let firstImage = decodedImages[0];
		let canvas = await HEIF2JPG.getCanvasFromImage(firstImage);
		let blob = await HEIF2JPG.getCanvasAsJPGBlob(canvas);
		return blob;
	}

	convert(buffer) {
		// buffer is an Uint8Array(?) - the output of FileReader.readAsArrayBuffer(file)
		let imageData = this.decoder.decode(buffer);
		return imageData;
	}

	static async getCanvasFromImage(libheifImage) {
		console.log('getCanvasFromImage')
		let canvas = document.createElement('canvas');
		canvas.width = libheifImage.get_width();
		canvas.height = libheifImage.get_height();
		let ctx = canvas.getContext('2d');
		ctx.fillStyle = '#fff';
		ctx.fillRect(0, 0, canvas.width, canvas.height);
		let imageData = ctx.createImageData(canvas.width, canvas.height);
		return new Promise((res, rej) => {
			// TODO: it would be nice to know what errors can occur, and `rej` if so
			libheifImage.display(imageData, (decodedImageData) => {
				console.log('image is decoded');
				ctx.putImageData(decodedImageData, 0, 0);
				res(canvas);
			})
		});
	}

	static async getCanvasAsJPGBlob(canvas, quality = 1.0) {
		console.log('getCanvasAsJPGBlob')
		return new Promise((res) => {
			canvas.toBlob(res, { type: 'image/jpeg' }, quality);
		});
	}
}