import React from 'react';
import {Pressable, RefreshControl, ScrollView, Text, View, SafeAreaView} from 'react-native';
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
      className={`flex-1 rounded-2xl p-4 items-center bg-white shadow-sm border ${
        danger && count > 0 ? 'border-red-200 bg-red-50' : 'border-slate-100'
      }`}
      onPress={onPress}>
      <View className="bg-slate-50 w-10 h-10 rounded-full items-center justify-center mb-2">
        <Text style={{fontSize: 18}}>{emoji}</Text>
      </View>
      <Text className={`text-2xl font-extrabold ${danger && count > 0 ? 'text-red-600' : 'text-slate-800'}`}>
        {count}
      </Text>
      <Text className="text-slate-500 text-xs font-medium mt-1">{label}</Text>
    </Pressable>
  );
}

function PendingSummaryRow({call, onPress}: {call: Call; onPress: () => void}) {
  const name = call.contact
    ? `${call.contact.first_name} ${call.contact.last_name}`
    : call.remote_number ?? 'Unknown';

  return (
    <Pressable
      className="flex-row items-center bg-white shadow-sm border border-amber-100 rounded-2xl px-4 py-4 mb-3"
      onPress={onPress}>
      <View className="bg-amber-50 w-10 h-10 rounded-full items-center justify-center mr-3">
        <Text className="text-amber-500 text-lg">⏳</Text>
      </View>
      <View className="flex-1">
        <Text className="text-slate-800 text-sm font-bold" numberOfLines={1}>
          Review summary — {name}
        </Text>
        <Text className="text-slate-500 text-xs mt-1 font-medium">
          {call.started_at ? format(new Date(call.started_at), 'h:mm a') : 'Today'}
          {' · '}
          {call.duration_formatted ?? '—'}
        </Text>
      </View>
      <View className="bg-brand-50 px-3 py-1.5 rounded-full">
        <Text className="text-brand-600 text-xs font-bold">Review</Text>
      </View>
    </Pressable>
  );
}

function TaskRow({task}: {task: Task}) {
  const isOverdue = task.due_at && new Date(task.due_at) < new Date();
  return (
    <View className="flex-row items-center bg-white shadow-sm border border-slate-100 rounded-2xl px-4 py-4 mb-3 overflow-hidden">
      <View className={`absolute left-0 top-0 bottom-0 w-1 ${isOverdue ? 'bg-red-500' : 'bg-brand-500'}`} />
      
      <View className={`w-3 h-3 rounded-full mr-3 ml-1 ${isOverdue ? 'bg-red-100 border border-red-500' : 'bg-brand-100 border border-brand-500'}`} />
      
      <View className="flex-1">
        <Text className="text-slate-800 text-sm font-bold" numberOfLines={1}>{task.title}</Text>
        {task.contact && (
          <Text className="text-slate-500 text-xs mt-1 font-medium">
            {task.contact.first_name} {task.contact.last_name}
          </Text>
        )}
      </View>
      {task.due_at && (
        <Text className={`text-xs font-semibold ${isOverdue ? 'text-red-500' : 'text-slate-400'}`}>
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
    <SafeAreaView className="flex-1 bg-slate-50">
      <ScrollView
        className="flex-1"
        contentContainerClassName="px-5 pt-8 pb-10"
        refreshControl={
          <RefreshControl refreshing={tasksLoading} onRefresh={refetchAll} tintColor="#10b981" />
        }>

        {/* Greeting Header */}
        <View className="mb-8 flex-row items-center justify-between">
          <View>
            <Text className="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">
              {format(new Date(), 'EEEE, MMMM d')}
            </Text>
            <Text className="text-slate-900 text-3xl font-extrabold tracking-tight">
              {greeting()},{'\n'}{user?.first_name} <Text className="text-3xl">👋</Text>
            </Text>
          </View>
          {/* Avatar Placeholder */}
          <View className="w-14 h-14 bg-brand-100 rounded-full items-center justify-center border-2 border-white shadow-sm">
            <Text className="text-brand-600 font-bold text-lg">
              {user?.first_name?.[0] || 'V'}
            </Text>
          </View>
        </View>

        {/* Stat chips row */}
        <View className="flex-row gap-3 mb-8">
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
          <View className="bg-brand-600 rounded-3xl p-6 mb-8 shadow-lg shadow-brand-500/30">
            <View className="flex-row items-center mb-3">
              <View className="bg-brand-500 rounded-full px-3 py-1 mr-3">
                <Text className="text-white text-xs font-bold tracking-wider">AI BRIEF</Text>
              </View>
              <Text className="text-brand-100 text-xs font-medium uppercase tracking-widest">
                Daily Insights
              </Text>
            </View>
            
            <Text className="text-white text-base leading-6 font-medium mb-4">
              {brief.content}
            </Text>

            {/* Priority actions */}
            {(brief.priority_actions ?? []).length > 0 && (
              <View className="bg-brand-700/50 rounded-2xl p-4 gap-3">
                <Text className="text-brand-200 text-xs font-bold uppercase tracking-wider mb-1">Suggested Actions</Text>
                {brief.priority_actions.slice(0, 3).map((action, i) => (
                  <View key={i} className="flex-row items-center">
                    <View className="w-6 h-6 rounded-full bg-brand-500 items-center justify-center mr-3">
                      <Text className="text-white text-xs font-bold">→</Text>
                    </View>
                    <Text className="text-white text-sm font-medium flex-1">
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
          <View className="mb-8">
            <Text className="text-slate-800 text-lg font-extrabold tracking-tight mb-4">
              Awaiting Review <Text className="text-amber-500 text-sm">({pendingSummaries.length})</Text>
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
          <View className="mb-6">
            <Text className="text-slate-800 text-lg font-extrabold tracking-tight mb-4">
              Overdue <Text className="text-red-500 text-sm">({overdueTasks.length})</Text>
            </Text>
            {overdueTasks.map(t => <TaskRow key={t.id} task={t} />)}
          </View>
        )}

        {/* Today's tasks */}
        <View className="mb-6">
          <Text className="text-slate-800 text-lg font-extrabold tracking-tight mb-4">
            Today <Text className="text-slate-400 text-sm">({todayTasks.length})</Text>
          </Text>
          
          {todayTasks.length === 0 && overdueTasks.length === 0 ? (
            <View className="bg-white border border-slate-100 shadow-sm rounded-3xl p-8 items-center justify-center">
              <Text className="text-4xl mb-3">🎉</Text>
              <Text className="text-slate-800 font-bold text-lg mb-1">All caught up!</Text>
              <Text className="text-slate-500 text-sm text-center">You have no tasks remaining for today.</Text>
            </View>
          ) : (
            todayTasks.map(t => <TaskRow key={t.id} task={t} />)
          )}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}
