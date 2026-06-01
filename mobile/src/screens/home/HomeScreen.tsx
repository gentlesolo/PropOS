import React from 'react';
import {Pressable, RefreshControl, ScrollView, Text, View} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import {tasksApi} from '../../api/tasks';
import {viewingsApi} from '../../api/viewings';
import {callsApi} from '../../api/calls';
import {messagingApi} from '../../api/messaging';
import {briefApi} from '../../api/brief';
import {useAuthStore} from '../../store/authStore';
import {useNotificationStore} from '../../store/notificationStore';
import {Task, Call} from '../../types';
import {format, isToday} from 'date-fns';

function greeting(): string {
  const h = new Date().getHours();
  if (h < 12) return 'Good morning';
  if (h < 17) return 'Good afternoon';
  return 'Good evening';
}

function StatChip({
  emoji,
  count,
  label,
  danger,
  onPress,
}: {
  emoji: string;
  count: number;
  label: string;
  danger?: boolean;
  onPress?: () => void;
}) {
  return (
    <Pressable
      className={`flex-1 rounded-xl p-3 items-center ${
        danger && count > 0 ? 'bg-red-950/60 border border-red-800' : 'bg-surface-card'
      }`}
      onPress={onPress}>
      <Text style={{fontSize: 20}}>{emoji}</Text>
      <Text className={`text-lg font-bold mt-1 ${danger && count > 0 ? 'text-red-400' : 'text-white'}`}>
        {count}
      </Text>
      <Text className="text-slate-500 text-xs text-center">{label}</Text>
    </Pressable>
  );
}

function PendingSummaryRow({call, onPress}: {call: Call; onPress: () => void}) {
  const name = call.contact
    ? `${call.contact.first_name} ${call.contact.last_name}`
    : call.remote_number ?? 'Unknown';

  return (
    <Pressable
      className="flex-row items-center bg-amber-950/40 border border-amber-800/60 rounded-xl px-4 py-3 mb-2"
      onPress={onPress}>
      <Text className="text-amber-400 mr-3 text-base">⏳</Text>
      <View className="flex-1">
        <Text className="text-white text-sm font-medium" numberOfLines={1}>
          Review summary — {name}
        </Text>
        <Text className="text-slate-400 text-xs mt-0.5">
          {call.started_at ? format(new Date(call.started_at), 'h:mm a') : 'Today'}
          {' · '}
          {call.duration_formatted ?? '—'}
        </Text>
      </View>
      <Text className="text-brand-500 text-xs">Review →</Text>
    </Pressable>
  );
}

function TaskRow({task}: {task: Task}) {
  const isOverdue = task.due_at && new Date(task.due_at) < new Date();
  return (
    <View className="flex-row items-center bg-surface-card rounded-xl px-4 py-3 mb-2">
      <View className={`w-2 h-2 rounded-full mr-3 ${isOverdue ? 'bg-red-500' : 'bg-brand-500'}`} />
      <View className="flex-1">
        <Text className="text-white text-sm font-medium" numberOfLines={1}>{task.title}</Text>
        {task.contact && (
          <Text className="text-slate-400 text-xs mt-0.5">
            {task.contact.first_name} {task.contact.last_name}
          </Text>
        )}
      </View>
      {task.due_at && (
        <Text className={`text-xs ${isOverdue ? 'text-red-400' : 'text-slate-400'}`}>
          {format(new Date(task.due_at), 'h:mm a')}
        </Text>
      )}
    </View>
  );
}

