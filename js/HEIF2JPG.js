class HEIF2JPG {
	constructor(libheif) {
		this.decoder = new libheif.HeifDecoder();
	}

	convert(buffer) {
		// buffer is an Uint8Array(?) - the output of FileReader.readAsArrayBuffer(file)
		let imageData = this.decoder.decode(buffer);
		return imageData;
	}
}