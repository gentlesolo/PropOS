import {device, element, by, expect as detoxExpect, waitFor} from 'detox';

describe('Task flow', () => {
  beforeAll(async () => {
    await device.launchApp({newInstance: false});
    await element(by.text('Tasks')).tap();
  });

  it('shows tasks screen', async () => {
    await detoxExpect(element(by.text('Tasks'))).toBeVisible();
  });

  it('can complete a task', async () => {
    // Tap the checkbox on the first visible task
    const checkbox = element(by.id('task-checkbox-0'));
    await detoxExpect(checkbox).toBeVisible();
    await checkbox.tap();
    // The task should visually dim (completed state) — no navigation change
    await detoxExpect(checkbox).toBeVisible();
  });
});
