@include('includes.head')
@include('sweetalert::alert')

<div class="page-wrapper">
    <div class="content container-fluid">
        <style>
            #signature-pad {
                border: 2px solid hsl(0, 0%, 0%);
                border-radius: 5px;
                max-width: 100%;
                height: 60px;
                margin: 0 auto;
                display: block;
            }
        </style>

        <div class="container mt-5">
            <h2>Capture Signature</h2>
            <div class="row">
                <div class="col-md-12">
                    <canvas id="signature-pad" class="signature-pad"></canvas>
                </div>
                <div class="col-md-12 mt-3">
                    <form id="signature-form" action="{{ route('signature.store') }}" method="POST">
                        @csrf
                        <input type="hidden" id="signature-input" name="signature">
                        <button type="button" id="clear" class="btn btn-danger">Clear</button>
                        <button type="submit" id="save" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
        <script>
            const clearButton = document.getElementById('clear');
            const saveButton = document.getElementById('save');
            const signatureForm = document.getElementById('signature-form');
            const signatureInput = document.getElementById('signature-input');
            const canvas = document.getElementById('signature-pad');
            const signaturePad = new SignaturePad(canvas, {
                penColor: 'rgb(0, 0, 255)' // Set pen color
            });

            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext('2d').scale(ratio, ratio);
                signaturePad.clear(); // otherwise isEmpty() might return incorrect value
            }

            window.addEventListener('resize', resizeCanvas);
            resizeCanvas();

            clearButton.addEventListener('click', () => {
                signaturePad.clear();
            });

            saveButton.addEventListener('click', () => {
                if (signaturePad.isEmpty()) {
                    alert('Please provide a signature first.');
                    return;
                }

                const dataURL = signaturePad.toDataURL('image/png');
                const img = new Image();
                img.src = dataURL;
                img.onload = function() {
                    const imgWidth = img.width;
                    const imgHeight = img.height;
                    const offscreenCanvas = document.createElement('canvas');
                    const offscreenCtx = offscreenCanvas.getContext('2d');

                    offscreenCanvas.width = imgWidth;
                    offscreenCanvas.height = imgHeight;
                    offscreenCtx.drawImage(img, 0, 0);

                    const boundingBox = {
                        left: imgWidth,
                        top: imgHeight,
                        right: 0,
                        bottom: 0
                    };

                    const imgData = offscreenCtx.getImageData(0, 0, imgWidth, imgHeight);
                    const data = imgData.data;

                    for (let y = 0; y < imgHeight; y++) {
                        for (let x = 0; x < imgWidth; x++) {
                            const index = (y * imgWidth + x) * 4;
                            if (data[index + 3] > 0) {
                                boundingBox.left = Math.min(boundingBox.left, x);
                                boundingBox.top = Math.min(boundingBox.top, y);
                                boundingBox.right = Math.max(boundingBox.right, x);
                                boundingBox.bottom = Math.max(boundingBox.bottom, y);
                            }
                        }
                    }

                    const { left, top, right, bottom } = boundingBox;
                    const width = right - left;
                    const height = bottom - top;

                    const croppedCanvas = document.createElement('canvas');
                    const croppedCtx = croppedCanvas.getContext('2d');
                    croppedCanvas.width = width;
                    croppedCanvas.height = height;
                    croppedCtx.drawImage(img, left, top, width, height, 0, 0, width, height);

                    const croppedDataURL = croppedCanvas.toDataURL('image/png');
                    signatureInput.value = croppedDataURL;
                    signatureForm.submit();
                };
            });

            canvas.addEventListener('touchstart', (event) => {
                const touch = event.touches[0];
                const mouseEvent = new MouseEvent('mousedown', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            }, false);

            canvas.addEventListener('touchmove', (event) => {
                const touch = event.touches[0];
                const mouseEvent = new MouseEvent('mousemove', {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                });
                canvas.dispatchEvent(mouseEvent);
            }, false);

            canvas.addEventListener('touchend', (event) => {
                const mouseEvent = new MouseEvent('mouseup', {});
                canvas.dispatchEvent(mouseEvent);
            }, false);
        </script>
    </div>
</div>