export function HomeScreen() {
  const {user} = useAuthStore();
  const {unreadCount} = useNotificationStore();
  const navigation = useNavigation<any>();

  const {data: tasks, isLoading: tasksLoading, refetch: refetchTasks} = useQuery({
    queryKey: ['tasks', 'today'],
    queryFn: () => tasksApi.list().then(r => r.data),
  });

  const {data: viewings, refetch: refetchViewings} = useQuery({
    queryKey: ['viewings', 'today'],
    queryFn: () => viewingsApi.today().then(r => r.data),
  });

  const {data: inbox, refetch: refetchInbox} = useQuery({
    queryKey: ['inbox'],
    queryFn: () => messagingApi.inbox().then(r => r.data),
    staleTime: 60_000,
  });

  const {data: pendingCalls, refetch: refetchCalls} = useQuery({
    queryKey: ['calls', 'pending-review'],
    queryFn: () =>
      callsApi
        .list({direction: 'outbound'})
        .then(r =>
          r.data.data.filter(
            c =>
              c.status === 'completed' &&
              c.summary &&
              !c.summary.agent_confirmed_at &&
              c.started_at &&
              isToday(new Date(c.started_at)),
          ),
        ),
    staleTime: 60_000,
  });

  const {data: brief, refetch: refetchBrief} = useQuery({
    queryKey: ['brief'],
    queryFn: () => briefApi.get().then(r => r.data),
    staleTime: 30 * 60_000,
  });

  const refetchAll = () => {
    refetchTasks();
    refetchViewings();
    refetchInbox();
    refetchCalls();
    refetchBrief();
  };

  const overdueTasks = tasks?.filter(
    t => t.status !== 'completed' && t.due_at && new Date(t.due_at) < new Date(),
  ) ?? [];
  const todayTasks = tasks?.filter(
    t => t.status !== 'completed' && (!t.due_at || new Date(t.due_at) >= new Date()),
  ) ?? [];
  const pendingSummaries = pendingCalls ?? [];
  const todayViewingsCount = viewings?.length ?? 0;
  const unreadMessages = inbox?.length ?? 0;

  return (
    <ScrollView
      className="flex-1 bg-surface"
      contentContainerClassName="px-4 pt-14 pb-8"
      refreshControl={
        <RefreshControl refreshing={tasksLoading} onRefresh={refetchAll} />
      }>

      {/* Greeting */}
      <View className="mb-5">
        <Text className="text-slate-400 text-sm">{format(new Date(), 'EEEE, MMMM d')}</Text>
        <Text className="text-white text-2xl font-bold mt-1">
          {greeting()}, {user?.first_name} 👋
        </Text>
      </View>

      {/* Stat chips row */}
      <View className="flex-row gap-2 mb-5">
        <StatChip
          emoji="✅"
          count={overdueTasks.length + todayTasks.length}
          label="Tasks"
          danger={overdueTasks.length > 0}
          onPress={() => navigation.navigate('Tasks')}
        />
        <StatChip
          emoji="🏡"
          count={todayViewingsCount}
          label="Viewings"
          onPress={() => navigation.navigate('Viewings')}
        />
        <StatChip
          emoji="💬"
          count={unreadMessages}
          label="Messages"
          onPress={() => navigation.navigate('Messages')}
        />
      </View>

      {/* AI Daily Brief */}
      {brief?.content && (
        <View className="bg-brand-900 border border-brand-700 rounded-xl p-4 mb-5">
          <Text className="text-brand-100 text-xs font-semibold uppercase tracking-wide mb-2">
            AI Daily Brief
          </Text>
          <Text className="text-slate-200 text-sm leading-5">{brief.content}</Text>

          {/* Priority actions — one-tap links */}
          {(brief.priority_actions ?? []).length > 0 && (
            <View className="mt-3 gap-1.5">
              {brief.priority_actions.slice(0, 3).map((action, i) => (
                <View key={i} className="flex-row items-center gap-2">
                  <Text className="text-brand-500 text-xs">→</Text>
                  <Text className="text-brand-100 text-xs flex-1">
                    {typeof action === 'string' ? action : action.title}
                  </Text>
                </View>
              ))}
            </View>
          )}
        </View>
      )}

      {/* Pending call summaries */}
      {pendingSummaries.length > 0 && (
        <View className="mb-5">
          <Text className="text-amber-400 text-xs font-semibold uppercase tracking-wide mb-2">
            Awaiting Review · {pendingSummaries.length}
          </Text>
          {pendingSummaries.map(call => (
            <PendingSummaryRow
              key={call.id}
              call={call}
              onPress={() => navigation.navigate('Calls', {
                screen: 'PostCallSummary',
                params: {callId: call.id},
              })}
            />
          ))}
        </View>
      )}

      {/* Overdue tasks */}
      {overdueTasks.length > 0 && (
        <View className="mb-4">
          <Text className="text-red-400 text-xs font-semibold uppercase tracking-wide mb-2">
            Overdue · {overdueTasks.length}
          </Text>
          {overdueTasks.map(t => <TaskRow key={t.id} task={t} />)}
        </View>
      )}

      {/* Today's tasks */}
      <View>
        <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide mb-2">
          Today · {todayTasks.length}
        </Text>
        {todayTasks.length === 0 && overdueTasks.length === 0 ? (
          <View className="bg-surface-card rounded-xl p-6 items-center">
            <Text className="text-slate-500 text-sm">All caught up for today!</Text>
          </View>
        ) : (
          todayTasks.map(t => <TaskRow key={t.id} task={t} />)
        )}
      </View>
    </ScrollView>
  );
}
