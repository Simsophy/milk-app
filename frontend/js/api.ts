// api.ts
import {
    AuthActionResponse,
    MeApiResponse,
    ProductApiResponse,
    QRGenerateResponse,
    QRVerifyResponse,
    PaymentConfirmResponse,
    TransactionStatusResponse,
    TransactionsResponse
} from './types.js';

const API_BASE = '/routes/api.php';

type HttpMethod = 'GET' | 'POST';

async function requestJson<T>(
    action: string,
    method: HttpMethod = 'GET',
    body?: unknown
): Promise<T> {
    const response = await fetch(`${API_BASE}?action=${action}`, {
        method,
        credentials: 'include', // ✅ IMPORTANT FIX
        headers: method === 'POST'
            ? { 'Content-Type': 'application/json' }
            : undefined,
        body: body !== undefined ? JSON.stringify(body) : undefined
    });

    if (!response.ok) {
        throw new Error(`Request failed (${action}): ${response.status}`);
    }

    return response.json() as Promise<T>;
}

// AUTH
export const fetchMe = () => requestJson<MeApiResponse>('me');
export const login = (username: string, password: string) =>
    requestJson<AuthActionResponse>('login', 'POST', { username, password });

export const register = (payload: any) =>
    requestJson<AuthActionResponse>('register', 'POST', payload);

export const logout = () =>
    requestJson<AuthActionResponse>('logout');

// PRODUCTS
export const fetchProducts = () =>
    requestJson<ProductApiResponse>('products');

// PAYMENTS
export const generatePaymentQR = (amount: number, description?: string, orderId?: string) =>
    requestJson<QRGenerateResponse>('generate-qr', 'POST', {
        amount,
        description,
        order_id: orderId
    });

export const scanAndVerifyQR = (qrData: string) =>
    requestJson<QRVerifyResponse>('verify-qr', 'POST', {
        qr_data: qrData
    });

export const confirmPayment = (qrToken: string, orderId: string, amount?: number) =>
    requestJson<PaymentConfirmResponse>('confirm-payment', 'POST', {
        qr_token: qrToken,
        order_id: orderId,
        amount
    });

export const getTransactionStatus = (transactionId: string) =>
    requestJson<TransactionStatusResponse>('transaction-status', 'POST', {
        transaction_id: transactionId
    });

export const getMyTransactions = () =>
    requestJson<TransactionsResponse>('my-transactions');