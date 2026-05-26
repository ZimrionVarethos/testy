function imageUpload(assetInputName = null, pickerFolder = 'landing', frameOptions = {}) {
    return {
        preview: null,
        fileName: null,
        assetId: null,
        frameX: 50,
        frameY: 50,
        assetInputName,
        pickerFolder,
        frameOptions,
        handleFile(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.assetId = null;
            this.fileName = file.name;
            const reader = new FileReader();
            reader.onload = e => { this.preview = e.target.result; };
            reader.readAsDataURL(file);
        },
        openPicker() {
            window.CloudinaryPicker.open({
                uploadFolder: this.pickerFolder,
                ...this.frameOptions,
                onSelect: (asset) => this.selectAsset(asset),
            });
        },
        selectAsset(asset) {
            const frame = asset.frame || {};
            this.assetId = asset.id;
            this.preview = asset.url;
            this.fileName = asset.name || 'Cloudinary asset';
            this.frameX = frame.x || 50;
            this.frameY = frame.y || 50;
            if (this.$refs.inp) this.$refs.inp.value = '';
            if (this.$refs.newInp) this.$refs.newInp.value = '';
            if (this.$refs.sInp) this.$refs.sInp.value = '';
        },
        clear() {
            this.preview = null;
            this.fileName = null;
            this.assetId = null;
            this.frameX = 50;
            this.frameY = 50;
            if (this.$refs.inp) this.$refs.inp.value = '';
            if (this.$refs.newInp) this.$refs.newInp.value = '';
            if (this.$refs.sInp) this.$refs.sInp.value = '';
        }
    }
}

function heroSliderManager() {
    return {
        newSlots: [],
        addSlot()         { this.newSlots.push({ id: Date.now() }); },
        removeSlot(index) { this.newSlots.splice(index, 1); }
    }
}
