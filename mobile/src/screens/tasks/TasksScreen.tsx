import React from 'react';
import {ActivityIndicator, FlatList, Pressable, Text, View, SafeAreaView} from 'react-native';
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
    <View className={`flex-row items-center bg-white shadow-sm border border-slate-100 rounded-3xl mx-5 mb-3 p-4 ${
      task.status === 'completed' ? 'opacity-50' : ''
    }`}>
      {/* Dynamic left edge indicator */}
      <View className={`absolute left-0 top-0 bottom-0 w-1.5 rounded-l-3xl ${
        task.status === 'completed' ? 'bg-slate-300' : (overdue ? 'bg-red-500' : 'bg-brand-500')
      }`} />

      <Pressable
        className={`w-7 h-7 rounded-full border-2 mr-4 ml-1 items-center justify-center transition-colors ${
          task.status === 'completed' ? 'bg-brand-500 border-brand-500' : 'bg-slate-50 border-slate-300'
        }`}
        onPress={onComplete}>
        {task.status === 'completed' && <Text className="text-white text-sm font-bold">✓</Text>}
      </Pressable>

      <View className="flex-1">
        <Text
          className={`text-base font-bold ${
            task.status === 'completed' ? 'line-through text-slate-400' : 'text-slate-800'
          }`}
          numberOfLines={2}>
          {task.title}
        </Text>
        {task.contact && (
          <Text className="text-slate-500 text-xs font-medium mt-1">
            {task.contact.first_name} {task.contact.last_name}
          </Text>
        )}
      </View>

      <View className="items-end ml-3">
        {task.due_at && (
          <View className={`px-2 py-1 rounded-md ${overdue ? 'bg-red-50' : 'bg-slate-50'}`}>
            <Text className={`text-xs font-bold ${overdue ? 'text-red-600' : 'text-slate-500'}`}>
              {isToday(new Date(task.due_at))
                ? format(new Date(task.due_at), 'h:mm a')
                : format(new Date(task.due_at), 'd MMM')}
            </Text>
          </View>
        )}
        {task.source === 'call_summary' && (
          <View className="bg-brand-50 rounded-full px-2 py-0.5 mt-1">
            <Text className="text-[10px] text-brand-600 font-bold uppercase tracking-wider">AI Call</Text>
          </View>
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
      <View className="flex-1 bg-slate-50 items-center justify-center">
        <ActivityIndicator color="#10b981" size="large" />
      </View>
    );
  }

  return (
    <SafeAreaView className="flex-1 bg-slate-50">
      <View className="px-5 pt-6 pb-4 bg-white border-b border-slate-100 shadow-sm z-10 flex-row justify-between items-center">
        <Text className="text-slate-900 text-3xl font-extrabold tracking-tight">Tasks</Text>
        <View className="w-10 h-10 bg-brand-50 rounded-full items-center justify-center">
          <Text className="text-brand-600 font-bold text-lg">{tasks?.filter(t => t.status !== 'completed').length || 0}</Text>
        </View>
      </View>

      <FlatList
        className="flex-1 pt-4"
        contentContainerStyle={{ paddingBottom: 40 }}
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
              <View className="px-6 pt-4 pb-2">
                <Text className={`text-xs font-extrabold uppercase tracking-widest ${
                  item.danger ? 'text-red-500' : 'text-slate-400'
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
          <View className="py-20 px-10 items-center">
            <View className="w-24 h-24 bg-brand-50 rounded-full items-center justify-center mb-6">
              <Text className="text-4xl">☕</Text>
            </View>
            <Text className="text-slate-800 text-xl font-bold mb-2 text-center">All caught up!</Text>
            <Text className="text-slate-500 text-center font-medium">You have completed all your tasks. Take a break or find a new lead.</Text>
          </View>
        }
      />
    </SafeAreaView>
  );
}
