import {useQuery} from '@tanstack/react-query';
import {contactsApi} from '../api/contacts';
import {cacheService} from '../services/cacheService';
import {Contact, PaginatedResponse} from '../types';

export function useContacts(search?: string, mine = true) {
  return useQuery({
    queryKey: ['contacts', search, mine],
    queryFn: async () => {
      // Return cached list while revalidating in background
      const cached = cacheService.contacts.get<PaginatedResponse<Contact>>();
      if (cached && !search) return cached;

      const {data} = await contactsApi.list({search, mine});
      if (!search) cacheService.contacts.set(data);
      return data;
    },
    staleTime: 2 * 60 * 1000,
    placeholderData: (prev) => prev,
  });
}

export function useContact(contactId: number) {
  return useQuery({
    queryKey: ['contact', contactId],
    queryFn: async () => {
      const cached = cacheService.contact.get<{contact: Contact; recent_calls: any[]}>(contactId);
      if (cached) return cached;

      const {data} = await contactsApi.get(contactId);
      cacheService.contact.set(contactId, data);
      return data;
    },
    staleTime: 2 * 60 * 1000,
  });
}
