import React from 'react';
import {ActivityIndicator, FlatList, Pressable, Text, View} from 'react-native';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {tasksApi} from '../../api/tasks';
import {Task} from '../../types';
import {format, isToday, isPast} from 'date-fns';

function TaskItem({task, onComplete, onSnooze}: {
  task: Task;
  onComplete: () => void;
  onSnooze: () => void;
}) {
  const overdue = task.due_at && isPast(new Date(task.due_at)) && task.status !== 'completed';

  return (
    <View className={`flex-row items-center px-4 py-3.5 border-b border-slate-800 ${
      task.status === 'completed' ? 'opacity-40' : ''
    }`}>
      <Pressable
        className={`w-6 h-6 rounded-full border-2 mr-3 items-center justify-center ${
          task.status === 'completed' ? 'bg-green-500 border-green-500' : 'border-slate-500'
        }`}
        onPress={onComplete}>
        {task.status === 'completed' && <Text className="text-white text-xs">✓</Text>}
      </Pressable>

      <View className="flex-1">
        <Text
          className={`text-sm font-medium ${
            task.status === 'completed' ? 'line-through text-slate-500' : 'text-white'
          }`}
          numberOfLines={2}>
          {task.title}
        </Text>
        {task.contact && (
          <Text className="text-slate-500 text-xs mt-0.5">
            {task.contact.first_name} {task.contact.last_name}
          </Text>
        )}
      </View>

      <View className="items-end ml-3">
        {task.due_at && (
          <Text className={`text-xs ${overdue ? 'text-red-400' : 'text-slate-400'}`}>
            {isToday(new Date(task.due_at))
              ? format(new Date(task.due_at), 'h:mm a')
              : format(new Date(task.due_at), 'd MMM')}
          </Text>
        )}
        {task.source === 'call_summary' && (
          <Text className="text-xs text-brand-400 mt-0.5">📞</Text>
        )}
      </View>
    </View>
  );
}

export function TasksScreen() {
  const queryClient = useQueryClient();

  const {data: tasks, isLoading, refetch} = useQuery({
    queryKey: ['tasks', 'today'],
    queryFn: () => tasksApi.list().then(r => r.data),
  });

  const updateTask = useMutation({
    mutationFn: ({id, status, due_at}: {id: number; status?: string; due_at?: string}) =>
      tasksApi.update(id, {status, due_at}),
    onSuccess: () => queryClient.invalidateQueries({queryKey: ['tasks']}),
  });

  const overdue = tasks?.filter(
    t => t.status !== 'completed' && t.due_at && isPast(new Date(t.due_at)),
  ) ?? [];
  const today = tasks?.filter(
    t => t.status !== 'completed' && t.due_at && isToday(new Date(t.due_at)),
  ) ?? [];
  const upcoming = tasks?.filter(
    t => t.status !== 'completed' && (!t.due_at || !isPast(new Date(t.due_at)) && !isToday(new Date(t.due_at))),
  ) ?? [];

  const sections = [
    ...(overdue.length > 0 ? [{title: `Overdue · ${overdue.length}`, data: overdue, danger: true}] : []),
    ...(today.length > 0 ? [{title: `Today · ${today.length}`, data: today, danger: false}] : []),
    ...(upcoming.length > 0 ? [{title: 'Upcoming', data: upcoming, danger: false}] : []),
  ];

  if (isLoading) {
    return (
      <View className="flex-1 bg-surface items-center justify-center">
        <ActivityIndicator color="#3b82f6" />
      </View>
    );
  }

  return (
    <View className="flex-1 bg-surface">
      <View className="pt-14 px-4 pb-3">
        <Text className="text-white text-2xl font-bold">Tasks</Text>
      </View>

      <FlatList
        data={sections.flatMap(s => [
          {type: 'header' as const, title: s.title, danger: s.danger, id: s.title},
          ...s.data.map(t => ({type: 'item' as const, task: t, id: String(t.id)})),
        ])}
        keyExtractor={item => item.id}
        onRefresh={refetch}
        refreshing={isLoading}
        renderItem={({item}) => {
          if (item.type === 'header') {
            return (
              <View className="px-4 pt-4 pb-1">
                <Text className={`text-xs font-semibold uppercase tracking-wide ${
                  item.danger ? 'text-red-400' : 'text-slate-400'
                }`}>
                  {item.title}
                </Text>
              </View>
            );
          }
          return (
            <TaskItem
              task={item.task}
              onComplete={() => updateTask.mutate({id: item.task.id, status: 'completed'})}
              onSnooze={() => {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                tomorrow.setHours(9, 0, 0, 0);
                updateTask.mutate({id: item.task.id, due_at: tomorrow.toISOString()});
              }}
            />
          );
        }}
        ListEmptyComponent={
          <View className="py-16 items-center">
            <Text className="text-slate-500">No tasks today — you're all caught up!</Text>
          </View>
        }
      />
    </View>
  );
}
