import React, {useState, useRef, useEffect, useMemo} from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  Text,
  TextInput,
  View,
  SafeAreaView,
  Modal,
  Alert,
  Animated,
  PanResponder,
  useColorScheme,
  ScrollView,
} from 'react-native';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {tasksApi} from '../../api/tasks';
import {contactsApi} from '../../api/contacts';
import {Task} from '../../types';
import {format, isToday, isPast, isTomorrow, addDays, addHours, parseISO} from 'date-fns';
import Icon from 'react-native-vector-icons/Feather';
import {useNavigation} from '@react-navigation/native';

type SegmentType = 'My Day' | 'Upcoming' | 'All';

// Pulsing Overdue Header Component
function PulsingOverdueHeader({title}: {title: string}) {
  const pulseAnim = useRef(new Animated.Value(0.4)).current;

  useEffect(() => {
    Animated.loop(
      Animated.sequence([
        Animated.timing(pulseAnim, {toValue: 1, duration: 800, useNativeDriver: true}),
        Animated.timing(pulseAnim, {toValue: 0.4, duration: 800, useNativeDriver: true}),
      ])
    ).start();
  }, []);

  return (
    <View className="px-4 py-3 mt-4 flex-row items-center border-l-[4px] border-l-danger bg-danger/5">
      <Animated.View style={{opacity: pulseAnim}} className="w-1.5 h-1.5 rounded-full bg-danger mr-2" />
      <Text className="text-danger text-xs font-black uppercase tracking-wider">
        {title}
      </Text>
    </View>
  );
}

