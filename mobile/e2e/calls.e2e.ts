import {device, element, by, expect as detoxExpect, waitFor} from 'detox';

describe('Call flow', () => {
  beforeAll(async () => {
    await device.launchApp({newInstance: false}); // reuse logged-in session from login.e2e
  });

  it('shows call history screen', async () => {
    await element(by.text('Calls')).tap();
    await detoxExpect(element(by.text('Calls'))).toBeVisible();
  });

  it('can search calls by keyword', async () => {
    await element(by.id('calls-search-input')).typeText('interested');
    await waitFor(element(by.id('calls-list'))).toBeVisible().withTimeout(3000);
  });

  it('navigates to call detail on tap', async () => {
    // Assumes at least one call exists in the test account
    await element(by.id('call-row-0')).tap();
    await detoxExpect(element(by.text('Summary'))).toBeVisible();
  });
});
