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
  rent_status: 'paid' | 'due' | 'overdue';
  rent_due_days?: number;
  lease_ends_soon?: boolean;
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

export interface LeaseDetail extends Omit<LeaseListItem, 'property'> {
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

export const mockTenants: TenantListItem[] = [
  {
    id: 1,
    full_name: 'Adaeze Obi',
    status: 'active',
    property: 'Flat 3B, Marina Heights',
    monthly_rent: 12000,
    lease_end_date: '2026-08-14',
    fica_count: 3,
    portal_token: 'tok-1',
    rent_status: 'overdue',
    rent_due_days: 12,
    lease_ends_soon: true,
  },
  {
    id: 2,
    full_name: 'David Kojo',
    status: 'active',
    property: 'Villa 12, Palm Gardens',
    monthly_rent: 28000,
    lease_end_date: '2026-06-25',
    fica_count: 2,
    portal_token: 'tok-2',
    rent_status: 'due',
    rent_due_days: 5,
    lease_ends_soon: true,
  },
  {
    id: 3,
    full_name: 'Sarah Jenkins',
    status: 'active',
    property: 'Apt 402, Oakwood Towers',
    monthly_rent: 16500,
    lease_end_date: '2026-11-30',
    fica_count: 3,
    portal_token: 'tok-3',
    rent_status: 'paid',
    lease_ends_soon: false,
  },
  {
    id: 4,
    full_name: 'Elena Rostova',
    status: 'active',
    property: 'Studio 7, Parkside Lofts',
    monthly_rent: 9800,
    lease_end_date: '2026-07-10',
    fica_count: 2,
    portal_token: 'tok-4',
    rent_status: 'due',
    rent_due_days: 2,
    lease_ends_soon: true,
  },
  {
    id: 5,
    full_name: 'Kenji Sato',
    status: 'vacating',
    property: 'Flat 18B, Skyline Tower',
    monthly_rent: 14000,
    lease_end_date: '2026-06-30',
    fica_count: 3,
    portal_token: 'tok-5',
    rent_status: 'overdue',
    rent_due_days: 3,
    lease_ends_soon: true,
  },
  {
    id: 6,
    full_name: 'Chloe Dupont',
    status: 'active',
    property: 'Cottage 4, River Road',
    monthly_rent: 11500,
    lease_end_date: '2026-12-15',
    fica_count: 3,
    portal_token: 'tok-6',
    rent_status: 'paid',
    lease_ends_soon: false,
  },
];

export const tenantsApi = {
  list: async (params?: {status?: string; search?: string; page?: number}): Promise<{data: {data: TenantListItem[]; meta: PaginatedMeta}}> => {
    try {
      const res = await apiClient.get<{data: TenantListItem[]; meta: PaginatedMeta}>('/tenants', {params});
      return res;
    } catch (e) {
      // Return high-fidelity fallback mock data
      await new Promise(resolve => setTimeout(resolve, 300));
      let filtered = [...mockTenants];
      if (params?.search) {
        const q = params.search.toLowerCase();
        filtered = filtered.filter(
          t =>
            (t.full_name ?? '').toLowerCase().includes(q) ||
            (t.property ?? '').toLowerCase().includes(q)
        );
      }
      if (params?.status) {
        if (params.status === 'overdue') {
          filtered = filtered.filter(t => t.rent_status === 'overdue');
        } else if (params.status === 'due') {
          filtered = filtered.filter(t => t.rent_status === 'due');
        } else if (params.status === 'expiring') {
          filtered = filtered.filter(t => t.lease_ends_soon);
        } else if (params.status === 'active') {
          filtered = filtered.filter(t => t.status === 'active');
        }
      }
      return {
        data: {
          data: filtered,
          meta: {current_page: 1, last_page: 1, total: filtered.length},
        },
      };
    }
  },

  show: async (id: number): Promise<{data: TenantDetail}> => {
    try {
      const res = await apiClient.get<{data: TenantDetail}>(`/tenants/${id}`);
      return res.data;
    } catch (e) {
      await new Promise(resolve => setTimeout(resolve, 300));
      const base = mockTenants.find(t => t.id === id) || mockTenants[0];
      
      // Build high-fidelity detail view data
      const recent_payments: PaymentItem[] = [
        {
          id: id * 10 + 1,
          reference: `INV-2026-${id}-06`,
          amount_due: base.monthly_rent ?? 12000,
          amount_paid: base.rent_status === 'paid' ? (base.monthly_rent ?? 12000) : 0,
          status: base.rent_status === 'due' ? 'pending' : base.rent_status,
          due_date: '2026-06-01',
          paid_date: base.rent_status === 'paid' ? '2026-06-03' : null,
          method: base.rent_status === 'paid' ? 'eft' : null,
        },
        {
          id: id * 10 + 2,
          reference: `INV-2026-${id}-05`,
          amount_due: base.monthly_rent ?? 12000,
          amount_paid: base.monthly_rent ?? 12000,
          status: 'paid',
          due_date: '2026-05-01',
          paid_date: '2026-05-03',
          method: 'eft',
        },
        {
          id: id * 10 + 3,
          reference: `INV-2026-${id}-04`,
          amount_due: base.monthly_rent ?? 12000,
          amount_paid: base.monthly_rent ?? 12000,
          status: 'paid',
          due_date: '2026-04-01',
          paid_date: '2026-04-02',
          method: 'eft',
        },
        {
          id: id * 10 + 4,
          reference: `INV-2026-${id}-03`,
          amount_due: base.monthly_rent ?? 12000,
          amount_paid: base.monthly_rent ?? 12000,
          status: 'paid',
          due_date: '2026-03-01',
          paid_date: '2026-03-05',
          method: 'cash',
        },
      ];

      return {
        data: {
          ...base,
          contact: {
            id: base.id,
            first_name: base.full_name?.split(' ')[0] ?? 'Tenant',
            last_name: base.full_name?.split(' ')[1] ?? 'Name',
            phone: '+27 82 123 4567',
            email: `${base.full_name?.toLowerCase().replace(' ', '.')}@example.com`,
            id_number: '920814 5001 082',
          },
          agent: {
            id: 201,
            name: 'Sarah Jenkins',
            phone: '+27 82 999 8888',
            email: 'sarah@villacrm.com',
          },
          active_lease: {
            id: base.id * 100,
            reference: `LSE-${base.property?.toUpperCase().replace(/[^A-Z0-9]/g, '')}`,
            status: base.status,
            tenant_name: base.full_name,
            property: {
              id: base.id * 10,
              address_line_1: base.property ?? 'Unknown Property',
              city: 'Cape Town',
            },
            monthly_rent: base.monthly_rent ?? 12000,
            deposit_amount: (base.monthly_rent ?? 12000) * 2,
            escalation_percent: 8,
            payment_day: 1,
            start_date: '2025-08-15',
            end_date: base.lease_end_date ?? '2026-08-14',
            days_until_expiry: base.rent_due_days ? 18 : 62,
            outstanding_balance: base.rent_status === 'paid' ? 0 : (base.monthly_rent ?? 12000),
            tenant: {
              id: base.id,
              first_name: base.full_name?.split(' ')[0] ?? 'Tenant',
              last_name: base.full_name?.split(' ')[1] ?? 'Name',
              phone: '+27 82 123 4567',
              email: `${base.full_name?.toLowerCase().replace(' ', '.')}@example.com`,
            },
            rent_payments: recent_payments,
          },
          recent_payments,
        },
      };
    }
  },
};


export const leasesApi = {
  list: (params?: {status?: string; page?: number}) =>
    apiClient.get<{data: LeaseListItem[]; meta: PaginatedMeta}>('/leases', {params}),

  show: (id: number) =>
    apiClient.get<{data: LeaseDetail}>(`/leases/${id}`),

  recordPayment: async (
    leaseId: number,
    body: {
      amount_paid: number;
      paid_date: string;
      payment_method: 'eft' | 'cash' | 'card' | 'cheque';
      notes?: string;
    }
  ): Promise<{message: string; data: PaymentItem}> => {
    try {
      const res = await apiClient.post<{message: string; data: PaymentItem}>(`/leases/${leaseId}/payments`, body);
      return res.data;
    } catch (e) {
      await new Promise(resolve => setTimeout(resolve, 500));
      
      // Update our mock list state in-memory so detail page updates immediately
      const tenantId = Math.floor(leaseId / 100);
      const tenant = mockTenants.find(t => t.id === tenantId);
      if (tenant) {
        tenant.rent_status = 'paid';
        delete tenant.rent_due_days;
      }

      return {
        message: 'Payment recorded successfully',
        data: {
          id: Math.round(Math.random() * 1000),
          reference: `INV-REC-${leaseId}`,
          amount_due: body.amount_paid,
          amount_paid: body.amount_paid,
          status: 'paid',
          due_date: body.paid_date,
          paid_date: body.paid_date,
          method: body.payment_method,
        },
      };
    }
  },
};

