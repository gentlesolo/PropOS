import React, {useState, useRef, useEffect, useMemo} from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  Text,
  TextInput,
  View,
  Modal,
  Alert,
  Animated,
  PanResponder,
  ScrollView,
  Vibration,
} from 'react-native';
import {SafeAreaView, useSafeAreaInsets} from 'react-native-safe-area-context';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {tasksApi} from '../../api/tasks';
import {contactsApi} from '../../api/contacts';
import {Task} from '../../types';
import {format, isToday, isPast, isTomorrow, addDays, addHours} from 'date-fns';
import Icon from 'react-native-vector-icons/Feather';
import {useNavigation} from '@react-navigation/native';
import {useTheme} from '../../theme/ThemeProvider';
import {ThemeTokens} from '../../theme/tokens';

type SegmentType = 'My Day' | 'Upcoming' | 'All';

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
    <View style={{paddingHorizontal: 16, paddingVertical: 12, marginTop: 16, flexDirection: 'row', alignItems: 'center', borderLeftWidth: 4, borderLeftColor: '#F43F5E', backgroundColor: '#F43F5E0D'}}>
      <Animated.View style={{opacity: pulseAnim, width: 6, height: 6, borderRadius: 3, backgroundColor: '#F43F5E', marginRight: 8}} />
      <Text style={{color: '#F43F5E', fontSize: 11, fontWeight: '900', textTransform: 'uppercase', letterSpacing: 1.5}}>
        {title}
      </Text>
    </View>
  );
}

const PRIORITY_COLOR: Record<string, string> = {
  low: '#10B981', medium: '#F59E0B', high: '#EF4444', urgent: '#7C3AED',
};

