/**
 * Bakong QR Payment Module
 */

import {
    generatePaymentQR,
    scanAndVerifyQR,
    confirmPayment,
    getMyTransactions
} from './api.js';

let currentPaymentOrder = null;
let paymentQRCode = null;
let scannerInstance = null;

export function initPaymentModule() {
    setupEventListeners();
}

function setupEventListeners() {
    document.addEventListener('click', (e) => {
        const target = e.target;

        if (target.closest('#btnGenerateQR')) {
            const btn = target.closest('#btnGenerateQR');
            const orderId = btn.dataset.orderId;
            const amount = parseFloat(btn.dataset.amount);
            if (orderId) openGenerateQRModal(orderId, amount);
        }

        if (target.closest('#btnScanQR')) {
            const btn = target.closest('#btnScanQR');
            const orderId = btn.dataset.orderId;
            const amount = parseFloat(btn.dataset.amount);
            if (orderId) openScanQRModal(orderId, amount);
        }

        if (target.closest('#btnConfirmPayment')) {
            confirmCurrentPayment();
        }

        if (target.closest('.payment-close')) {
            closePaymentModal();
        }
    });
}

export function showPaymentOptions(orderId, totalAmount) {
    const actionsDiv = document.querySelector('.detail-actions');
    if (!actionsDiv) return;

    const existing = actionsDiv.querySelector('.payment-actions');
    if (existing) existing.remove();

    const paymentDiv = document.createElement('div');
    paymentDiv.className = 'payment-actions';

    paymentDiv.innerHTML = `
        <div style="margin-top:0.5rem;padding-top:0.75rem;border-top:1px dashed var(--border)">
            <div style="font-size:0.8rem;font-weight:600;color:var(--text-light);margin-bottom:0.5rem;text-transform:uppercase;">
                💳 Bakong Payment
            </div>
            <button id="btnGenerateQR" class="det-btn primary" data-order-id="${orderId}" data-amount="${totalAmount}">
                📱 Generate Payment QR
            </button>
            <button id="btnScanQR" class="det-btn secondary" data-order-id="${orderId}" data-amount="${totalAmount}">
                📷 Scan to Pay
            </button>
        </div>
    `;

    actionsDiv.appendChild(paymentDiv);
}

/* ================= GENERATE QR ================= */

async function openGenerateQRModal(orderId, amount) {
    currentPaymentOrder = { orderId, amount, type: 'generate' };

    const modalHtml = `
        <div id="paymentModal" class="payment-modal-overlay">
            <div class="payment-modal">
                <button class="payment-close">×</button>
                <h3>💰 Generate Payment QR</h3>
                <p>Order: ${orderId}<br>Amount: <strong>${amount.toFixed(2)} KHR</strong></p>
                <div id="qrContainer">Click generate</div>
                <button id="btnGenerate">Generate QR</button>
            </div>
        </div>
    `;

    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = modalHtml;
    document.body.appendChild(tempDiv.firstElementChild);

    document.getElementById('btnGenerate').addEventListener('click', performGenerateQR);
}

async function performGenerateQR() {
    if (!currentPaymentOrder) return;

    const qrContainer = document.getElementById('qrContainer');
    qrContainer.innerHTML = 'Generating...';

    try {
        const res = await generatePaymentQR(
            currentPaymentOrder.amount,
            `Order ${currentPaymentOrder.orderId}`,
            currentPaymentOrder.orderId
        );

        if (res.success && res.data.qr_code) {
            displayQRCode(res.data.qr_code);
        } else {
            qrContainer.innerHTML = 'Failed to generate QR';
        }
    } catch (e) {
        qrContainer.innerHTML = 'Error generating QR';
    }
}

function displayQRCode(qrData) {
    const qrContainer = document.getElementById('qrContainer');
    qrContainer.innerHTML = `
        <img src="${qrData}" style="max-width:200px"/>
    `;
}

/* ================= SCAN QR ================= */

function openScanQRModal(orderId, amount) {
    currentPaymentOrder = { orderId, amount, type: 'scan' };

    const modalHtml = `
        <div id="paymentModal" class="payment-modal-overlay">
            <div class="payment-modal">
                <button class="payment-close">×</button>
                <h3>📷 Scan QR</h3>
                <div id="qrScanner"></div>
                <button id="btnStartScan">Start</button>
                <button id="btnConfirmPayment" disabled>Confirm</button>
                <div id="paymentResult"></div>
            </div>
        </div>
    `;

    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = modalHtml;
    document.body.appendChild(tempDiv.firstElementChild);

    initializeScanner();
}

async function initializeScanner() {
    const startBtn = document.getElementById('btnStartScan');
    const confirmBtn = document.getElementById('btnConfirmPayment');

    let scannedData = null;

    startBtn.addEventListener('click', async () => {
        if (typeof Html5Qrcode === 'undefined') {
            await loadScript('https://unpkg.com/html5-qrcode');
        }

        scannerInstance = new Html5Qrcode('qrScanner');

        await scannerInstance.start(
            { facingMode: 'environment' },
            { fps: 10 },
            (decodedText) => {
                scannedData = decodedText;
                confirmBtn.disabled = false;
                scannerInstance.stop();
                verifyScannedQR(decodedText);
            }
        );
    });

  confirmBtn.addEventListener('click', async () => {
    if (!qrToken) return;

    const resultDiv = document.getElementById('paymentResult');
    resultDiv.innerHTML = '⏳ Processing payment...';

    try {
        const res = await confirmPayment(
            qrToken,
            currentPaymentOrder.orderId,
            currentPaymentOrder.amount
        );

        resultDiv.innerHTML = res.success
            ? '✅ Payment Successful'
            : '❌ Payment Failed';
    } catch {
        resultDiv.innerHTML = 'Error processing payment';
    }
});
}

/* ================= VERIFY ================= */

async function verifyScannedQR(qrData) {
    const resultDiv = document.getElementById('paymentResult');

    try {
        const res = await scanAndVerifyQR(qrData);

        if (res.success && res.data.valid) {
            qrToken = res.data.qr_token; // ✅ IMPORTANT
            resultDiv.innerHTML = '✅ QR Verified';
        } else {
            resultDiv.innerHTML = '❌ Invalid QR';
        }
    } catch {
        resultDiv.innerHTML = 'Error verifying QR';
    }
}

function showPaymentResult(response) {
    const resultDiv = document.getElementById('paymentResult');

    resultDiv.innerHTML = response.success
        ? '✅ Payment Success'
        : '❌ Payment Failed';
}

/* ================= CLOSE ================= */

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    if (modal) modal.remove();

    if (scannerInstance) {
        scannerInstance.stop();
        scannerInstance.clear();
        scannerInstance = null;
    }

    currentPaymentOrder = null;
}

/* ================= UTIL ================= */

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = src;
        s.onload = resolve;
        s.onerror = reject;
        document.head.appendChild(s);
    });
}

/* GLOBAL */
window.PaymentModule = {
    initPaymentModule,
    showPaymentOptions,
    openScanQRModal,
    openGenerateQRModal,
    closePaymentModal
};