// Custom Task Row with Pan Gestures and Checkbox Animation
function TaskRowItem({
  task,
  onComplete,
  onSnooze,
  onTap,
  isDark,
}: {
  task: Task;
  onComplete: () => void;
  onSnooze: () => void;
  onTap: () => void;
  isDark: boolean;
}) {
  const overdue = task.due_at && isPast(new Date(task.due_at)) && task.status !== 'completed';
  const isCompleted = task.status === 'completed';

  const [completing, setCompleting] = useState(false);
  const checkScale = useRef(new Animated.Value(0)).current;
  const rowOpacity = useRef(new Animated.Value(isCompleted ? 0.5 : 1)).current;
  const translateX = useRef(new Animated.Value(0)).current;
  const currentTranslation = useRef(0);

  // Checkbox complete animation
  const handleCheckboxPress = () => {
    if (isCompleted || completing) return;
    setCompleting(true);

    Animated.timing(checkScale, {
      toValue: 1,
      duration: 150,
      useNativeDriver: true,
    }).start(() => {
      setTimeout(() => {
        Animated.timing(rowOpacity, {
          toValue: 0.5,
          duration: 300,
          useNativeDriver: true,
        }).start(() => {
          onComplete();
        });
      }, 300);
    });
  };

  const snap = (toValue: number) => {
    Animated.spring(translateX, {
      toValue,
      useNativeDriver: true,
      bounciness: 4,
      speed: 12,
    }).start(() => {
      currentTranslation.current = toValue;
    });
  };

  const panResponder = useRef(
    PanResponder.create({
      onStartShouldSetPanResponder: () => false,
      onMoveShouldSetPanResponder: (_, gestureState) => {
        const {dx, dy} = gestureState;
        return Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 10;
      },
      onPanResponderMove: (_, gestureState) => {
        let newX = currentTranslation.current + gestureState.dx;
        // Swipe left reveals snooze, Swipe right reveals complete
        if (newX > 160) newX = 160;
        if (newX < -100) newX = -100;
        translateX.setValue(newX);
      },
      onPanResponderRelease: (_, gestureState) => {
        if (gestureState.dx > 125) {
          // Swipe Right complete gesture
          Animated.timing(translateX, {
            toValue: 400,
            duration: 200,
            useNativeDriver: true,
          }).start(() => {
            handleCheckboxPress();
          });
        } else if (gestureState.dx < -55) {
          // Swipe Left snooze gesture
          snap(-80);
        } else {
          snap(0);
        }
      },
      onPanResponderTerminate: () => {
        snap(0);
      },
    })
  ).current;

  const priorityColor = {
    low: 'bg-emerald-500',
    medium: 'bg-amber-500',
    high: 'bg-red-500',
    urgent: 'bg-purple-600',
  }[task.priority || 'medium'];

  const bgCard = isDark ? 'bg-[#090d16]' : 'bg-white';
  const borderCard = isDark ? 'border-zinc-800/80' : 'border-slate-200/50';
  const textPrimary = isDark ? 'text-text-primary' : 'text-slate-900';
  const textSecondary = isDark ? 'text-text-secondary' : 'text-slate-500';

  return (
    <View className="relative overflow-hidden mb-3 mx-4 rounded-2xl bg-[#090d16] border border-slate-800/60">
      {/* Swipe Right Background (Complete) */}
      <View className="absolute left-0 top-0 bottom-0 right-0 bg-emerald-600 flex-row items-center pl-5 z-0 rounded-2xl">
        <Icon name="check" size={20} color="#ffffff" />
        <Text className="text-white text-xs font-black ml-2">Complete</Text>
      </View>

      {/* Swipe Left Background (Snooze) */}
      <View className="absolute right-0 top-0 bottom-0 w-[80px] bg-amber-500 flex-row items-center justify-center z-0 rounded-2xl">
        <Pressable
          onPress={() => {
            snap(0);
            onSnooze();
          }}
          className="w-full h-full items-center justify-center active:bg-amber-600"
        >
          <Icon name="clock" size={18} color="#ffffff" />
          <Text className="text-white text-[10px] font-black mt-1">Snooze</Text>
        </Pressable>
      </View>

      {/* Content Body */}
      <Animated.View
        style={{
          transform: [{translateX}],
          opacity: rowOpacity,
        }}
        {...panResponder.panHandlers}
        className={`${bgCard} ${borderCard} border rounded-2xl p-4 flex-row items-center z-10 w-full`}
      >
        {/* Checkbox */}
        <Pressable
          onPress={handleCheckboxPress}
          className={`w-6 h-6 rounded-full border-2 items-center justify-center mr-3.5 ${
            isCompleted || completing
              ? 'bg-brand-500 border-brand-500'
              : isDark
              ? 'border-zinc-700 bg-transparent'
              : 'border-slate-350 bg-transparent'
          }`}
        >
          {(isCompleted || completing) && (
            <Animated.View style={{transform: [{scale: isCompleted ? 1 : checkScale}]}}>
              <Icon name="check" size={11} color="#ffffff" />
            </Animated.View>
          )}
        </Pressable>

        {/* Title / Details */}
        <Pressable onPress={onTap} className="flex-1">
          <View className="flex-row items-center flex-wrap gap-1.5 mb-1">
            <Text
              className={`text-sm leading-5 font-bold ${
                isCompleted
                  ? 'line-through text-slate-500/70'
                  : overdue
                  ? 'text-danger'
                  : textPrimary
              }`}
              numberOfLines={2}
            >
              {task.title}
            </Text>
            {overdue && (
              <View className="bg-danger/10 px-1.5 py-0.5 rounded">
                <Text className="text-danger text-[9px] font-black uppercase">Overdue</Text>
              </View>
            )}
          </View>

          {/* Secondary info */}
          <View className="flex-row items-center gap-2">
            {task.due_at && (
              <Text className={`text-[10px] font-mono font-bold ${overdue ? 'text-danger' : textSecondary}`}>
                ⏰ {format(new Date(task.due_at), 'd MMM, h:mm a')}
              </Text>
            )}
            {task.contact && (
              <Text className={`text-[10px] font-bold ${textSecondary}`} numberOfLines={1}>
                👤 {task.contact.first_name} {task.contact.last_name}
              </Text>
            )}
            {task.source === 'call_summary' && (
              <Icon name="phone" size={10} color={isDark ? '#F59E0B' : '#D97706'} />
            )}
          </View>
        </Pressable>

        {/* Priority dot */}
        <View className={`w-2 h-2 rounded-full ml-2.5 ${priorityColor}`} />
      </Animated.View>
    </View>
  );
}

