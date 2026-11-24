(() => {
    const dashboardTab = document.querySelector('#dashboard-tab');
    const generateTab = document.querySelector('#generate-tab');
    const peopleTab = document.querySelector('#people-tab');
    const qrTab = document.querySelector('#qr-tab');
    const scanTab = document.querySelector('#scan-tab');
    const activityTab = document.querySelector('#activity-tab');
    const panels = {
        dashboard: document.querySelector('#dashboard-panel'),
        generate: document.querySelector('#generate-panel'),
        people: document.querySelector('#people-panel'),
        qr: document.querySelector('#qr-panel'),
        scan: document.querySelector('#scan-panel'),
        activity: document.querySelector('#activity-panel'),
    };
    const breadcrumb = document.querySelector('#breadcrumb-current');
    const navigation = new Map([
        [dashboardTab, 'dashboard'],
        [generateTab, 'generate'],
        [peopleTab, 'people'],
        [qrTab, 'qr'],
        [scanTab, 'scan'],
        [activityTab, 'activity'],
    ].filter(([tab]) => tab));
    const startButton = document.querySelector('#start-camera');
    const stopButton = document.querySelector('#stop-camera');
    const cameraSelect = document.querySelector('#camera-select');
    const video = document.querySelector('#qr-video');
    const videoHint = document.querySelector('#video-hint');
    const scanStatus = document.querySelector('#scan-status');
    const imageInput = document.querySelector('#qr-image-file');
    const resultSection = document.querySelector('#scan-result');
    const resultHeading = document.querySelector('#result-heading');
    const signatureNote = document.querySelector('#signature-note');
    const personData = document.querySelector('#person-data');
    const rawData = document.querySelector('#raw-data');
    const rawText = document.querySelector('#raw-text');
    const copyButton = document.querySelector('#copy-record');
    const scanAgainButton = document.querySelector('#scan-again');
    const openProfileButton = document.querySelector('#open-profile');

    const fieldLabels = {
        full_name: 'Full name',
        date_of_birth: 'Date of birth',
        gender: 'Gender',
        phone: 'Phone',
        email: 'Email',
        address: 'Address',
        organization: 'Organization',
        department: 'Department',
        designation: 'Designation',
        reference_number: 'Reference number',
        emergency_contact: 'Emergency contact',
        notes: 'Additional notes',
    };

    let reader = null;
    let cameraControls = null;
    let startingCamera = false;
    let scanningImage = false;
    let scanLocked = false;
    let copyText = '';
    let selectedCameraId = '';
    let cameraGeneration = 0;
    const zxingTryHarderHint = 3;

    function setStatus(message, isError = false) {
        scanStatus.textContent = message;
        scanStatus.classList.toggle('error', isError);
    }

    function createReader() {
        if (!window.ZXingBrowser || !window.ZXingBrowser.BrowserQRCodeReader) {
            throw new Error('The QR decoder could not be loaded. Refresh the page and try again.');
        }

        // DecodeHintType.TRY_HARDER is enum value 3 in the bundled ZXing core.
        return new window.ZXingBrowser.BrowserQRCodeReader(new Map([[zxingTryHarderHint, true]]));
    }

    async function refreshCameras() {
        if (!navigator.mediaDevices || typeof navigator.mediaDevices.enumerateDevices !== 'function') {
            cameraSelect.hidden = true;
            return;
        }

        const devices = await navigator.mediaDevices.enumerateDevices();
        const cameras = devices.filter((device) => device.kind === 'videoinput');
        const previousValue = selectedCameraId || cameraSelect.value;

        cameraSelect.replaceChildren();

        cameras.forEach((camera, index) => {
            const option = document.createElement('option');
            option.value = camera.deviceId;
            option.textContent = camera.label || `Camera ${index + 1}`;
            cameraSelect.append(option);
        });

        cameraSelect.hidden = cameras.length < 2;

        if (cameras.some((camera) => camera.deviceId === previousValue)) {
            cameraSelect.value = previousValue;
        }
    }

    function cameraErrorMessage(error) {
        if (!window.isSecureContext) {
            return 'Camera access requires HTTPS. Use a secure site address or localhost.';
        }

        if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            return 'This browser does not provide camera access. Use image upload or open the site in a current browser over HTTPS.';
        }

        switch (error?.name) {
            case 'NotAllowedError':
            case 'PermissionDeniedError':
                return 'Camera permission was denied. Allow camera access in the browser settings, then try again.';
            case 'NotFoundError':
            case 'DevicesNotFoundError':
                return 'No camera was found. Connect a webcam or choose a QR image.';
            case 'NotReadableError':
            case 'TrackStartError':
                return 'The camera could not start. It may be in use by another app; close that app and retry.';
            case 'OverconstrainedError':
                return 'That camera could not meet the requested settings. Choose another camera and retry.';
            case 'SecurityError':
                return 'The browser blocked camera access. Use HTTPS or localhost and check the site permissions.';
            default:
                return error?.message || 'The camera could not start. Check permissions and try again.';
        }
    }

    async function stopCamera() {
        cameraGeneration += 1;

        if (cameraControls) {
            try {
                cameraControls.stop();
            } catch {
                // The media stream is stopped below even when the decoder already stopped it.
            }

            cameraControls = null;
        }

        reader = null;

        const stream = video.srcObject;

        if (stream instanceof MediaStream) {
            stream.getTracks().forEach((track) => track.stop());
        }

        video.pause();
        video.srcObject = null;
        videoHint.hidden = false;
        startButton.disabled = false;
        stopButton.disabled = true;
        startingCamera = false;
    }

    async function startCamera() {
        if (startingCamera || cameraControls || scanLocked) {
            return;
        }

        startingCamera = true;
        const generation = ++cameraGeneration;
        startButton.disabled = true;
        stopButton.disabled = false;
        setStatus('Requesting camera access…');

        try {
            if (!window.isSecureContext) {
                throw new DOMException('Camera access requires HTTPS or localhost.', 'SecurityError');
            }

            if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                throw new DOMException('The browser does not support camera access.', 'NotSupportedError');
            }

            reader = createReader();

            const videoConstraints = {
                width: { ideal: 1280 },
                height: { ideal: 720 },
            };

            if (selectedCameraId || cameraSelect.value) {
                videoConstraints.deviceId = { exact: selectedCameraId || cameraSelect.value };
            } else {
                videoConstraints.facingMode = { ideal: 'environment' };
            }

            const activeReader = reader;
            const controls = await activeReader.decodeFromConstraints(
                { audio: false, video: videoConstraints },
                video,
                (result) => {
                    if (result && !scanLocked) {
                        scanLocked = true;
                        handleDecodedText(result.getText());
                    }
                },
            );

            if (generation !== cameraGeneration || scanLocked) {
                controls.stop();
                const pendingStream = video.srcObject;

                if (pendingStream instanceof MediaStream) {
                    pendingStream.getTracks().forEach((track) => track.stop());
                }

                video.srcObject = null;
                return;
            }

            cameraControls = controls;
            startingCamera = false;
            startButton.disabled = true;
            stopButton.disabled = false;
            videoHint.hidden = true;
            setStatus('Camera active. Hold the QR code steady inside the preview.');
            await refreshCameras();
            const activeTrack = video.srcObject instanceof MediaStream ? video.srcObject.getVideoTracks()[0] : null;
            const activeDeviceId = activeTrack?.getSettings().deviceId;

            if (activeDeviceId && [...cameraSelect.options].some((option) => option.value === activeDeviceId)) {
                selectedCameraId = activeDeviceId;
                cameraSelect.value = activeDeviceId;
            }
        } catch (error) {
            if (generation !== cameraGeneration) {
                return;
            }

            await stopCamera();
            setStatus(cameraErrorMessage(error), true);
        }
    }

    function isValidUnicode(value) {
        try {
            encodeURIComponent(value);
            return true;
        } catch {
            return false;
        }
    }

    function validatePersonPayload(decodedText) {
        let payload;

        try {
            payload = JSON.parse(decodedText);
        } catch {
            throw new Error('The QR code was read, but its content is not valid JSON.');
        }

        if (!payload || Array.isArray(payload) || typeof payload !== 'object') {
            throw new Error('The QR code does not contain a supported person record.');
        }

        const rootKeys = Object.keys(payload).sort();

        if (rootKeys.join(',') !== 'person,type,version') {
            throw new Error('The QR code has missing or unsupported payload fields.');
        }

        if (!Number.isInteger(payload.version) || payload.version !== 1) {
            throw new Error('This person QR version is not supported.');
        }

        if (payload.type !== 'person') {
            throw new Error('This QR code contains an unsupported data type.');
        }

        const person = payload.person;

        if (!person || Array.isArray(person) || typeof person !== 'object') {
            throw new Error('The person data is malformed.');
        }

        const fieldLimits = {
            full_name: 120,
            date_of_birth: 10,
            gender: 40,
            phone: 40,
            email: 254,
            address: 500,
            organization: 160,
            department: 120,
            designation: 120,
            reference_number: 80,
            emergency_contact: 80,
            notes: 800,
        };

        if (Object.keys(person).some((key) => !Object.prototype.hasOwnProperty.call(fieldLimits, key))) {
            throw new Error('The person data contains unsupported fields.');
        }

        if (typeof person.full_name !== 'string' || person.full_name.trim() === '') {
            throw new Error('The person record is missing its required full name.');
        }

        for (const [field, value] of Object.entries(person)) {
            if (typeof value !== 'string' || !isValidUnicode(value) || [...value].length > fieldLimits[field]) {
                throw new Error(`The ${fieldLabels[field] || field} field has an invalid value or is too long.`);
            }
        }

        if (person.date_of_birth !== undefined) {
            const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(person.date_of_birth);
            const date = match ? new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3]))) : null;

            if (!date || date.toISOString().slice(0, 10) !== person.date_of_birth) {
                throw new Error('The date of birth is invalid; expected YYYY-MM-DD.');
            }
        }

        if (person.email !== undefined && person.email !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/u.test(person.email)) {
            throw new Error('The email address in this record is invalid.');
        }

        return person;
    }

    function handleDecodedText(decodedText) {
        resultSection.hidden = false;
        rawData.hidden = true;
        personData.replaceChildren();
        copyButton.hidden = true;
        openProfileButton.hidden = true;
        copyText = '';

        let decodedUrl;

        try {
            decodedUrl = new URL(decodedText);
        } catch {
            decodedUrl = null;
        }

        if (decodedUrl && ['http:', 'https:'].includes(decodedUrl.protocol) && !decodedUrl.username && !decodedUrl.password) {
            const configuredOrigin = document.body.dataset.profileOrigin;
            const profilePath = /^\/profile\/[A-Za-z0-9-]+\/?$/u.test(decodedUrl.pathname);
            const isProfileUrl = profilePath && configuredOrigin && decodedUrl.origin === new URL(configuredOrigin).origin;
            const term = document.createElement('dt');
            const definition = document.createElement('dd');
            const link = document.createElement('a');
            term.textContent = isProfileUrl ? 'Public profile' : 'Web address';
            link.href = decodedUrl.href;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.textContent = decodedUrl.href;
            definition.append(link);
            personData.append(term, definition);
            resultHeading.textContent = isProfileUrl ? 'Portfolio link found' : 'Website link found';
            signatureNote.textContent = isProfileUrl
                ? 'This QR code points to a QR Manager portfolio. Open it when you are ready.'
                : 'This is an external link. Check that you trust the destination before opening it.';
            openProfileButton.href = decodedUrl.href;
            openProfileButton.textContent = isProfileUrl ? 'Open profile' : 'Open link';
            openProfileButton.hidden = false;
            copyText = decodedUrl.href;
            copyButton.hidden = false;
            setStatus(isProfileUrl ? 'Public profile URL decoded.' : 'A web address was decoded.');
            resultSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            stopCamera();

            return;
        }

        try {
            const person = validatePersonPayload(decodedText);
            const lines = [];

            Object.entries(fieldLabels).forEach(([field, label]) => {
                const value = person[field];

                if (typeof value !== 'string' || value.trim() === '') {
                    return;
                }

                const term = document.createElement('dt');
                const definition = document.createElement('dd');
                term.textContent = label;
                definition.textContent = value;
                personData.append(term, definition);
                lines.push(`${label}: ${value}`);
            });

            resultHeading.textContent = 'Person record decoded';
            signatureNote.textContent = 'This is an older QR format that contains personal details directly. Regenerate it from the person record to create a portfolio link.';
            resultHeading.textContent = 'Older person QR format';
            setStatus('Legacy person details decoded.');
            copyText = lines.join('\n');
            copyButton.hidden = false;
        } catch (error) {
            resultHeading.textContent = 'QR data could not be validated';
            signatureNote.textContent = error.message;
            setStatus(error.message, true);
            personData.replaceChildren();
            rawData.open = true;
        }

        resultSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        stopCamera();
    }

    async function decodeImage(file) {
        if (!file) {
            return;
        }

        if (scanningImage) {
            return;
        }

        const supportedTypes = ['image/png', 'image/jpeg', 'image/webp'];
        const supportedExtension = /\.(png|jpe?g|webp)$/iu.test(file.name);

        if (!supportedTypes.includes(file.type) && !supportedExtension) {
            setStatus('Choose a PNG, JPG, JPEG, or WEBP image.', true);
            imageInput.value = '';
            return;
        }

        if (file.size > 15 * 1024 * 1024) {
            setStatus('This image is larger than 15 MB. Choose a smaller image and try again.', true);
            imageInput.value = '';
            return;
        }

        scanningImage = true;
        scanLocked = true;
        resultSection.hidden = true;
        personData.replaceChildren();
        rawData.hidden = true;
        openProfileButton.hidden = true;
        copyButton.hidden = true;
        copyText = '';
        await stopCamera();
        setStatus('Decoding image on this device…');

        const imageUrl = URL.createObjectURL(file);

        try {
            reader = createReader();
            const result = await decodeUploadedImage(imageUrl);
            handleDecodedText(result.getText());
        } catch (error) {
            console.warn('QR image decoding failed.', error);
            scanLocked = false;
            resultSection.hidden = false;
            personData.replaceChildren();
            signatureNote.textContent = 'The image may be blurry, rotated, too small, or missing the QR code’s white border.';
            copyText = '';
            copyButton.hidden = true;
            resultHeading.textContent = 'No QR code could be decoded';
            signatureNote.textContent = 'Try a clearer image with the complete QR code and its white border visible. Rotated images are checked automatically.';
            rawData.hidden = true;
            setStatus('The image was opened, but no readable QR code was found.', true);
        } finally {
            URL.revokeObjectURL(imageUrl);

            reader = null;

            scanningImage = false;
            imageInput.value = '';
        }
    }

    async function decodeUploadedImage(imageUrl) {
        try {
            return await reader.decodeFromImageUrl(imageUrl);
        } catch {
            const image = new Image();
            image.src = imageUrl;

            if (typeof image.decode === 'function') {
                await image.decode();
            } else {
                await new Promise((resolve, reject) => {
                    image.onload = resolve;
                    image.onerror = reject;
                });
            }

            const sourceWidth = image.naturalWidth || image.width;
            const sourceHeight = image.naturalHeight || image.height;
            const longestSide = Math.max(sourceWidth, sourceHeight);
            const targetLongestSide = Math.min(1800, Math.max(longestSide, Math.min(900, longestSide * 3)));
            const scale = targetLongestSide / longestSide;
            const width = Math.max(1, Math.round(sourceWidth * scale));
            const height = Math.max(1, Math.round(sourceHeight * scale));

            for (const orientation of [0, 90, 180, 270]) {
                const canvas = document.createElement('canvas');
                const isSideways = orientation === 90 || orientation === 270;
                canvas.width = isSideways ? height : width;
                canvas.height = isSideways ? width : height;

                const context = canvas.getContext('2d', { willReadFrequently: true });

                if (!context) {
                    throw new Error('The image could not be prepared for QR decoding.');
                }

                context.imageSmoothingEnabled = scale < 1;
                context.save();
                context.translate(canvas.width / 2, canvas.height / 2);
                context.rotate((orientation * Math.PI) / 180);
                context.drawImage(image, -width / 2, -height / 2, width, height);
                context.restore();

                try {
                    return await reader.decodeFromCanvas(canvas);
                } catch {
                    if (orientation !== 0) {
                        continue;
                    }

                    const pixels = context.getImageData(0, 0, canvas.width, canvas.height);

                    for (let index = 0; index < pixels.data.length; index += 4) {
                        const luminance = (pixels.data[index] * 0.299) + (pixels.data[index + 1] * 0.587) + (pixels.data[index + 2] * 0.114);
                        const contrast = Math.max(0, Math.min(255, (luminance - 128) * 1.6 + 128));
                        pixels.data[index] = contrast;
                        pixels.data[index + 1] = contrast;
                        pixels.data[index + 2] = contrast;
                    }

                    context.putImageData(pixels, 0, 0);

                    try {
                        return await reader.decodeFromCanvas(canvas);
                    } catch {
                        // Continue with other orientations; upload processing stays local to this device.
                    }
                }
            }

            throw new Error('No readable QR code was found in this image.');
        }
    }

    async function copyRecord() {
        if (!copyText) {
            return;
        }

        try {
            await navigator.clipboard.writeText(copyText);
            setStatus('Person information copied.');
        } catch {
            const temporaryField = document.createElement('textarea');
            temporaryField.value = copyText;
            temporaryField.style.position = 'fixed';
            temporaryField.style.opacity = '0';
            document.body.append(temporaryField);
            temporaryField.select();
            const copied = document.execCommand('copy');
            temporaryField.remove();
            setStatus(copied ? 'Person information copied.' : 'Copy failed. Select the fields and copy them manually.', !copied);
        }
    }

    function activateTab(tab) {
        const currentScreen = navigation.get(tab);

        if (!currentScreen) {
            return;
        }
        const pageTitles = {
            dashboard: 'Dashboard',
            generate: 'Register New Person',
            people: 'People Management',
            qr: 'QR Code Management',
            scan: 'QR Scanner',
            activity: 'Activity Logs',
        };

        navigation.forEach((screen, navigationTab) => {
            const selected = navigationTab === tab;
            navigationTab.setAttribute('aria-selected', String(selected));
            navigationTab.classList.toggle('active', selected && navigationTab !== generateTab);
        });
        Object.entries(panels).forEach(([screen, panel]) => {
            panel.hidden = screen !== currentScreen;
        });
        breadcrumb.textContent = pageTitles[currentScreen];
        document.body.classList.remove('nav-open');

        if (currentScreen !== 'scan') {
            stopCamera();
        }
    }

    navigation.forEach((screen, tab) => {
        tab.addEventListener('click', () => activateTab(tab));
    });
    document.querySelectorAll('[data-open-screen]').forEach((button) => {
        button.addEventListener('click', () => {
            const destination = [...navigation.entries()].find(([, screen]) => screen === button.dataset.openScreen)?.[0];
            destination?.click();
        });
    });

    startButton.addEventListener('click', startCamera);
    stopButton.addEventListener('click', async () => {
        scanLocked = false;
        await stopCamera();
        setStatus('Camera stopped. Start it again when you are ready.');
    });

    cameraSelect.addEventListener('change', async () => {
        selectedCameraId = cameraSelect.value;
        const shouldRestart = Boolean(cameraControls);
        await stopCamera();

        if (shouldRestart) {
            await startCamera();
        }
    });

    imageInput.addEventListener('change', () => decodeImage(imageInput.files?.[0]));

    copyButton.addEventListener('click', copyRecord);
    openProfileButton.addEventListener('click', (event) => {
        const destination = new URL(openProfileButton.href);

        if (!['http:', 'https:'].includes(destination.protocol)) {
            event.preventDefault();
        }
    });
    scanAgainButton.addEventListener('click', () => {
        scanLocked = false;
        resultSection.hidden = true;
        personData.replaceChildren();
        rawData.hidden = true;
        rawText.textContent = '';
        openProfileButton.hidden = true;
        copyButton.hidden = true;
        copyText = '';
        imageInput.value = '';
        setStatus('Ready. Start the camera or choose another QR image.');
        startButton.focus();
    });

    window.addEventListener('pagehide', () => stopCamera());
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopCamera();
        }
    });

    if (navigation.size > 0) {
        const initialScreen = document.body.dataset.defaultScreen || 'dashboard';
        const initialTab = [...navigation.entries()].find(([, screen]) => screen === initialScreen)?.[0] || dashboardTab;
        activateTab(initialTab);
    }
})();
