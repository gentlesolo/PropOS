import {apiClient} from './client';

export interface InvoiceLineItem {
  description: string;
  category: string;
  quantity: number;
  unit_price: number;
  amount: number;
}

export interface InvoiceListItem {
  id: number;
  reference: string;
  type: 'rent' | 'commission' | 'maintenance' | 'utility' | 'other';
  status: 'draft' | 'sent' | 'paid' | 'partially_paid' | 'overdue' | 'void';
  subtotal: number;
  tax_amount: number;
  total: number;
  amount_paid: number;
  balance: number;
  due_date: string;
  period_month: number;
  period_year: number;
  property: string | null;
  issued_at: string | null;
  paid_at: string | null;
}

export interface InvoiceDetail extends InvoiceListItem {
  tenant: {
    id: number;
    first_name: string;
    last_name: string;
    phone: string | null;
    email: string | null;
  } | null;
  property_detail: {
    id: number;
    address_line_1: string;
    city: string;
  } | null;
  line_items: InvoiceLineItem[];
}

export interface PaginatedMeta {
  current_page: number;
  last_page: number;
  total: number;
}

export const invoicesApi = {
  list: (params?: {status?: string; page?: number}) =>
    apiClient.get<{data: InvoiceListItem[]; meta: PaginatedMeta}>('/invoices', {params}),

  show: (id: number) =>
    apiClient.get<{data: InvoiceDetail}>(`/invoices/${id}`),

  payNow: (id: number) =>
    apiClient.post<{url: string; payment_id: string}>(`/invoices/${id}/pay`, {}),
};
