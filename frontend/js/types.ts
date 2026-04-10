export interface MilkProduct {
    id: number;
    name: string;
    image?: string;
    category: string;
    price?: number;
    stock?: number;
    image_url?: string | null;
    isFeatured?: boolean; // For the blue highlighted card
}

export interface ProductApiResponse {
    success: boolean;
    data: MilkProduct[];
    message?: string;
}

export interface AuthUser {
    id: number;
    username: string;
    role: string;
}

export interface MeApiResponse {
    success: boolean;
    user?: AuthUser;
    message?: string;
}

export interface AuthActionResponse {
    success: boolean;
    message?: string;
}

// Bakong Payment Types
export interface QRGenerateRequest {
    amount: number;
    description?: string;
    order_id?: string;
}

export interface QRGenerateResponse {
    success: boolean;
    message?: string;
    data: {
        order_id: string;
        qr_code?: string;
        qr_token?: string;
        amount: number;
        currency: string;
    };
}

export interface QRVerifyResponse {
    success: boolean;
    message?: string;
    data: {
        valid: boolean;
        already_paid: boolean;
        amount: number;
        merchant_name: string;
        qr_token?: string;
        order_id?: string;
        transaction_id?: string;
    };
}

export interface PaymentConfirmResponse {
    success: boolean;
    message?: string;
    data: {
        transaction_id?: string;
        order_id: string;
        amount: number;
        status: string;
    };
}

export interface TransactionStatusResponse {
    success: boolean;
    message?: string;
    data: {
        transaction_id: string;
        status: string;
        amount: number;
        currency: string;
        created_at?: string;
        completed_at?: string;
    };
}

export interface BakongTransaction {
    id: number;
    bakong_txn_id?: string;
    order_id: string;
    amount: number;
    currency: string;
    status: string;
    direction: string;
    qr_token?: string;
    description?: string;
    created_at: string;
    updated_at: string;
}

export interface TransactionsResponse {
    success: boolean;
    message?: string;
    data: BakongTransaction[];
}