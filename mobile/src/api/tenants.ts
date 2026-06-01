import {apiClient} from './client';

export interface TenantListItem {
  id: number;
  full_name: string | null;
  status: 'prospect' | 'active' | 'vacating' | 'vacated' | 'blacklisted';
  property: string | null;
  monthly_rent: number | null;
  lease_end_date: string | null;
  fica_count: number;
  portal_token: string | null;
}

export interface TenantDetail extends TenantListItem {
  contact: {
    id: number;
    first_name: string;
    last_name: string;
    phone: string | null;
    email: string | null;
    id_number: string | null;
  } | null;
  agent: {id: number; name: string; phone: string | null; email: string | null} | null;
  active_lease: LeaseDetail | null;
  recent_payments: PaymentItem[];
}

export interface LeaseListItem {
  id: number;
  reference: string;
  status: string;
  tenant_name: string | null;
  property: string | null;
  monthly_rent: number;
  deposit_amount: number | null;
  escalation_percent: number;
  payment_day: number;
  start_date: string;
  end_date: string;
  days_until_expiry: number;
  outstanding_balance: number;
}

export interface LeaseDetail extends LeaseListItem {
  tenant: {id: number; first_name: string; last_name: string; phone: string | null; email: string | null} | null;
  property: {id: number; address_line_1: string; city: string} | null;
  rent_payments: PaymentItem[];
}

export interface PaymentItem {
  id: number;
  reference: string;
  amount_due: number;
  amount_paid: number | null;
  status: 'pending' | 'partial' | 'paid' | 'overdue' | 'waived';
  due_date: string;
  paid_date: string | null;
  method?: string | null;
}

export interface PaginatedMeta {
  current_page: number;
  last_page: number;
  total: number;
}

export const tenantsApi = {
  list: (params?: {status?: string; search?: string; page?: number}) =>
    apiClient.get<{data: TenantListItem[]; meta: PaginatedMeta}>('/tenants', {params}),

  show: (id: number) =>
    apiClient.get<{data: TenantDetail}>(`/tenants/${id}`),
};

export const leasesApi = {
  list: (params?: {status?: string; page?: number}) =>
    apiClient.get<{data: LeaseListItem[]; meta: PaginatedMeta}>('/leases', {params}),

  show: (id: number) =>
    apiClient.get<{data: LeaseDetail}>(`/leases/${id}`),

  recordPayment: (leaseId: number, body: {
    amount_paid: number;
    paid_date: string;
    payment_method: 'eft' | 'cash' | 'card' | 'cheque';
    notes?: string;
  }) =>
    apiClient.post<{message: string; data: PaymentItem}>(`/leases/${leaseId}/payments`, body),
};