export function TasksScreen() {
  const queryClient = useQueryClient();
  const colorScheme = useColorScheme();
  const isDark = colorScheme !== 'light';
  const navigation = useNavigation();

  // Active filters and views
  const [activeSegment, setActiveSegment] = useState<SegmentType>('My Day');
  const [showCompleted, setShowCompleted] = useState(false);

  // Selected details sheet task
  const [selectedTask, setSelectedTask] = useState<Task | null>(null);
  const [taskDetailVisible, setTaskDetailVisible] = useState(false);
  const [editTitle, setEditTitle] = useState('');

  // Quick Add Sheet states
  const [quickAddVisible, setQuickAddVisible] = useState(false);
  const [quickText, setQuickText] = useState('');

  // Snooze options modal
  const [snoozeTask, setSnoozeTask] = useState<Task | null>(null);
  const [snoozeVisible, setSnoozeVisible] = useState(false);

  // Fetch tasks
  const {data: tasks, isLoading, refetch} = useQuery({
    queryKey: ['tasks'],
    queryFn: () => tasksApi.list().then((r) => r.data),
  });

  // Fetch contacts for NLP smart parsing match
  const {data: contacts} = useQuery({
    queryKey: ['contacts'],
    queryFn: () => contactsApi.list().then((r) => r.data),
  });

  // Task Mutations
  const updateTask = useMutation({
    mutationFn: ({id, status, due_at, title}: {id: number; status?: string; due_at?: string; title?: string}) =>
      tasksApi.update(id, {status, due_at, title}),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['tasks']});
      if (selectedTask) {
        // Refresh detail view state
        const updated = tasks?.find((t) => t.id === selectedTask.id);
        if (updated) setSelectedTask(updated);
      }
    },
  });

  const createTask = useMutation({
    mutationFn: (payload: {title: string; contact_id?: number; due_at?: string}) =>
      tasksApi.store(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['tasks']});
      setQuickText('');
      setQuickAddVisible(false);
    },
  });

  const deleteTask = useMutation({
    mutationFn: (id: number) => tasksApi.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['tasks']});
      setTaskDetailVisible(false);
      setSelectedTask(null);
    },
  });

  // Overdue count for header badge
  const overdueCount = useMemo(() => {
    return tasks?.filter(
      (t) => t.status !== 'completed' && t.due_at && isPast(new Date(t.due_at))
    ).length || 0;
  }, [tasks]);

  // Smart Entity Parsing for Inline Highlights
  const parsedNLP = useMemo(() => {
    if (!quickText) return {words: [], contact: null, date: null};
    const words = quickText.split(' ');
    const contactsList = contacts?.data ?? [];
    
    // Match Contact
    const matchedContact = contactsList.find((c) =>
      words.some(
        (w) =>
          w.toLowerCase().replace(/[^a-zA-Z]/g, '') === c.first_name.toLowerCase() ||
          w.toLowerCase().replace(/[^a-zA-Z]/g, '') === c.last_name.toLowerCase()
      )
    );

    // Match Date
    let matchedDate: string | null = null;
    const lowerText = quickText.toLowerCase();
    if (lowerText.includes('tomorrow')) matchedDate = 'Tomorrow';
    else if (lowerText.includes('today')) matchedDate = 'Today';
    else if (lowerText.includes('next week')) matchedDate = 'Next Week';
    else if (lowerText.includes('pm') || lowerText.includes('am')) matchedDate = 'Time Specified';

    return {words, contact: matchedContact || null, date: matchedDate};
  }, [quickText, contacts]);

  // Generate sections based on active Segment SegmentType
  const sectionsList = useMemo(() => {
    const list = tasks ?? [];
    const overdueList = list.filter(
      (t) => t.status !== 'completed' && t.due_at && isPast(new Date(t.due_at))
    );

    if (activeSegment === 'My Day') {
      const todayList = list.filter(
        (t) => t.status !== 'completed' && t.due_at && isToday(new Date(t.due_at))
      );
      const nodateList = list.filter(
        (t) => t.status !== 'completed' && !t.due_at
      );

      return [
        ...(overdueList.length > 0 ? [{title: 'Overdue', data: overdueList, type: 'overdue' as const}] : []),
        {title: 'Today', data: [...todayList, ...nodateList], type: 'today' as const},
      ];
    }

    if (activeSegment === 'Upcoming') {
      const tomorrowList = list.filter(
        (t) => t.status !== 'completed' && t.due_at && isTomorrow(new Date(t.due_at))
      );
      const thisWeekList = list.filter((t) => {
        if (t.status === 'completed' || !t.due_at) return false;
        const d = new Date(t.due_at);
        const limit = addDays(new Date(), 7);
        return d > addDays(new Date(), 1) && d <= limit;
      });
      const laterList = list.filter((t) => {
        if (t.status === 'completed' || !t.due_at) return false;
        return new Date(t.due_at) > addDays(new Date(), 7);
      });

      return [
        ...(overdueList.length > 0 ? [{title: 'Overdue', data: overdueList, type: 'overdue' as const}] : []),
        {title: 'Tomorrow', data: tomorrowList, type: 'tomorrow' as const},
        {title: 'This Week', data: thisWeekList, type: 'week' as const},
        {title: 'Later', data: laterList, type: 'later' as const},
      ];
    }

    // "All" segment
    const pendingList = list.filter((t) => t.status !== 'completed');
    const completedList = list.filter((t) => t.status === 'completed');

    return [
      ...(overdueList.length > 0 ? [{title: 'Overdue', data: overdueList, type: 'overdue' as const}] : []),
      {title: 'Pending Tasks', data: pendingList, type: 'pending' as const},
      ...(showCompleted && completedList.length > 0
        ? [{title: 'Completed Tasks', data: completedList, type: 'completed' as const}]
        : []),
    ];
  }, [tasks, activeSegment, showCompleted]);

  // Handle Quick Add Action
  const handleQuickAdd = () => {
    if (!quickText.trim()) return;

    let dueTime: Date | undefined;
    const lower = quickText.toLowerCase();

    if (lower.includes('tomorrow')) {
      dueTime = addDays(new Date(), 1);
      dueTime.setHours(9, 0, 0, 0);
    } else if (lower.includes('next week')) {
      dueTime = addDays(new Date(), 7);
      dueTime.setHours(9, 0, 0, 0);
    } else {
      dueTime = new Date();
      dueTime.setHours(18, 0, 0, 0); // End of today default
    }

    createTask.mutate({
      title: quickText.trim(),
      contact_id: parsedNLP.contact?.id,
      due_at: dueTime.toISOString(),
    });
  };

  // Snooze action
  const handleSnoozeCommit = (option: '1h' | 'tomorrow' | 'week') => {
    if (!snoozeTask) return;

    let targetTime = new Date();
    if (option === '1h') {
      targetTime = addHours(new Date(), 1);
    } else if (option === 'tomorrow') {
      targetTime = addDays(new Date(), 1);
      targetTime.setHours(9, 0, 0, 0);
    } else if (option === 'week') {
      targetTime = addDays(new Date(), 7);
      targetTime.setHours(9, 0, 0, 0);
    }

    updateTask.mutate({id: snoozeTask.id, due_at: targetTime.toISOString()});
    setSnoozeVisible(false);
    setSnoozeTask(null);
  };

  // Inline Title Save
  const handleSaveTitle = () => {
    if (selectedTask && editTitle.trim() && editTitle !== selectedTask.title) {
      updateTask.mutate({id: selectedTask.id, title: editTitle.trim()});
    }
  };

  // Styling selectors
  const bgScreen = isDark ? 'bg-[#030712]' : 'bg-slate-50';
  const bgCard = isDark ? 'bg-[#090d16]' : 'bg-white';
  const bgInput = isDark ? 'bg-[#111827]' : 'bg-slate-100';
  const borderHeader = isDark ? 'border-zinc-800/85' : 'border-slate-200/60';
  const textPrimary = isDark ? 'text-text-primary' : 'text-slate-900';
  const textSecondary = isDark ? 'text-text-secondary' : 'text-slate-550';

  return (
    <SafeAreaView className={`flex-1 ${bgScreen}`}>
      {/* Header */}
      <View className={`px-4 pt-4 pb-3 ${bgCard} border-b ${borderHeader} z-10 flex-row justify-between items-center`}>
        <View className="flex-row items-center gap-2">
          <Text className={`${textPrimary} text-2xl font-black tracking-tight`}>Tasks</Text>
          {overdueCount > 0 && (
            <View className="bg-danger px-2.5 py-0.5 rounded-full">
              <Text className="text-white text-[10px] font-black">{overdueCount} Overdue</Text>
            </View>
          )}
        </View>

        {activeSegment === 'All' && (
          <Pressable
            onPress={() => setShowCompleted(!showCompleted)}
            className="px-3 py-1.5 rounded-lg border border-brand-500/20 bg-brand-500/5 active:bg-brand-500/10 flex-row items-center gap-1.5"
          >
            <Icon name={showCompleted ? 'eye-off' : 'eye'} size={12} color="#10B981" />
            <Text className="text-brand-500 text-xs font-bold">
              {showCompleted ? 'Hide Completed' : 'Show Completed'}
            </Text>
          </Pressable>
        )}
      </View>

      {/* Segmented Pill Toggle */}
      <View className={`flex-row mx-4 mt-3 mb-2 rounded-xl p-1 ${
        isDark ? 'bg-zinc-900 border border-zinc-850' : 'bg-slate-250/60'
      }`}>
        {(['My Day', 'Upcoming', 'All'] as const).map((seg) => {
          const isActive = activeSegment === seg;
          return (
            <Pressable
              key={seg}
              onPress={() => setActiveSegment(seg)}
              className={`flex-1 py-2 rounded-lg items-center ${
                isActive
                  ? isDark
                    ? 'bg-[#090d16] border border-zinc-800 shadow-sm'
                    : 'bg-white shadow-sm'
                  : 'bg-transparent'
              }`}
            >
              <Text
                className={`text-xs font-black ${
                  isActive ? textPrimary : isDark ? 'text-text-secondary' : 'text-slate-500'
                }`}
              >
                {seg}
              </Text>
            </Pressable>
          );
        })}
      </View>

      {/* Task List */}
      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#10b981" size="large" />
        </View>
      ) : (
        <FlatList
          className="flex-1 pt-3"
          contentContainerStyle={{paddingBottom: 80}}
          data={sectionsList.flatMap((s) => [
            {type: 'header' as const, title: s.title, sectionType: s.type, id: s.title},
            ...s.data.map((t) => ({type: 'item' as const, task: t, id: String(t.id)})),
          ])}
          keyExtractor={(item) => item.id}
          onRefresh={refetch}
          refreshing={isLoading}
          renderItem={({item}) => {
            if (item.type === 'header') {
              if (item.sectionType === 'overdue') {
                return <PulsingOverdueHeader title="⚠️ Overdue Tasks" />;
              }
              return (
                <View className="px-4 pt-4 pb-2">
                  <Text className={`text-[10px] font-black uppercase tracking-widest ${textSecondary}`}>
                    {item.title}
                  </Text>
                </View>
              );
            }

            return (
              <TaskRowItem
                task={item.task}
                isDark={isDark}
                onComplete={() => updateTask.mutate({id: item.task.id, status: 'completed'})}
                onSnooze={() => {
                  setSnoozeTask(item.task);
                  setSnoozeVisible(true);
                }}
                onTap={() => {
                  setSelectedTask(item.task);
                  setEditTitle(item.task.title);
                  setTaskDetailVisible(true);
                }}
              />
            );
          }}
          ListEmptyComponent={
            <View className="py-24 px-8 items-center justify-center">
              <View className="w-16 h-16 bg-brand-500/10 border border-brand-500/20 rounded-full items-center justify-center mb-4">
                <Icon name="check-circle" size={24} color="#10B981" />
              </View>
              <Text className={`${textPrimary} text-base font-bold mb-1.5 text-center`}>
                Nothing left for today ✦
              </Text>
              <Text className="text-text-secondary text-xs text-center leading-4 max-w-[240px]">
                You've completed all active tasks. Enjoy the clean slate!
              </Text>
            </View>
          }
        />
      )}

      {/* Floating Add Task FAB */}
      <Pressable
        onPress={() => setQuickAddVisible(true)}
        className="absolute bottom-6 right-6 w-14 h-14 rounded-full bg-brand-500 shadow-xl items-center justify-center active:scale-95 z-20"
      >
        <Icon name="plus" size={26} color="#ffffff" />
      </Pressable>

      {/* Quick Add Bottom Sheet Modal */}
      <Modal
        visible={quickAddVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setQuickAddVisible(false)}
      >
        <View className="flex-1 justify-end bg-black/60">
          <Pressable className="flex-1" onPress={() => setQuickAddVisible(false)} />
          <View className={`${bgCard} rounded-t-3xl border-t ${borderHeader} p-5 pb-8`}>
            <View className={`w-12 h-1 ${isDark ? 'bg-zinc-800' : 'bg-slate-350'} rounded-full self-center mb-4`} />

            <View className="flex-row justify-between items-center mb-3">
              <Text className={`${textPrimary} font-black text-lg`}>Quick Add Task</Text>
              <Pressable
                onPress={() => setQuickAddVisible(false)}
                className={`w-8 h-8 rounded-full ${isDark ? 'bg-zinc-800' : 'bg-slate-100'} items-center justify-center`}
              >
                <Icon name="x" size={16} color={isDark ? '#A1A1AA' : '#64748b'} />
              </Pressable>
            </View>

            {/* Input Box */}
            <TextInput
              autoFocus
              className={`rounded-xl px-4 py-3.5 text-sm border ${bgInput} ${textPrimary} ${
                isDark ? 'border-zinc-850' : 'border-slate-200'
              }`}
              placeholder="What needs to be done?"
              placeholderTextColor={isDark ? '#52525B' : '#94a3b8'}
              value={quickText}
              onChangeText={setQuickText}
            />

            {/* Parsing highlights display */}
            {quickText.length > 0 && (
              <View className="mt-2 px-1 flex-row flex-wrap items-center">
                <Text className="text-[10px] text-zinc-550 mr-1 font-bold">NLP Interpretation:</Text>
                {parsedNLP.words.map((word, i) => {
                  const cleanedWord = word.toLowerCase().replace(/[^a-zA-Z]/g, '');
                  const isDate = ['tomorrow', 'today', 'pm', 'am', 'next', 'week'].includes(cleanedWord);
                  const isContact = parsedNLP.contact && (
                    cleanedWord === parsedNLP.contact.first_name.toLowerCase() ||
                    cleanedWord === parsedNLP.contact.last_name.toLowerCase()
                  );

                  if (isDate || isContact) {
                    return (
                      <Text key={i} className="text-brand-500 font-extrabold text-[10.5px]">
                        {word}{' '}
                      </Text>
                    );
                  }
                  return <Text key={i} className={`${textSecondary} text-[10.5px]`}>{word} </Text>;
                })}
              </View>
            )}

            {/* Hint phrase */}
            <Text className="text-zinc-500 text-[10px] mt-2 font-medium">
              💡 Hint: Try typing "Call {contacts?.data?.[0]?.first_name || 'Sarah'} tomorrow 2pm"
            </Text>

            {/* Create Actions */}
            <View className="flex-row gap-3 mt-6">
              <Pressable
                className={`flex-1 rounded-xl py-3.5 items-center ${isDark ? 'bg-zinc-800' : 'bg-slate-100'}`}
                onPress={() => setQuickAddVisible(false)}
              >
                <Text className={`font-bold text-sm ${isDark ? 'text-text-secondary' : 'text-slate-650'}`}>
                  Cancel
                </Text>
              </Pressable>

              <Pressable
                className={`flex-1 rounded-xl py-3.5 items-center bg-brand-500 active:bg-brand-600 ${
                  !quickText.trim() || createTask.isPending ? 'opacity-55' : ''
                }`}
                onPress={handleQuickAdd}
                disabled={!quickText.trim() || createTask.isPending}
              >
                {createTask.isPending ? (
                  <ActivityIndicator color="#fff" size="small" />
                ) : (
                  <Text className="text-white font-bold text-sm">Add Task</Text>
                )}
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>

      {/* Task Details Bottom Sheet */}
      <Modal
        visible={taskDetailVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setTaskDetailVisible(false)}
      >
        <View className="flex-1 justify-end bg-black/60">
          <Pressable className="flex-1" onPress={() => setTaskDetailVisible(false)} />
          <View className={`${bgCard} rounded-t-3xl border-t ${borderHeader} p-5 pb-8`}>
            <View className={`w-12 h-1 ${isDark ? 'bg-zinc-800' : 'bg-slate-350'} rounded-full self-center mb-4`} />

            <View className="flex-row justify-between items-center mb-4">
              <Text className={`${textSecondary} text-xs font-bold uppercase tracking-wider`}>Task Detail</Text>
              <Pressable
                onPress={() => setTaskDetailVisible(false)}
                className={`w-8 h-8 rounded-full ${isDark ? 'bg-zinc-800' : 'bg-slate-100'} items-center justify-center`}
              >
                <Icon name="x" size={16} color={isDark ? '#A1A1AA' : '#64748b'} />
              </Pressable>
            </View>

            {/* Editable Title */}
            <TextInput
              className={`text-lg font-black ${textPrimary} mb-4 border-b ${
                isDark ? 'border-zinc-800' : 'border-slate-200'
              } pb-2 p-0`}
              value={editTitle}
              onChangeText={setEditTitle}
              onBlur={handleSaveTitle}
              placeholder="Task title"
              placeholderTextColor={isDark ? '#52525B' : '#94a3b8'}
            />

            {/* Metadata chips */}
            {selectedTask && (
              <ScrollView horizontal showsHorizontalScrollIndicator={false} className="flex-row gap-2 mb-5">
                {/* Due Date chip */}
                {selectedTask.due_at && (
                  <View className={`px-3 py-1.5 rounded-lg border ${
                    isDark ? 'bg-zinc-800/40 border-zinc-700/60' : 'bg-slate-100 border-slate-200'
                  } flex-row items-center gap-1`}>
                    <Icon name="calendar" size={12} color="#10B981" />
                    <Text className={`text-xs ${textPrimary}`}>
                      {format(new Date(selectedTask.due_at), 'd MMM yyyy, h:mm a')}
                    </Text>
                  </View>
                )}

                {/* Contact Chip */}
                {selectedTask.contact && (
                  <Pressable
                    onPress={() => {
                      setTaskDetailVisible(false);
                      (navigation as any).navigate('Contacts', {
                        screen: 'ContactDetail',
                        params: {contactId: selectedTask.contact_id},
                      });
                    }}
                    className={`px-3 py-1.5 rounded-lg border ${
                      isDark ? 'bg-brand-500/10 border-brand-500/20' : 'bg-emerald-50 border-emerald-200'
                    } flex-row items-center gap-1 active:opacity-80`}
                  >
                    <Icon name="user" size={12} color="#10B981" />
                    <Text className="text-brand-500 text-xs font-bold">
                      {selectedTask.contact.first_name} {selectedTask.contact.last_name}
                    </Text>
                  </Pressable>
                )}

                {/* Mock Listing Chip if call summary */}
                {selectedTask.source === 'call_summary' && (
                  <View className={`px-3 py-1.5 rounded-lg border ${
                    isDark ? 'bg-zinc-800/40 border-zinc-700/60' : 'bg-slate-100 border-slate-200'
                  } flex-row items-center gap-1`}>
                    <Icon name="home" size={12} color="#F59E0B" />
                    <Text className={`text-xs ${textPrimary}`}>Lekki Phase 1 Property</Text>
                  </View>
                )}
              </ScrollView>
            )}

            {/* AI Context Card if task originated from a call */}
            {selectedTask?.source === 'call_summary' && (
              <View className={`border-l-4 border-l-brand-500 p-3.5 mb-6 rounded-r-xl ${
                isDark ? 'bg-brand-500/5' : 'bg-emerald-500/5'
              }`}>
                <View className="flex-row items-center gap-1 mb-1">
                  <Icon name="sparkles" size={12} color="#10B981" />
                  <Text className="text-brand-500 text-[10px] font-black uppercase tracking-wider">
                    AI Briefing Context
                  </Text>
                </View>
                <Text className={`text-xs leading-5 italic ${textPrimary}`}>
                  "Created from your call with {selectedTask.contact?.first_name || 'Sarah'} — she requested follow-up viewing availability, Nigerian Naira pricing sheets, and nearby school coordinates."
                </Text>
              </View>
            )}

            {/* Delete option */}
            <View className="border-t border-zinc-800/80 pt-4 flex-row justify-between items-center">
              <Pressable
                onPress={() => {
                  if (!selectedTask) return;
                  Alert.alert('Delete Task', 'Are you sure you want to permanently delete this task?', [
                    {text: 'Cancel', style: 'cancel'},
                    {
                      text: 'Delete',
                      style: 'destructive',
                      onPress: () => deleteTask.mutate(selectedTask.id),
                    },
                  ]);
                }}
                className="py-2.5 px-4 active:opacity-75"
              >
                <Text className="text-danger font-black text-sm">Delete Task</Text>
              </Pressable>

              <Pressable
                onPress={() => setTaskDetailVisible(false)}
                className="bg-brand-500 px-6 py-2.5 rounded-xl active:bg-brand-650"
              >
                <Text className="text-white font-bold text-sm">Done</Text>
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>

      {/* Snooze Picker Sheet */}
      <Modal
        visible={snoozeVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setSnoozeVisible(false)}
      >
        <View className="flex-1 justify-end bg-black/60">
          <Pressable className="flex-1" onPress={() => setSnoozeVisible(false)} />
          <View className={`${bgCard} rounded-t-3xl border-t ${borderHeader} p-5 pb-8`}>
            <View className={`w-12 h-1 ${isDark ? 'bg-zinc-800' : 'bg-slate-350'} rounded-full self-center mb-4`} />

            <Text className={`${textPrimary} font-black text-lg mb-4`}>Snooze Task</Text>

            <View className="gap-2.5">
              {[
                {label: 'Snooze for 1 Hour', value: '1h' as const, icon: 'clock'},
                {label: 'Snooze until Tomorrow Morning (9 AM)', value: 'tomorrow' as const, icon: 'sun'},
                {label: 'Snooze until Next Week (9 AM)', value: 'week' as const, icon: 'calendar'},
              ].map((opt) => (
                <Pressable
                  key={opt.value}
                  onPress={() => handleSnoozeCommit(opt.value)}
                  className={`flex-row items-center gap-3 p-4 rounded-xl border ${
                    isDark
                      ? 'bg-[#111827] border-zinc-850 active:bg-zinc-800'
                      : 'bg-slate-100 border-slate-200 active:bg-slate-200'
                  }`}
                >
                  <Icon name={opt.icon} size={16} color="#F59E0B" />
                  <Text className={`font-bold text-sm ${textPrimary}`}>{opt.label}</Text>
                </Pressable>
              ))}
            </View>

            <Pressable
              onPress={() => setSnoozeVisible(false)}
              className={`mt-4 rounded-xl py-3.5 items-center ${isDark ? 'bg-zinc-800' : 'bg-slate-100'}`}
            >
              <Text className={`font-bold text-sm ${isDark ? 'text-text-secondary' : 'text-slate-650'}`}>
                Cancel
              </Text>
            </Pressable>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}
