/**
 * assets/js/scanner.js
 * Lecture de codes-barres via QuaggaJS (ou fallback saisie manuelle).
 * Expose : initScanner(onDetected), stopScanner()
 */

(function (window) {
    'use strict';

    let _running = false;

    /**
     * Initialise le scanner de codes-barres.
     * @param {string}   containerId  ID du div conteneur vidéo
     * @param {Function} onDetected   Callback(code) appelé à chaque lecture
     * @param {string}   statusId     (optionnel) ID de l'élément de statut
     */
    function initScanner(containerId, onDetected, statusId) {
        const statusEl = statusId ? document.getElementById(statusId) : null;

        function setStatus(msg) {
            if (statusEl) statusEl.textContent = msg;
        }

        if (typeof Quagga === 'undefined') {
            setStatus('⚠ QuaggaJS non disponible – saisie manuelle uniquement.');
            return;
        }

        setStatus('Démarrage de la caméra…');

        Quagga.init({
            inputStream: {
                name: 'Live',
                type: 'LiveStream',
                target: document.getElementById(containerId),
                constraints: {
                    facingMode: 'environment',
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
            },
            decoder: {
                readers: [
                    'ean_reader',
                    'ean_8_reader',
                    'code_128_reader',
                    'code_39_reader',
                    'upc_reader',
                    'upc_e_reader',
                ],
            },
            locate: true,
            numOfWorkers: navigator.hardwareConcurrency > 2 ? 2 : 1,
        }, function (err) {
            if (err) {
                console.error('Quagga init error:', err);
                setStatus('❌ Impossible d\'accéder à la caméra : ' + (err.message || err));
                return;
            }
            Quagga.start();
            _running = true;
            setStatus('📷 Caméra active – pointez vers un code-barres');
        });

        // Éviter les faux positifs : accumuler les lectures
        const detectionBuffer = {};
        const THRESHOLD = 3; // 3 lectures identiques → confirmation

        Quagga.onDetected(function (result) {
            const code = result.codeResult.code;
            if (!code) return;

            detectionBuffer[code] = (detectionBuffer[code] || 0) + 1;

            if (detectionBuffer[code] >= THRESHOLD) {
                // Vider le buffer
                Object.keys(detectionBuffer).forEach(k => delete detectionBuffer[k]);

                // Feedback visuel
                setStatus('✓ Code détecté : ' + code);

                // Flash de confirmation
                const container = document.getElementById(containerId);
                if (container) {
                    container.style.border = '3px solid #f0c040';
                    setTimeout(() => { container.style.border = ''; }, 600);
                }

                if (typeof onDetected === 'function') {
                    onDetected(code);
                }
            }
        });
    }

    /**
     * Arrête le scanner.
     */
    function stopScanner() {
        if (typeof Quagga !== 'undefined' && _running) {
            Quagga.stop();
            _running = false;
        }
    }

    // Exposition globale
    window.initScanner = initScanner;
    window.stopScanner  = stopScanner;

})(window);