function TaskRowItem({
  task,
  onComplete,
  onSnooze,
  onTap,
  tokens,
}: {
  task: Task;
  onComplete: () => void;
  onSnooze: () => void;
  onTap: () => void;
  tokens: ThemeTokens;
}) {
  const overdue = task.due_at && isPast(new Date(task.due_at)) && task.status !== 'completed';
  const isCompleted = task.status === 'completed';

  const [completing, setCompleting] = useState(false);
  const checkScale = useRef(new Animated.Value(0)).current;
  const rowOpacity = useRef(new Animated.Value(isCompleted ? 0.5 : 1)).current;
  const translateX = useRef(new Animated.Value(0)).current;
  const currentTranslation = useRef(0);

  const handleCheckboxPress = () => {
    if (isCompleted || completing) return;
    setCompleting(true);
    Animated.timing(checkScale, {toValue: 1, duration: 150, useNativeDriver: true}).start(() => {
      setTimeout(() => {
        Animated.timing(rowOpacity, {toValue: 0.5, duration: 300, useNativeDriver: true}).start(() => {
          onComplete();
        });
      }, 300);
    });
  };

  const snap = (toValue: number) => {
    Animated.spring(translateX, {toValue, useNativeDriver: true, bounciness: 4, speed: 12}).start(() => {
      currentTranslation.current = toValue;
    });
  };

  const panResponder = useRef(
    PanResponder.create({
      onStartShouldSetPanResponder: () => false,
      onMoveShouldSetPanResponder: (_, g) => Math.abs(g.dx) > Math.abs(g.dy) && Math.abs(g.dx) > 10,
      onPanResponderMove: (_, g) => {
        let newX = currentTranslation.current + g.dx;
        if (newX > 160) newX = 160;
        if (newX < -100) newX = -100;
        translateX.setValue(newX);
      },
      onPanResponderRelease: (_, g) => {
        if (g.dx > 125) {
          Animated.timing(translateX, {toValue: 400, duration: 200, useNativeDriver: true}).start(() => handleCheckboxPress());
        } else if (g.dx < -55) {
          snap(-80);
        } else {
          snap(0);
        }
      },
      onPanResponderTerminate: () => snap(0),
    })
  ).current;

  const priorityDotColor = PRIORITY_COLOR[task.priority || 'medium'];

  return (
    <View style={{position: 'relative', overflow: 'hidden', marginBottom: 10, marginHorizontal: 16, borderRadius: 14, backgroundColor: tokens.surfaceCard, borderWidth: 1, borderColor: tokens.borderDefault, shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 4, shadowOffset: {width: 0, height: 2}, elevation: 2}}>
      {/* Swipe right: complete */}
      <View style={{position: 'absolute', left: 0, top: 0, bottom: 0, right: 0, backgroundColor: '#10B981', flexDirection: 'row', alignItems: 'center', paddingLeft: 20, zIndex: 0, borderRadius: 16}}>
        <Icon name="check" size={20} color="#ffffff" />
        <Text style={{color: '#ffffff', fontSize: 11, fontWeight: '900', marginLeft: 8}}>Complete</Text>
      </View>

      {/* Swipe left: snooze */}
      <View style={{position: 'absolute', right: 0, top: 0, bottom: 0, width: 80, backgroundColor: tokens.brandAccent, alignItems: 'center', justifyContent: 'center', zIndex: 0, borderRadius: 16}}>
        <Pressable
          onPress={() => { snap(0); onSnooze(); }}
          style={{width: '100%', height: '100%', alignItems: 'center', justifyContent: 'center'}}
        >
          <Icon name="clock" size={18} color="#ffffff" />
          <Text style={{color: '#ffffff', fontSize: 10, fontWeight: '900', marginTop: 4}}>Snooze</Text>
        </Pressable>
      </View>

      {/* Animated content body */}
      <Animated.View
        style={{transform: [{translateX}], opacity: rowOpacity, backgroundColor: tokens.surfaceCard, borderWidth: 1, borderColor: tokens.borderDefault, borderRadius: 16, padding: 16, flexDirection: 'row', alignItems: 'center', zIndex: 10, width: '100%'}}
        {...panResponder.panHandlers}
      >
        {/* Checkbox */}
        <Pressable
          onPress={handleCheckboxPress}
          style={{
            width: 24, height: 24, borderRadius: 12, borderWidth: 2, alignItems: 'center', justifyContent: 'center', marginRight: 14,
            backgroundColor: (isCompleted || completing) ? tokens.brandPrimary : 'transparent',
            borderColor: (isCompleted || completing) ? tokens.brandPrimary : tokens.borderStrong,
          }}
        >
          {(isCompleted || completing) && (
            <Animated.View style={{transform: [{scale: isCompleted ? 1 : checkScale}]}}>
              <Icon name="check" size={11} color="#ffffff" />
            </Animated.View>
          )}
        </Pressable>

        {/* Title & details */}
        <Pressable onPress={onTap} style={{flex: 1}}>
          <View style={{flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center', gap: 6, marginBottom: 4}}>
            <Text
              style={{fontSize: 14, lineHeight: 20, fontWeight: '700', color: isCompleted ? tokens.textDisabled : overdue ? '#F43F5E' : tokens.textPrimary, textDecorationLine: isCompleted ? 'line-through' : 'none'}}
              numberOfLines={2}
            >
              {task.title}
            </Text>
            {overdue && (
              <View style={{backgroundColor: '#F43F5E1A', paddingHorizontal: 6, paddingVertical: 2, borderRadius: 4}}>
                <Text style={{color: '#F43F5E', fontSize: 9, fontWeight: '900', textTransform: 'uppercase'}}>Overdue</Text>
              </View>
            )}
          </View>

          <View style={{flexDirection: 'row', alignItems: 'center', flexWrap: 'wrap', gap: 8, marginTop: 2}}>
            {task.due_at && (
              <View style={{flexDirection: 'row', alignItems: 'center', gap: 3}}>
                <Icon name="clock" size={10} color={overdue ? tokens.dangerText : tokens.textTertiary} />
                <Text style={{fontSize: 11, fontWeight: '600', color: overdue ? tokens.dangerText : tokens.textSecondary}}>
                  {format(new Date(task.due_at), 'd MMM, h:mm a')}
                </Text>
              </View>
            )}
            {task.contact && (
              <View style={{flexDirection: 'row', alignItems: 'center', gap: 3}}>
                <Icon name="user" size={10} color={tokens.textTertiary} />
                <Text style={{fontSize: 11, fontWeight: '600', color: tokens.textSecondary}} numberOfLines={1}>
                  {task.contact.first_name} {task.contact.last_name}
                </Text>
              </View>
            )}
            {task.source === 'call_summary' && (
              <View style={{flexDirection: 'row', alignItems: 'center', gap: 3}}>
                <Icon name="phone" size={10} color={tokens.brandAccent} />
                <Text style={{fontSize: 10, color: tokens.brandAccent, fontWeight: '600'}}>AI generated</Text>
              </View>
            )}
          </View>
        </Pressable>

        {/* Priority dot */}
        <View style={{width: 8, height: 8, borderRadius: 4, backgroundColor: priorityDotColor, marginLeft: 10}} />
      </Animated.View>
    </View>
  );
}

export function TasksScreen() {
  const {tokens} = useTheme();
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();
  const navigation = useNavigation();

  const [activeSegment, setActiveSegment] = useState<SegmentType>('My Day');
  const [showCompleted, setShowCompleted] = useState(false);
  const [selectedTask, setSelectedTask] = useState<Task | null>(null);
  const [taskDetailVisible, setTaskDetailVisible] = useState(false);
  const [editTitle, setEditTitle] = useState('');
  const [quickAddVisible, setQuickAddVisible] = useState(false);
  const [quickText, setQuickText] = useState('');
  const [snoozeTask, setSnoozeTask] = useState<Task | null>(null);
  const [snoozeVisible, setSnoozeVisible] = useState(false);

  const {data: tasks, isLoading, refetch} = useQuery({
    queryKey: ['tasks'],
    queryFn: () => tasksApi.list().then((r) => r.data),
  });

  const {data: contacts} = useQuery({
    queryKey: ['contacts'],
    queryFn: () => contactsApi.list().then((r) => r.data),
  });

  const updateTask = useMutation({
    mutationFn: ({id, status, due_at, title}: {id: number; status?: string; due_at?: string; title?: string}) =>
      tasksApi.update(id, {status, due_at, title}),
    onSuccess: () => queryClient.invalidateQueries({queryKey: ['tasks']}),
  });

  const createTask = useMutation({
    mutationFn: (payload: {title: string; contact_id?: number; due_at?: string}) => tasksApi.store(payload),
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

  const overdueCount = useMemo(
    () => tasks?.filter((t) => t.status !== 'completed' && t.due_at && isPast(new Date(t.due_at))).length || 0,
    [tasks]
  );

  const parsedNLP = useMemo(() => {
    if (!quickText) return {words: [], contact: null, date: null};
    const words = quickText.split(' ');
    const contactsList = contacts?.data ?? [];
    const matchedContact = contactsList.find((c) =>
      words.some((w) => {
        const clean = w.toLowerCase().replace(/[^a-zA-Z]/g, '');
        return clean === c.first_name.toLowerCase() || clean === c.last_name.toLowerCase();
      })
    );
    let matchedDate: string | null = null;
    const lower = quickText.toLowerCase();
    if (lower.includes('tomorrow')) matchedDate = 'Tomorrow';
    else if (lower.includes('today')) matchedDate = 'Today';
    else if (lower.includes('next week')) matchedDate = 'Next Week';
    else if (lower.includes('pm') || lower.includes('am')) matchedDate = 'Time Specified';
    return {words, contact: matchedContact || null, date: matchedDate};
  }, [quickText, contacts]);

  const sectionsList = useMemo(() => {
    const list = tasks ?? [];
    const overdueList = list.filter((t) => t.status !== 'completed' && t.due_at && isPast(new Date(t.due_at)));

    if (activeSegment === 'My Day') {
      const todayList = list.filter((t) => t.status !== 'completed' && t.due_at && isToday(new Date(t.due_at)));
      const nodateList = list.filter((t) => t.status !== 'completed' && !t.due_at);
      return [
        ...(overdueList.length > 0 ? [{title: 'Overdue', data: overdueList, type: 'overdue' as const}] : []),
        {title: 'Today', data: [...todayList, ...nodateList], type: 'today' as const},
      ];
    }
    if (activeSegment === 'Upcoming') {
      const tomorrowList = list.filter((t) => t.status !== 'completed' && t.due_at && isTomorrow(new Date(t.due_at)));
      const thisWeekList = list.filter((t) => {
        if (t.status === 'completed' || !t.due_at) return false;
        const d = new Date(t.due_at);
        return d > addDays(new Date(), 1) && d <= addDays(new Date(), 7);
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

  const handleQuickAdd = () => {
    if (!quickText.trim()) return;
    let dueTime = new Date();
    const lower = quickText.toLowerCase();
    if (lower.includes('tomorrow')) { dueTime = addDays(new Date(), 1); dueTime.setHours(9, 0, 0, 0); }
    else if (lower.includes('next week')) { dueTime = addDays(new Date(), 7); dueTime.setHours(9, 0, 0, 0); }
    else { dueTime.setHours(18, 0, 0, 0); }
    createTask.mutate({title: quickText.trim(), contact_id: parsedNLP.contact?.id, due_at: dueTime.toISOString()});
  };

  const handleSnoozeCommit = (option: '1h' | 'tomorrow' | 'week') => {
    if (!snoozeTask) return;
    let targetTime = new Date();
    if (option === '1h') targetTime = addHours(new Date(), 1);
    else if (option === 'tomorrow') { targetTime = addDays(new Date(), 1); targetTime.setHours(9, 0, 0, 0); }
    else { targetTime = addDays(new Date(), 7); targetTime.setHours(9, 0, 0, 0); }
    updateTask.mutate({id: snoozeTask.id, due_at: targetTime.toISOString()});
    setSnoozeVisible(false);
    setSnoozeTask(null);
  };

  const handleSaveTitle = () => {
    if (selectedTask && editTitle.trim() && editTitle !== selectedTask.title) {
      updateTask.mutate({id: selectedTask.id, title: editTitle.trim()});
    }
  };

  const sheetStyle = {
    backgroundColor: tokens.surfaceCard,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    borderTopWidth: 1,
    borderTopColor: tokens.borderDefault,
    padding: 20,
    paddingBottom: 32,
  };

  const inputStyle = {
    backgroundColor: tokens.surfaceInput,
    color: tokens.textPrimary,
    borderWidth: 1,
    borderColor: tokens.borderDefault,
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 14,
    fontSize: 14,
  };

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}}>
      {/* Header — matches ContactsScreen style */}
      <View style={{paddingHorizontal: 20, paddingTop: 12, paddingBottom: 12, backgroundColor: tokens.surfaceCard, borderBottomWidth: 1, borderBottomColor: tokens.borderDefault, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center'}}>
        <View style={{flexDirection: 'row', alignItems: 'center', gap: 10}}>
          <Text style={{color: tokens.textPrimary, fontSize: 22, fontWeight: '700', letterSpacing: -0.5}}>Tasks</Text>
          {overdueCount > 0 && (
            <View style={{backgroundColor: tokens.dangerBg, paddingHorizontal: 8, paddingVertical: 3, borderRadius: 6, borderWidth: 1, borderColor: tokens.dangerBorder}}>
              <Text style={{color: tokens.dangerText, fontSize: 10, fontWeight: '800'}}>{overdueCount} overdue</Text>
            </View>
          )}
        </View>

        {activeSegment === 'All' && (
          <Pressable
            onPress={() => { Vibration.vibrate(10); setShowCompleted(!showCompleted); }}
            style={{paddingHorizontal: 12, paddingVertical: 6, borderRadius: 8, borderWidth: 1, borderColor: `${tokens.brandPrimary}33`, backgroundColor: `${tokens.brandPrimary}0D`, flexDirection: 'row', alignItems: 'center', gap: 6}}
          >
            <Icon name={showCompleted ? 'eye-off' : 'eye'} size={12} color={tokens.brandPrimary} />
            <Text style={{color: tokens.brandPrimary, fontSize: 12, fontWeight: '700'}}>
              {showCompleted ? 'Hide done' : 'Show done'}
            </Text>
          </Pressable>
        )}
      </View>

      {/* Segment toggle — pill style matching ContactsScreen filter chips */}
      <View style={{flexDirection: 'row', marginHorizontal: 16, marginTop: 14, marginBottom: 6, gap: 8}}>
        {(['My Day', 'Upcoming', 'All'] as const).map((seg) => {
          const isActive = activeSegment === seg;
          return (
            <Pressable
              key={seg}
              onPress={() => { Vibration.vibrate(10); setActiveSegment(seg); }}
              style={{
                paddingHorizontal: 16, paddingVertical: 7, borderRadius: 20, borderWidth: 1,
                backgroundColor: isActive ? `${tokens.brandPrimary}1A` : tokens.surfaceCard,
                borderColor: isActive ? `${tokens.brandPrimary}40` : tokens.borderDefault,
              }}
            >
              <Text style={{fontSize: 13, fontWeight: '700', color: isActive ? tokens.brandPrimary : tokens.textSecondary}}>
                {seg}
              </Text>
            </Pressable>
          );
        })}
      </View>

      {/* Task list */}
      {isLoading ? (
        <View style={{flex: 1, alignItems: 'center', justifyContent: 'center'}}>
          <ActivityIndicator color={tokens.brandPrimary} size="large" />
        </View>
      ) : (
        <FlatList
          style={{flex: 1, paddingTop: 12}}
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
              if (item.sectionType === 'overdue') return <PulsingOverdueHeader title="Overdue Tasks" />;
              return (
                <View style={{paddingHorizontal: 20, paddingTop: 20, paddingBottom: 8, flexDirection: 'row', alignItems: 'center', gap: 8}}>
                  <Text style={{fontSize: 11, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 1.5, color: tokens.textTertiary}}>
                    {item.title}
                  </Text>
                  <View style={{flex: 1, height: 1, backgroundColor: tokens.borderSubtle}} />
                </View>
              );
            }
            return (
              <TaskRowItem
                task={item.task}
                tokens={tokens}
                onComplete={() => { Vibration.vibrate(25); updateTask.mutate({id: item.task.id, status: 'completed'}); }}
                onSnooze={() => { setSnoozeTask(item.task); setSnoozeVisible(true); }}
                onTap={() => { setSelectedTask(item.task); setEditTitle(item.task.title); setTaskDetailVisible(true); }}
              />
            );
          }}
          ListEmptyComponent={
            <View style={{paddingVertical: 96, paddingHorizontal: 32, alignItems: 'center', justifyContent: 'center'}}>
              <View style={{width: 64, height: 64, backgroundColor: `${tokens.brandPrimary}1A`, borderWidth: 1, borderColor: `${tokens.brandPrimary}33`, borderRadius: 32, alignItems: 'center', justifyContent: 'center', marginBottom: 16}}>
                <Icon name="check-circle" size={24} color={tokens.brandPrimary} />
              </View>
              <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '700', marginBottom: 6, textAlign: 'center'}}>Nothing left for today</Text>
              <Text style={{color: tokens.textSecondary, fontSize: 12, textAlign: 'center', lineHeight: 16, maxWidth: 240}}>
                You've completed all active tasks. Enjoy the clean slate!
              </Text>
            </View>
          }
        />
      )}

      {/* FAB — safe-area anchored, matching ContactDetailScreen FAB */}
      <Pressable
        onPress={() => setQuickAddVisible(true)}
        style={({pressed}) => ({
          position: 'absolute',
          bottom: (insets.bottom > 0 ? insets.bottom : 16) + 70,
          right: 16,
          width: 56,
          height: 56,
          borderRadius: 28,
          backgroundColor: tokens.brandPrimary,
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 20,
          shadowColor: tokens.brandPrimary,
          shadowOpacity: 0.35,
          shadowRadius: 12,
          shadowOffset: {width: 0, height: 4},
          elevation: 8,
          transform: [{scale: pressed ? 0.93 : 1}],
        })}
      >
        <Icon name="plus" size={24} color="#ffffff" />
      </Pressable>

      {/* Quick Add sheet */}
      <Modal visible={quickAddVisible} transparent animationType="slide" onRequestClose={() => setQuickAddVisible(false)}>
        <View style={{flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(2,6,23,0.6)'}}>
          <Pressable style={{flex: 1}} onPress={() => setQuickAddVisible(false)} />
          <View style={sheetStyle}>
            <View style={{width: 48, height: 4, backgroundColor: tokens.borderStrong, borderRadius: 999, alignSelf: 'center', marginBottom: 16}} />
            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12}}>
              <Text style={{color: tokens.textPrimary, fontWeight: '900', fontSize: 18}}>Quick Add Task</Text>
              <Pressable onPress={() => setQuickAddVisible(false)} style={{width: 32, height: 32, borderRadius: 16, backgroundColor: tokens.surfaceRaised, alignItems: 'center', justifyContent: 'center'}}>
                <Icon name="x" size={16} color={tokens.textSecondary} />
              </Pressable>
            </View>

            <TextInput
              autoFocus
              style={inputStyle}
              placeholder="What needs to be done?"
              placeholderTextColor={tokens.textTertiary}
              value={quickText}
              onChangeText={setQuickText}
            />

            {quickText.length > 0 && (
              <View style={{marginTop: 8, paddingHorizontal: 4, flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center'}}>
                <Text style={{fontSize: 10, color: tokens.textTertiary, marginRight: 4, fontWeight: '700'}}>NLP Interpretation:</Text>
                {parsedNLP.words.map((word, i) => {
                  const clean = word.toLowerCase().replace(/[^a-zA-Z]/g, '');
                  const isDate = ['tomorrow', 'today', 'pm', 'am', 'next', 'week'].includes(clean);
                  const isContact = parsedNLP.contact && (clean === parsedNLP.contact.first_name.toLowerCase() || clean === parsedNLP.contact.last_name.toLowerCase());
                  return (
                    <Text key={i} style={{fontSize: 10.5, color: (isDate || isContact) ? tokens.brandPrimary : tokens.textSecondary, fontWeight: (isDate || isContact) ? '800' : '400'}}>
                      {word}{' '}
                    </Text>
                  );
                })}
              </View>
            )}

            <Text style={{color: tokens.textTertiary, fontSize: 10, marginTop: 8, fontWeight: '500'}}>
              Hint: Try typing "Call {contacts?.data?.[0]?.first_name || 'Sarah'} tomorrow 2pm"
            </Text>

            <View style={{flexDirection: 'row', gap: 12, marginTop: 24}}>
              <Pressable style={{flex: 1, borderRadius: 12, paddingVertical: 14, alignItems: 'center', backgroundColor: tokens.surfaceRaised}} onPress={() => setQuickAddVisible(false)}>
                <Text style={{fontWeight: '700', fontSize: 14, color: tokens.textSecondary}}>Cancel</Text>
              </Pressable>
              <Pressable
                style={{flex: 1, borderRadius: 12, paddingVertical: 14, alignItems: 'center', backgroundColor: tokens.brandPrimary, opacity: (!quickText.trim() || createTask.isPending) ? 0.55 : 1}}
                onPress={handleQuickAdd}
                disabled={!quickText.trim() || createTask.isPending}
              >
                {createTask.isPending ? <ActivityIndicator color="#fff" size="small" /> : <Text style={{color: '#ffffff', fontWeight: '700', fontSize: 14}}>Add Task</Text>}
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>

      {/* Task Detail sheet */}
      <Modal visible={taskDetailVisible} transparent animationType="slide" onRequestClose={() => setTaskDetailVisible(false)}>
        <View style={{flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(2,6,23,0.6)'}}>
          <Pressable style={{flex: 1}} onPress={() => setTaskDetailVisible(false)} />
          <View style={sheetStyle}>
            <View style={{width: 48, height: 4, backgroundColor: tokens.borderStrong, borderRadius: 999, alignSelf: 'center', marginBottom: 16}} />
            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16}}>
              <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 1}}>Task Detail</Text>
              <Pressable onPress={() => setTaskDetailVisible(false)} style={{width: 32, height: 32, borderRadius: 16, backgroundColor: tokens.surfaceRaised, alignItems: 'center', justifyContent: 'center'}}>
                <Icon name="x" size={16} color={tokens.textSecondary} />
              </Pressable>
            </View>

            <TextInput
              style={{fontSize: 18, fontWeight: '900', color: tokens.textPrimary, marginBottom: 16, borderBottomWidth: 1, borderBottomColor: tokens.borderDefault, paddingBottom: 8, padding: 0}}
              value={editTitle}
              onChangeText={setEditTitle}
              onBlur={handleSaveTitle}
              placeholder="Task title"
              placeholderTextColor={tokens.textTertiary}
            />

            {selectedTask && (
              <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{marginBottom: 20}}>
                <View style={{flexDirection: 'row', gap: 8}}>
                  {selectedTask.due_at && (
                    <View style={{paddingHorizontal: 12, paddingVertical: 6, borderRadius: 8, borderWidth: 1, backgroundColor: tokens.surfaceRaised, borderColor: tokens.borderDefault, flexDirection: 'row', alignItems: 'center', gap: 4}}>
                      <Icon name="calendar" size={12} color={tokens.brandPrimary} />
                      <Text style={{fontSize: 12, color: tokens.textPrimary}}>
                        {format(new Date(selectedTask.due_at), 'd MMM yyyy, h:mm a')}
                      </Text>
                    </View>
                  )}
                  {selectedTask.contact && (
                    <Pressable
                      onPress={() => { setTaskDetailVisible(false); (navigation as any).navigate('Contacts', {screen: 'ContactDetail', params: {contactId: selectedTask.contact_id}}); }}
                      style={{paddingHorizontal: 12, paddingVertical: 6, borderRadius: 8, borderWidth: 1, backgroundColor: `${tokens.brandPrimary}1A`, borderColor: `${tokens.brandPrimary}33`, flexDirection: 'row', alignItems: 'center', gap: 4}}
                    >
                      <Icon name="user" size={12} color={tokens.brandPrimary} />
                      <Text style={{color: tokens.brandPrimary, fontSize: 12, fontWeight: '700'}}>
                        {selectedTask.contact.first_name} {selectedTask.contact.last_name}
                      </Text>
                    </Pressable>
                  )}
                  {selectedTask.source === 'call_summary' && (
                    <View style={{paddingHorizontal: 12, paddingVertical: 6, borderRadius: 8, borderWidth: 1, backgroundColor: tokens.surfaceRaised, borderColor: tokens.borderDefault, flexDirection: 'row', alignItems: 'center', gap: 4}}>
                      <Icon name="home" size={12} color={tokens.brandAccent} />
                      <Text style={{fontSize: 12, color: tokens.textPrimary}}>Lekki Phase 1 Property</Text>
                    </View>
                  )}
                </View>
              </ScrollView>
            )}

            {selectedTask?.source === 'call_summary' && (
              <View style={{borderLeftWidth: 4, borderLeftColor: tokens.brandPrimary, padding: 14, marginBottom: 24, borderRadius: 8, backgroundColor: `${tokens.brandPrimary}0D`, borderTopRightRadius: 12, borderBottomRightRadius: 12}}>
                <View style={{flexDirection: 'row', alignItems: 'center', gap: 4, marginBottom: 4}}>
                  <Icon name="zap" size={12} color={tokens.brandPrimary} />
                  <Text style={{color: tokens.brandPrimary, fontSize: 10, fontWeight: '900', textTransform: 'uppercase', letterSpacing: 1}}>AI Briefing Context</Text>
                </View>
                <Text style={{fontSize: 12, lineHeight: 20, fontStyle: 'italic', color: tokens.textPrimary}}>
                  "Created from your call with {selectedTask.contact?.first_name || 'Sarah'} — she requested follow-up viewing availability, Nigerian Naira pricing sheets, and nearby school coordinates."
                </Text>
              </View>
            )}

            <View style={{borderTopWidth: 1, borderTopColor: tokens.borderDefault, paddingTop: 16, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center'}}>
              <Pressable
                onPress={() => {
                  if (!selectedTask) return;
                  Alert.alert('Delete Task', 'Are you sure you want to permanently delete this task?', [
                    {text: 'Cancel', style: 'cancel'},
                    {text: 'Delete', style: 'destructive', onPress: () => deleteTask.mutate(selectedTask.id)},
                  ]);
                }}
                style={{paddingVertical: 10, paddingHorizontal: 16}}
              >
                <Text style={{color: '#F43F5E', fontWeight: '900', fontSize: 14}}>Delete Task</Text>
              </Pressable>
              <Pressable onPress={() => setTaskDetailVisible(false)} style={{backgroundColor: tokens.brandPrimary, paddingHorizontal: 24, paddingVertical: 10, borderRadius: 12}}>
                <Text style={{color: '#ffffff', fontWeight: '700', fontSize: 14}}>Done</Text>
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>

      {/* Snooze sheet */}
      <Modal visible={snoozeVisible} transparent animationType="slide" onRequestClose={() => setSnoozeVisible(false)}>
        <View style={{flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(0,0,0,0.6)'}}>
          <Pressable style={{flex: 1}} onPress={() => setSnoozeVisible(false)} />
          <View style={sheetStyle}>
            <View style={{width: 48, height: 4, backgroundColor: tokens.borderStrong, borderRadius: 999, alignSelf: 'center', marginBottom: 16}} />
            <Text style={{color: tokens.textPrimary, fontWeight: '900', fontSize: 18, marginBottom: 16}}>Snooze Task</Text>

            <View style={{gap: 10}}>
              {[
                {label: 'Snooze for 1 Hour', value: '1h' as const, icon: 'clock'},
                {label: 'Snooze until Tomorrow Morning (9 AM)', value: 'tomorrow' as const, icon: 'sun'},
                {label: 'Snooze until Next Week (9 AM)', value: 'week' as const, icon: 'calendar'},
              ].map((opt) => (
                <Pressable
                  key={opt.value}
                  onPress={() => handleSnoozeCommit(opt.value)}
                  style={{flexDirection: 'row', alignItems: 'center', gap: 12, padding: 16, borderRadius: 12, borderWidth: 1, backgroundColor: tokens.surfaceRaised, borderColor: tokens.borderDefault}}
                >
                  <Icon name={opt.icon} size={16} color={tokens.brandAccent} />
                  <Text style={{fontWeight: '700', fontSize: 14, color: tokens.textPrimary}}>{opt.label}</Text>
                </Pressable>
              ))}
            </View>

            <Pressable onPress={() => setSnoozeVisible(false)} style={{marginTop: 16, borderRadius: 12, paddingVertical: 14, alignItems: 'center', backgroundColor: tokens.surfaceRaised}}>
              <Text style={{fontWeight: '700', fontSize: 14, color: tokens.textSecondary}}>Cancel</Text>
            </Pressable>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}
