import {
  formatDuration,
  formatDurationLong,
  formatSentiment,
  sentimentDotClass,
  initials,
  truncate,
} from '../src/utils/format';

describe('formatDuration', () => {
  it('formats 0 seconds', () => expect(formatDuration(0)).toBe('0:00'));
  it('formats 65 seconds as 1:05', () => expect(formatDuration(65)).toBe('1:05'));
  it('formats 3600 seconds as 60:00', () => expect(formatDuration(3600)).toBe('60:00'));
  it('pads single-digit seconds', () => expect(formatDuration(61)).toBe('1:01'));
});

describe('formatDurationLong', () => {
  it('returns seconds only for < 1 min', () => expect(formatDurationLong(45)).toBe('45s'));
  it('returns mins and seconds for < 1 hour', () => expect(formatDurationLong(125)).toBe('2m 5s'));
  it('returns hours and mins for >= 1 hour', () => expect(formatDurationLong(3660)).toBe('1h 1m'));
});

describe('formatSentiment', () => {
  it('capitalises first letter', () => {
    expect(formatSentiment('hot')).toBe('Hot');
    expect(formatSentiment('neutral')).toBe('Neutral');
  });
});

describe('sentimentDotClass', () => {
  it('returns correct classes', () => {
    expect(sentimentDotClass('hot')).toBe('bg-red-500');
    expect(sentimentDotClass('warm')).toBe('bg-amber-500');
    expect(sentimentDotClass('cold')).toBe('bg-blue-400');
    expect(sentimentDotClass('neutral')).toBe('bg-slate-500');
    expect(sentimentDotClass('unknown')).toBe('bg-slate-500');
  });
});

describe('initials', () => {
  it('returns uppercase initials', () => expect(initials('john', 'doe')).toBe('JD'));
  it('handles empty strings', () => expect(initials('', '')).toBe(''));
});

describe('truncate', () => {
  it('does not truncate short text', () => expect(truncate('hello', 10)).toBe('hello'));
  it('truncates and adds ellipsis', () => expect(truncate('hello world', 5)).toBe('hello…'));
});
