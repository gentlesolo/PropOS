import React from 'react';
import {FlatList, Pressable, RefreshControl, ScrollView, Text, View} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {apiClient} from '../../api/client';
import {tasksApi} from '../../api/tasks';
import {useAuthStore} from '../../store/authStore';
import {Task} from '../../types';
import {format} from 'date-fns';

function greeting(): string {
  const h = new Date().getHours();
  if (h < 12) return 'Good morning';
  if (h < 17) return 'Good afternoon';
  return 'Good evening';
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

  const {data: tasks, isLoading: tasksLoading, refetch} = useQuery({
    queryKey: ['tasks', 'today'],
    queryFn: () => tasksApi.list().then(r => r.data),
  });

  const {data: brief} = useQuery({
    queryKey: ['brief'],
    queryFn: () => apiClient.get<{content: string}>('/brief').then(r => r.data),
    staleTime: 1000 * 60 * 30,
  });

  const overdueTasks = tasks?.filter(
    t => t.status !== 'completed' && t.due_at && new Date(t.due_at) < new Date(),
  ) ?? [];
  const todayTasks = tasks?.filter(
    t => t.status !== 'completed' && (!t.due_at || new Date(t.due_at) >= new Date()),
  ) ?? [];

  return (
    <ScrollView
      className="flex-1 bg-surface"
      contentContainerClassName="px-4 pt-14 pb-8"
      refreshControl={<RefreshControl refreshing={tasksLoading} onRefresh={refetch} />}>

      {/* Header */}
      <View className="mb-6">
        <Text className="text-slate-400 text-sm">{format(new Date(), 'EEEE, MMMM d')}</Text>
        <Text className="text-white text-2xl font-bold mt-1">
          {greeting()}, {user?.first_name} 👋
        </Text>
      </View>

      {/* AI Brief */}
      {brief?.content && (
        <View className="bg-brand-900 border border-brand-700 rounded-xl p-4 mb-6">
          <Text className="text-brand-100 text-xs font-semibold uppercase tracking-wide mb-2">
            AI Daily Brief
          </Text>
          <Text className="text-slate-200 text-sm leading-5">{brief.content}</Text>
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
        {todayTasks.length === 0 ? (
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
