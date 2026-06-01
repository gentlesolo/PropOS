/**
 * Shared formatting utilities used across multiple screens.
 * Centralised here to avoid duplication and ensure consistency.
 */

export function formatDuration(seconds: number): string {
  if (!seconds || seconds <= 0) return '0:00';
  const m = Math.floor(seconds / 60);
  const s = seconds % 60;
  return `${m}:${String(s).padStart(2, '0')}`;
}

export function formatDurationLong(seconds: number): string {
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  if (h > 0) return `${h}h ${m}m`;
  if (m > 0) return `${m}m ${seconds % 60}s`;
  return `${seconds}s`;
}

export function formatSentiment(sentiment: string): string {
  return sentiment.charAt(0).toUpperCase() + sentiment.slice(1);
}

export function sentimentColor(sentiment: string): string {
  return (
    {hot: '#ef4444', warm: '#f59e0b', cold: '#3b82f6', neutral: '#64748b'}[sentiment] ??
    '#64748b'
  );
}

export function sentimentDotClass(sentiment: string): string {
  return (
    {hot: 'bg-red-500', warm: 'bg-amber-500', cold: 'bg-blue-400', neutral: 'bg-slate-500'}[
      sentiment
    ] ?? 'bg-slate-500'
  );
}

export function initials(firstName: string, lastName: string): string {
  return (
    (firstName.charAt(0) ?? '').toUpperCase() +
    (lastName.charAt(0) ?? '').toUpperCase()
  );
}

export function truncate(text: string, maxLen: number): string {
  return text.length > maxLen ? text.slice(0, maxLen) + '…' : text;
}
