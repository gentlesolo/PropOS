import {useEffect} from 'react';
import {useQueryClient} from '@tanstack/react-query';
import {notificationService} from '../services/notificationService';
import {useNotificationStore} from '../store/notificationStore';

/**
 * Subscribes to foreground push notifications and invalidates
 * the relevant React Query caches so screens refresh automatically.
 * Mount this once near the root of the authenticated app.
 */
export function useRealtime() {
  const queryClient = useQueryClient();
  const {increment} = useNotificationStore();

  useEffect(() => {
    const unsub = notificationService.onForegroundMessage((data) => {
      increment();

      switch (data.type) {
        case 'call_summary_ready':
          queryClient.invalidateQueries({queryKey: ['calls']});
          queryClient.invalidateQueries({queryKey: ['call', Number(data.call_id)]});
          break;
        case 'new_lead_assigned':
          queryClient.invalidateQueries({queryKey: ['contacts']});
          break;
        case 'new_message':
          queryClient.invalidateQueries({queryKey: ['inbox']});
          queryClient.invalidateQueries({queryKey: ['thread', Number(data.contact_id)]});
          break;
        case 'task_due':
          queryClient.invalidateQueries({queryKey: ['tasks']});
          break;
        case 'viewing_reminder':
          queryClient.invalidateQueries({queryKey: ['viewings']});
          break;
      }
    });

    return unsub;
  }, [queryClient, increment]);
}
