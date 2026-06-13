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

  create: async (invoice: Partial<InvoiceDetail>): Promise<{data: InvoiceDetail}> => {
    // Simulating API creation delay
    await new Promise((resolve) => setTimeout(resolve, 800));
    const total = invoice.total || 0;
    const amount_paid = invoice.amount_paid || 0;
    return {
      data: {
        id: Math.floor(Math.random() * 10000) + 1000,
        reference: invoice.reference || 'INV-' + Math.random().toString(36).substring(2, 10).toUpperCase(),
        type: invoice.type || 'other',
        status: invoice.status || 'draft',
        subtotal: invoice.subtotal || total,
        tax_amount: invoice.tax_amount || 0,
        total: total,
        amount_paid: amount_paid,
        balance: total - amount_paid,
        due_date: invoice.due_date || new Date().toISOString().split('T')[0],
        period_month: invoice.period_month || new Date().getMonth() + 1,
        period_year: invoice.period_year || new Date().getFullYear(),
        property: invoice.property || null,
        issued_at: new Date().toISOString(),
        paid_at: null,
        tenant: invoice.tenant || null,
        property_detail: invoice.property_detail || null,
        line_items: invoice.line_items || [],
      }
    };
  },

  sendBulkReminders: async (ids: number[]): Promise<{success: boolean; count: number}> => {
    await new Promise((resolve) => setTimeout(resolve, 1000));
    return { success: true, count: ids.length };
  },
};

